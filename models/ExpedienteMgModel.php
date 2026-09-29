<?php
// =========================================================
// MODELO: EXPEDIENTE MG (ExpedienteMgModel.php)
// ---------------------------------------------------------
// HU-024. El "expediente" de Modalidades de Grado vive sobre
// declaraciones_modalidad (decisión del proyecto: cerrar brechas
// sobre el modelo actual, sin crear una entidad paralela). Este
// modelo agrupa listados, ficha y alta manual de expedientes;
// las etapas se operan vía EtapasExpedienteModel y las
// asignaciones de tutor vía AsignacionTutorModel.
// =========================================================
class ExpedienteMgModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Listado paginable de expedientes con búsqueda y filtros:
     * q, id_cohorte, id_modalidad, etapa_actual, estado.
     * @param array $filtros Parámetros del listado
     * @return array Resultado de paginarConsulta con columnas extra
     */
    public function listar($filtros = [])
    {
        require_once __DIR__ . '/../includes/lista_helper.php';

        $params = parametrosLista();
        $q = $params['q'];
        $cohorte = max(0, (int)($filtros['id_cohorte'] ?? 0));
        $modalidad = max(0, (int)($filtros['id_modalidad'] ?? 0));
        $etapa = trim((string)($filtros['etapa_actual'] ?? ''));
        $estado = trim((string)($filtros['estado'] ?? ''));

        $busqueda = condicionBusqueda([
            'u.nombre', 'u.apellido', 'e.registro_universitario', 'u.correo',
        ], $q);

        $etapasPermitidas = ['previa', 'mg1', 'mg2', 'finalizado'];
        $estadosPermitidos = ['borrador', 'enviada', 'en_revision', 'aprobada', 'rechazada', 'cancelada', 'reprobado', 'abandono'];
        $etapa = in_array($etapa, $etapasPermitidas, true) ? $etapa : '';
        $estado = in_array($estado, $estadosPermitidos, true) ? $estado : '';

        $filtrosSql = [];
        $paramsFila = $busqueda['params'];
        if ($cohorte > 0) {
            $filtrosSql[] = 'd.id_cohorte = :f_cohorte';
            $paramsFila[':f_cohorte'] = $cohorte;
        }
        if ($modalidad > 0) {
            $filtrosSql[] = 'd.id_modalidad = :f_modalidad';
            $paramsFila[':f_modalidad'] = $modalidad;
        }
        if ($etapa !== '') {
            $filtrosSql[] = 'd.etapa_actual = :f_etapa';
            $paramsFila[':f_etapa'] = $etapa;
        }
        if ($estado !== '') {
            $filtrosSql[] = 'd.estado = :f_estado';
            $paramsFila[':f_estado'] = $estado;
        }
        $where = whereLista($busqueda['condicion'], $filtrosSql);

        $desde = "FROM declaraciones_modalidad d
                  INNER JOIN estudiantes e ON d.id_estudiante = e.id_usuario
                  INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                  LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
                  INNER JOIN modalidades_catalogo m ON d.id_modalidad = m.id_modalidad
                  LEFT JOIN cohortes_mg ch ON d.id_cohorte = ch.id_cohorte
                  LEFT JOIN periodos p ON d.id_periodo = p.id_periodo
                  LEFT JOIN asignaciones_tutor at ON d.id_declaracion = at.id_declaracion AND at.estado = 'vigente'
                  LEFT JOIN tutores tt ON at.id_tutor = tt.id_tutor
                  LEFT JOIN usuarios ut ON tt.id_usuario = ut.id_usuario";

        // Ordenamiento permitido (lista blanca)
        $columnas = [
            'id'          => 'd.id_declaracion',
            'estudiante'  => 'u.apellido',
            'ru'          => 'e.registro_universitario',
            'modalidad'   => 'm.nombre',
            'cohorte'     => 'ch.codigo',
            'etapa'       => 'd.etapa_actual',
            'estado'      => 'd.estado',
            'fecha'       => 'd.fecha_enviada',
        ];
        $orden = $columnas[$params['col']] ?? 'd.fecha_enviada';
        $dirSql = $params['dir'];

        $sqlConteo = "SELECT COUNT(*) " . $desde . $where;
        $sqlDatos  = "SELECT d.id_declaracion, d.id_cohorte, d.id_periodo, d.etapa_actual,
                             d.estado, d.titulo_proyecto, d.tutor_facultativo,
                             d.fecha_enviada, d.fecha_creacion, d.fecha_revision,
                             e.id_estudiante, e.registro_universitario,
                             u.nombre, u.apellido, u.correo,
                             c.nombre_carrera,
                             m.codigo AS modalidad_codigo, m.nombre AS modalidad_nombre,
                             ch.codigo AS cohorte_codigo, ch.nombre AS cohorte_nombre,
                             p.nombre AS periodo_nombre,
                             tt.id_tutor AS tutor_id, ut.nombre AS tutor_nombre,
                             ut.apellido AS tutor_apellido, at.numero_carta
                      " . $desde . $where .
                      " ORDER BY " . $orden . " " . $dirSql . ", d.id_declaracion DESC";

        // Nota: el término de búsqueda y todos los filtros van por
        // prepared statement; las columnas de orden usan lista blanca.
        return paginarConsulta(
            $this->pdo, $sqlConteo, $sqlDatos, $paramsFila,
            $filtros['pagina'] ?? max(1, (int)($_GET['pagina'] ?? 1)),
            $filtros['por_pagina'] ?? 15
        );
    }

    /**
     * Ficha completa de un expediente (declaración + estudiante + tutor
     * vigente + cohorte). No incluye etapas ni documentos (otras consultas).
     * @param int $id_declaracion Identificador de la declaración
     * @return array|false Ficha o false
     */
    public function obtenerPorId($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, e.id_estudiante, e.registro_universitario, e.semestre,
                    e.id_usuario AS estudiante_id_usuario,
                    u.nombre AS estudiante_nombre, u.apellido AS estudiante_apellido,
                    u.correo AS estudiante_correo, u.telefono AS estudiante_telefono,
                    c.nombre_carrera,
                    m.codigo AS modalidad_codigo, m.nombre AS modalidad_nombre,
                    m.tipo AS modalidad_tipo, m.requiere_tutor,
                    ch.codigo AS cohorte_codigo, ch.nombre AS cohorte_nombre,
                    ch.id_periodo AS cohorte_periodo,
                    p.nombre AS periodo_nombre,
                    at.id_asignacion, at.id_tutor AS tutor_id, at.numero_carta,
                    at.disponibilidad_consultada, at.referencia_decanatura,
                    tt.id_usuario AS tutor_id_usuario, tt.especialidad,
                    ut.nombre AS tutor_nombre, ut.apellido AS tutor_apellido,
                    ut.correo AS tutor_correo
             FROM declaraciones_modalidad d
             INNER JOIN estudiantes e ON d.id_estudiante = e.id_usuario
             INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
             LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
             INNER JOIN modalidades_catalogo m ON d.id_modalidad = m.id_modalidad
             LEFT JOIN cohortes_mg ch ON d.id_cohorte = ch.id_cohorte
             LEFT JOIN periodos p ON d.id_periodo = p.id_periodo
             LEFT JOIN asignaciones_tutor at ON d.id_declaracion = at.id_declaracion AND at.estado = 'vigente'
             LEFT JOIN tutores tt ON at.id_tutor = tt.id_tutor
             LEFT JOIN usuarios ut ON tt.id_usuario = ut.id_usuario
             WHERE d.id_declaracion = :id
             LIMIT 1"
        );
        $stmt->execute([':id' => $id_declaracion]);
        return $stmt->fetch();
    }

    /**
     * Alta manual de un expediente (declaración 'enviada' + etapa previa).
     * @param int $id_usuarioEstudiante Usuario del estudiante (id_estudiante = id_usuario)
     * @param int $id_modalidad Modalidad elegida
     * @param int $id_periodo Periodo
     * @param int|null $id_cohorte Cohorte (opcional)
     * @param string|null $titulo Título/tema del trabajo (opcional)
     * @return int|false Id del expediente o false
     */
    public function crearManual($id_usuarioEstudiante, $id_modalidad, $id_periodo, $id_cohorte = null, $titulo = null)
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                "INSERT INTO declaraciones_modalidad
                    (id_estudiante, id_modalidad, id_periodo, id_cohorte, estado, titulo_proyecto, fecha_enviada)
                 VALUES (:usuario, :modalidad, :periodo, :cohorte, 'enviada', :titulo, NOW())"
            );
            $ok = $stmt->execute([
                ':usuario'   => $id_usuarioEstudiante,
                ':modalidad' => $id_modalidad,
                ':periodo'   => $id_periodo,
                ':cohorte'   => $id_cohorte ?: null,
                ':titulo'    => trim($titulo ?? '') ?: null,
            ]);
            if (!$ok) {
                $this->pdo->rollBack();
                return false;
            }
            $id = (int)$this->pdo->lastInsertId();

            $etapa = $this->pdo->prepare(
                "INSERT INTO etapas_expediente (id_declaracion, etapa, fecha_inicio) VALUES (:id, 'previa', NOW())"
            );
            $etapa->execute([':id' => $id]);

            $this->pdo->commit();
            return $id;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * Lista de estudiantes con cuenta (para alta manual).
     */
    public function listarEstudiantes()
    {
        return $this->pdo->query(
            "SELECT e.id_estudiante, e.id_usuario, e.registro_universitario, u.nombre, u.apellido, u.correo
             FROM estudiantes e
             INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
             WHERE u.estado = 'activo'
             ORDER BY u.apellido ASC, u.nombre ASC"
        )->fetchAll();
    }

    /**
     * Lista de modalidades del catálogo para los filtros y el alta.
     */
    public function listarModalidades()
    {
        return $this->pdo->query(
            "SELECT ms.*, ms.codigo FROM modalidades_catalogo ms
             ORDER BY ms.codigo ASC"
        )->fetchAll();
    }

    /**
     * Conteos resumidos para el encabezado del listado.
     * @return array Totales por estado y por etapa
     */
    public function contarResumen()
    {
        $resumen = ['estados' => [], 'etapas' => [], 'total' => 0];
        foreach ($this->pdo->query(
            "SELECT estado, COUNT(*) AS total FROM declaraciones_modalidad GROUP BY estado"
        ) as $fila) {
            $resumen['estados'][$fila['estado']] = (int)$fila['total'];
        }
        foreach ($this->pdo->query(
            "SELECT etapa_actual, COUNT(*) AS total FROM declaraciones_modalidad
             WHERE estado NOT IN ('reprobado','abandono','cancelada')
             GROUP BY etapa_actual"
        ) as $fila) {
            $resumen['etapas'][$fila['etapa_actual']] = (int)$fila['total'];
        }
        $resumen['total'] = (int)$this->pdo->query(
            "SELECT COUNT(*) FROM declaraciones_modalidad"
        )->fetchColumn();
        return $resumen;
    }

    /**
     * Si la declaración está en estado 'aprobada' no se permiten
     * transiciones MG hacia atrás. Helper de uso en ficha.
     */
    public function esExpedienteAbierto($fila)
    {
        return in_array($fila['estado'] ?? '', [
            'borrador', 'enviada', 'en_revision', 'aprobada',
        ], true);
    }
}