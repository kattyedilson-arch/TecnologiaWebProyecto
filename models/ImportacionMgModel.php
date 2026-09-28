<?php
// =========================================================
// MODELO: IMPORTACIÓN DE PADRÓN MG (ImportacionMgModel.php)
// ---------------------------------------------------------
// HU-023. El coordinador sube un CSV con el padrón de
// estudiantes que cursarán Modalidades de Grado. El sistema
// previsualiza fila por fila (ok / advertencia / error /
// pendiente_cuenta / omitida) y solo al confirmar crea los
// expedientes (declaraciones_modalidad con estado 'enviada',
// etapa 'previa'). Nunca se sube el archivo al servidor: se
// lee en memoria con fgetcsv y se valida servidor-side.
// =========================================================
class ImportacionMgModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lee un archivo CSV subido y devuelve sus filas como arreglos
     * asociativos normalizados. No guarda el archivo en disco.
     * @param array $archivo Entrada $_FILES['archivo']
     * @return array ['cabeceras'=>[], 'filas'=>[['campo'=>valor]]]
     */
    public function parsear($archivo)
    {
        $error = '';
        if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['error' => 'No se recibió el archivo o falló su subida.', 'datos' => []];
        }
        if (($archivo['size'] ?? 0) > 2 * 1024 * 1024) {
            return ['error' => 'El archivo supera el tamaño máximo de 2 MB.', 'datos' => []];
        }
        $nombre = $archivo['name'] ?? '';
        $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            return ['error' => 'Solo se permiten archivos .csv.', 'datos' => []];
        }

        $ruta = $archivo['tmp_name'];
        $gestor = @fopen($ruta, 'r');
        if (!$gestor) {
            return ['error' => 'No se pudo leer el archivo.', 'datos' => []];
        }

        // Autodetección de delimitador: ; o ,
        $primera = fgets($gestor);
        rewind($gestor);
        $delimitador = substr_count($primera ?? '', ';') >= substr_count($primera ?? '', ',') ? ';' : ',';

        $cabeceras = [];
        $filas = [];
        $numLinea = 0;
        while (($datos = fgetcsv($gestor, 0, $delimitador)) !== false) {
            $numLinea++;
            if ($numLinea === 1) {
                $cabeceras = array_values($datos);
                continue;
            }
            // Omite líneas totalmente vacías
            if (count(array_filter($datos, 'trim')) === 0) {
                continue;
            }
            $fila = [];
            foreach ($cabeceras as $i => $cabecera) {
                $fila[$this->normalizarClave($cabecera)] = trim((string)($datos[$i] ?? ''));
            }
            $filas[] = $fila;
        }
        fclose($gestor);

        return ['error' => null, 'datos' => ['cabeceras' => $cabeceras, 'filas' => $filas]];
    }

    /**
     * Normaliza el nombre de una columna para el mapeo flexible:
     * minúsculas, sin tildes/espacios y sin puntos.
     */
    private function normalizarClave($texto)
    {
        $texto = mb_strtolower(trim((string)$texto), 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
        ]);
        return preg_replace('/[^a-z0-9]/', '', $texto);
    }

    /**
     * Resuelve la columna de registro universitario buscando entre
     * los alias conocidos de la cabecera.
     */
    private function clavesRu($fila)
    {
        $alias = ['registrouniversitario', 'registro', 'ru', 'rutadeestudiante', 'matricula'];
        foreach ($alias as $a) {
            foreach ($fila as $clave => $valor) {
                if ($this->normalizarClave($clave) === $a && $valor !== '') {
                    return $valor;
                }
            }
        }
        return '';
    }

    private function clave($fila, $aliasPosibles)
    {
        foreach ($aliasPosibles as $a) {
            foreach ($fila as $clave => $valor) {
                if ($this->normalizarClave($clave) === $a && $valor !== '') {
                    return $valor;
                }
            }
        }
        return '';
    }

    /**
     * Previsualiza las filas: valida cada una contra la base sin
     * escribir nada. Resuelve estudiante, modalidad y cohorte.
     * @param array $filas Filas crudas del CSV
     * @return array Filas anotadas con [resultado, mensaje, datos]
     */
    public function previsualizar($filas)
    {
        $periodoActivo = $this->periodoActivo() ?: null;
        $resultado = [];

        foreach ($filas as $n => $fila) {
            $ru = $this->clavesRu($fila);
            $correo = $this->clave($fila, ['correo', 'email', 'correoelectronico']);
            $modalidadCodigo = $this->clave($fila, ['modalidad', 'codigomodalidad', 'idmodalidad']);
            $cohorteCodigo = $this->clave($fila, ['cohorte', 'codigocohorte', 'idcohorte']);
            $nombre = $this->clave($fila, ['nombre', 'nombres']) ?: '—';
            $apellido = $this->clave($fila, ['apellido', 'apellidos']) ?: '—';

            $item = [
                'fila'       => $n + 2, // línea física del CSV (1 = cabeceras)
                'ru'         => $ru,
                'correo'     => $correo,
                'nombre'     => $nombre,
                'apellido'   => $apellido,
                'modalidad'  => $modalidadCodigo,
                'cohorte'    => $cohorteCodigo,
                'resultado'  => 'error',
                'mensaje'    => '',
                'datos'      => [],
            ];

            // 1) El registro universitario es obligatorio
            if ($ru === '') {
                $item['mensaje'] = 'Fila omitida: sin registro universitario.';
                $item['resultado'] = 'error';
                $resultado[] = $item;
                continue;
            }

            // 2) Busca la cuenta del estudiante por RU
            $estudiante = $this->buscarEstudiante($ru, $correo);
            if (!$estudiante) {
                $item['mensaje'] = 'pendiente_cuenta';
                $item['resultado'] = 'pendiente_cuenta';
                $resultado[] = $item;
                continue;
            }

            // 3) Modalidad por código
            $modalidad = $modalidadCodigo !== '' ? $this->buscarModalidad($modalidadCodigo) : null;
            if (!$modalidad) {
                $item['mensaje'] = 'No se encontró una modalidad con el código indicado.';
                $item['resultado'] = 'error';
                $resultado[] = $item;
                continue;
            }

            // 4) Cohorte (opcional pero validada si llega)
            $cohorte = null;
            if ($cohorteCodigo !== '') {
                $cohorte = $this->buscarCohorte($cohorteCodigo);
                if (!$cohorte) {
                    $item['mensaje'] = 'El código de cohorte no existe. Revisar la fila.';
                    $item['resultado'] = 'error';
                    $resultado[] = $item;
                    continue;
                }
            }

            // 5) Periodo: el de la cohorte o el periodo abierto
            $idPeriodo = $cohorte['id_periodo'] ?: ($periodoActivo ?? null);
            if (!$idPeriodo) {
                $item['mensaje'] = 'No hay un periodo abierto para crear el expediente.';
                $item['resultado'] = 'error';
                $resultado[] = $item;
                continue;
            }

            // 6) Duplicado por UNIQUE(id_estudiante, id_periodo)
            if ($this->existeDeclaracion($estudiante['id_usuario'], $idPeriodo)) {
                $item['mensaje'] = 'El estudiante ya tiene un expediente en este periodo.';
                $item['resultado'] = 'omitida';
                $resultado[] = $item;
                continue;
            }

            $item['resultado'] = 'ok';
            $item['mensaje'] = 'Lista para crear el expediente.';
            $item['datos'] = [
                'id_usuario'   => (int)$estudiante['id_usuario'],
                'id_estudiante'=> (int)$estudiante['id_estudiante'],
                'id_modalidad' => (int)$modalidad['id_modalidad'],
                'id_periodo'   => (int)$idPeriodo,
                'id_cohorte'   => $cohorte ? (int)$cohorte['id_cohorte'] : null,
            ];
            $resultado[] = $item;
        }

        return $resultado;
    }

    /**
     * Confirma una importación ya previsualizada: crea cabecera,
     * detalle y los expedientes de las filas 'ok', todo en una
     * transacción. Notifica a cada estudiante creado.
     * @param string $nombreArchivo Nombre del archivo original
     * @param array $filas Filas anotadas (salida de previsualizar)
     * @param int $usuario Usuario que importa
     * @return array ['ok'=>bool, 'mensaje'=>string, 'id_importacion'=>int|null, 'conteos'=>array]
     */
    public function confirmar($nombreArchivo, $filas, $usuario)
    {
        $totales = [
            'total' => count($filas),
            'ok' => 0, 'error' => 0, 'pendiente_cuenta' => 0, 'omitida' => 0,
            'expedientes_creados' => 0,
        ];
        foreach ($filas as $item) {
            $r = $item['resultado'];
            if (isset($totales[$r])) {
                $totales[$r]++;
            }
        }

        try {
            $this->pdo->beginTransaction();

            $stmtHead = $this->pdo->prepare(
                "INSERT INTO importaciones_mg
                    (nombre_archivo, total_filas, filas_ok, filas_error, expedientes_creados, importado_por)
                 VALUES (:nombre, :total, :ok, :error, :expedientes, :usuario)"
            );
            $ok = $stmtHead->execute([
                ':nombre'      => substr($nombreArchivo, 0, 255),
                ':total'       => $totales['total'],
                ':ok'          => $totales['ok'],
                ':error'       => $totales['error'] + $totales['pendiente_cuenta'],
                ':expedientes' => $totales['ok'],
                ':usuario'     => $usuario,
            ]);
            if (!$ok) {
                $this->pdo->rollBack();
                return ['ok' => false, 'mensaje' => 'No se pudo registrar la cabecera de importación.', 'id_importacion' => null, 'conteos' => $totales];
            }
            $idImportacion = (int)$this->pdo->lastInsertId();

            $stmtDet = $this->pdo->prepare(
                "INSERT INTO importaciones_mg_detalle
                    (id_importacion, fila, registro_universitario, nombre, apellido, correo,
                     modalidad, cohorte, resultado, mensaje)
                 VALUES (:id_imp, :fila, :ru, :nombre, :apellido, :correo,
                         :modalidad, :cohorte, :resultado, :mensaje)"
            );

            $stmtDecl = $this->pdo->prepare(
                "INSERT INTO declaraciones_modalidad
                    (id_estudiante, id_modalidad, id_periodo, id_cohorte, estado, fecha_enviada)
                 VALUES (:usuario, :modalidad, :periodo, :cohorte, 'enviada', NOW())"
            );

            $stmtEtapa = $this->pdo->prepare(
                "INSERT INTO etapas_expediente (id_declaracion, etapa, fecha_inicio)
                 VALUES (:id, 'previa', NOW())"
            );

            $notifModel = new NotificacionModel($this->pdo);
            $creados = 0;

            foreach ($filas as $item) {
                $stmtDet->execute([
                    ':id_imp'    => $idImportacion,
                    ':fila'      => $item['fila'],
                    ':ru'        => $item['ru'],
                    ':nombre'    => $item['nombre'],
                    ':apellido'  => $item['apellido'],
                    ':correo'    => $item['correo'],
                    ':modalidad' => $item['modalidad'],
                    ':cohorte'   => $item['cohorte'],
                    ':resultado' => $item['resultado'],
                    ':mensaje'   => mb_substr($item['mensaje'] ?? '', 0, 255),
                ]);

                if ($item['resultado'] !== 'ok') {
                    continue;
                }
                $d = $item['datos'];
                $okDecl = $stmtDecl->execute([
                    ':usuario'   => $d['id_usuario'],
                    ':modalidad' => $d['id_modalidad'],
                    ':periodo'   => $d['id_periodo'],
                    ':cohorte'   => $d['id_cohorte'],
                ]);
                if (!$okDecl) {
                    throw new PDOException('No se pudo crear la declaración');
                }
                $idDecl = (int)$this->pdo->lastInsertId();
                $stmtEtapa->execute([':id' => $idDecl]);

                if (!empty($item['correo']) || true) {
                    $notifModel->crear(
                        $d['id_usuario'],
                        'modalidad',
                        'Expediente de Modalidad de Grado creado',
                        'Tu expediente fue registrado desde el padrón. Revisa tu declaración.',
                        '/controllers/mg_expediente.php?id=' . $idDecl
                    );
                }
                $creados++;
            }

            $this->pdo->commit();
            return [
                'ok' => true,
                'mensaje' => 'Importación completada: ' . $creados . ' expediente(s) creado(s).',
                'id_importacion' => $idImportacion,
                'conteos' => $totales,
            ];
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'mensaje' => 'Falló la importación y se revirtió todo.', 'id_importacion' => null, 'conteos' => $totales];
        }
    }

    /**
     * Listado histórico de importaciones para la pestaña de registro.
     * @return array Importaciones con usuario que las ejecutó
     */
    public function obtenerImportaciones()
    {
        return $this->pdo->query(
            "SELECT i.*, u.nombre, u.apellido
             FROM importaciones_mg i
             LEFT JOIN usuarios u ON i.importado_por = u.id_usuario
             ORDER BY i.fecha_importacion DESC, i.id_importacion DESC
             LIMIT 50"
        )->fetchAll();
    }

    public function obtenerDetalle($id_importacion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM importaciones_mg_detalle
             WHERE id_importacion = :id ORDER BY fila ASC"
        );
        $stmt->execute([':id' => $id_importacion]);
        return $stmt->fetchAll();
    }

    // -----------------------------------------------------
    // Helpers de búsqueda
    // -----------------------------------------------------

    private function periodoActivo()
    {
        $stmt = $this->pdo->query(
            "SELECT id_periodo FROM periodos WHERE estado = 'abierto' ORDER BY fecha_inicio DESC LIMIT 1"
        );
        return $stmt->fetchColumn() ?: null;
    }

    private function buscarEstudiante($ru, $correo)
    {
        $sql = "SELECT e.id_estudiante, e.id_usuario, e.registro_universitario, u.nombre, u.apellido, u.correo
                FROM estudiantes e
                INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                WHERE e.registro_universitario = :ru OR u.correo = :correo OR u.correo = :ru2
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':ru' => $ru, ':correo' => $correo ?: '__noexiste__', ':ru2' => $ru]);
        return $stmt->fetch();
    }

    private function buscarModalidad($codigo)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM modalidades_catalogo WHERE codigo = :codigo OR nombre = :codigo2 LIMIT 1"
        );
        $stmt->execute([':codigo' => $codigo, ':codigo2' => $codigo]);
        return $stmt->fetch();
    }

    private function buscarCohorte($codigo)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM cohortes_mg WHERE codigo = :codigo LIMIT 1");
        $stmt->execute([':codigo' => $codigo]);
        return $stmt->fetch();
    }

    private function existeDeclaracion($idUsuario, $idPeriodo)
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM declaraciones_modalidad
             WHERE id_estudiante = :usuario AND id_periodo = :periodo"
        );
        $stmt->execute([':usuario' => $idUsuario, ':periodo' => $idPeriodo]);
        return (int)$stmt->fetchColumn() > 0;
    }
}