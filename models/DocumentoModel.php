<?php
// =========================================================
// MODELO: DOCUMENTOS GENERADOS MG (DocumentoModel.php)
// ---------------------------------------------------------
// HU-027. Cartas y citaciones del módulo MG se generan desde
// plantillas HTML editables (plantillas_documento). La variable
// {{...}} se reemplaza siempre escapando el valor (whitelist),
// el número correlativo es transaccional por tipo+año
// (contadores_documento) y cada emisión guarda un snapshot en
// documentos_generados para poder reimprimir la versión exacta.
// =========================================================
class DocumentoModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    // -----------------------------------------------------
    // Plantillas
    // -----------------------------------------------------

    /**
     * Plantilla activa por su código único.
     * @param string $codigo Código único (p. ej. CARTA_ASIGNACION_TUTOR)
     * @return array|false Fila de plantilla o false
     */
    public function obtenerPlantillaPorCodigo($codigo)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM plantillas_documento
             WHERE codigo = :codigo AND activa = 1
             LIMIT 1"
        );
        $stmt->execute([':codigo' => $codigo]);
        return $stmt->fetch();
    }

    /**
     * Todas las plantillas registradas (para la pantalla de gestión).
     * @return array Plantillas ordenadas por código
     */
    public function listarPlantillas()
    {
        return $this->pdo->query(
            "SELECT p.*, u.nombre AS actualizado_nombre
             FROM plantillas_documento p
             LEFT JOIN usuarios u ON p.actualizado_por = u.id_usuario
             ORDER BY p.codigo ASC"
        )->fetchAll();
    }

    /**
     * Guarda los cambios de una plantilla. Si el cuerpo cambió,
     * incrementa la versión.
     * @param int $id_plantilla Identificador de la plantilla
     * @param string $nombre Nombre visible
     * @param string $cuerpoHttp Cuerpo HTML con {{variables}}
     * @param int|null $usuario Usuario que edita
     * @return bool True si se actualizó
     */
    public function guardarPlantilla($id_plantilla, $nombre, $cuerpoHttp, $usuario = null)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE plantillas_documento
             SET version = IF(cuerpo_html = :actual, version, version + 1),
                 nombre = :nombre,
                 cuerpo_html = :cuerpo,
                 actualizado_por = :usuario
             WHERE id_plantilla = :id"
        );
        return $stmt->execute([
            ':nombre' => trim($nombre),
            ':cuerpo' => $cuerpoHttp,
            ':actual' => $cuerpoHttp,
            ':usuario'=> $usuario,
            ':id'     => $id_plantilla,
        ]);
    }

    // -----------------------------------------------------
    // Renderizado
    // -----------------------------------------------------

    /**
     * Reemplaza las variables {{...}} de la plantilla por sus valores
     * ESCAPADOS para salida segura en HTML. Solo se reemplazan claves
     * presentes en $variables (whitelist); las restantes se dejan
     * marcadas para que el usuario las detecte.
     * @param string $cuerpo Plantilla HTML
     * @param array $variables Variables permitidas clave => valor
     * @return string Plantilla renderizada
     */
    public function render($cuerpo, $variables)
    {
        $salida = $cuerpo;
        $valores = [];
        foreach ($variables as $clave => $valor) {
            $valores['{{' . $clave . '}}'] = e($valor);
        }
        $marcas = array_keys($valores);
        if (count($marcas) > 0) {
            $salida = str_replace($marcas, array_values($valores), $salida);
        }
        // Marca las variables que quedaron sin reemplazar
        $salida = preg_replace('/\{\{(\w+)\}\}/', '<span class="text-danger">{{\1}}</span>', $salida);
        return $salida;
    }

    // -----------------------------------------------------
    // Correlativo transaccional
    // -----------------------------------------------------

    /**
     * Entrega el siguiente número correlativo para un tipo de
     * documento y año, con bloqueo de fila (SELECT ... FOR UPDATE)
     * para que sea seguro bajo concurrencia.
     * @param string $tipo Tipo de documento (p. ej. CARTA_ASIGNACION_TUTOR)
     * @param int $anio Año del correlativo
     * @return string Correlativo formateado: ASC-2026-0001
     */
    public function siguienteCorrelativo($tipo, $anio)
    {
        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare(
                "SELECT ultimo_numero FROM contadores_documento WHERE tipo = :tipo AND anio = :anio FOR UPDATE"
            );
            $stmt->execute([':tipo' => $tipo, ':anio' => $anio]);
            $ultimo = (int)$stmt->fetchColumn();

            $nuevo = $ultimo + 1;
            $upsert = $this->pdo->prepare(
                "INSERT INTO contadores_documento (tipo, anio, ultimo_numero)
                 VALUES (:tipo, :anio, :nuevo)
                 ON DUPLICATE KEY UPDATE ultimo_numero = :nuevo_b"
            );
            $upsert->execute([':tipo' => $tipo, ':anio' => $anio, ':nuevo' => $nuevo, ':nuevo_b' => $nuevo]);

            $this->pdo->commit();
            return sprintf('%s-%04d-%04d', $tipo, $anio, $nuevo);
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return '';
        }
    }

    // -----------------------------------------------------
    // Emisión y consulta de documentos
    // -----------------------------------------------------

    /**
     * Registra un documento generado con su snapshot.
     * @param array $datos tipo, id_plantilla, id_declaracion, destinatario,
     *                     numero_correlativo, contenido_snapshot, generado_por
     * @return int|false Id del documento o false
     */
    public function generar($datos)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO documentos_generados
                (id_plantilla, tipo, id_declaracion, destinatario, numero_correlativo,
                 contenido_snapshot, generado_por)
             VALUES (:plantilla, :tipo, :declaracion, :destinatario, :numero,
                     :snapshot, :usuario)"
        );
        $ok = $stmt->execute([
            ':plantilla'    => $datos['id_plantilla'],
            ':tipo'         => $datos['tipo'],
            ':declaracion'  => $datos['id_declaracion'],
            ':destinatario' => $datos['destinatario'] ?: null,
            ':numero'       => $datos['numero_correlativo'] ?: null,
            ':snapshot'     => $datos['contenido_snapshot'],
            ':usuario'      => $datos['generado_por'] ?: null,
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    /**
     * Documentos emitidos para una declaración.
     * @param int $id_declaracion Identificador de la declaración
     * @return array Documentos de la más reciente a la más antigua
     */
    public function obtenerPorDeclaracion($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, u.nombre AS generado_nombre
             FROM documentos_generados d
             LEFT JOIN usuarios u ON d.generado_por = u.id_usuario
             WHERE d.id_declaracion = :id
             ORDER BY d.id_documento DESC"
        );
        $stmt->execute([':id' => $id_declaracion]);
        return $stmt->fetchAll();
    }

    /**
     * Un documento por su identificador.
     * @param int $id_documento Identificador del documento
     * @return array|false Fila o false
     */
    public function obtenerPorId($id_documento)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM documentos_generados WHERE id_documento = :id");
        $stmt->execute([':id' => $id_documento]);
        return $stmt->fetch();
    }

    /**
     * Devuelve la lista de variables {{...}} usadas en una plantilla.
     * @param string $cuerpo Cuerpo HTML de la plantilla
     * @return array Variables únicas en orden de aparición
     */
    public function listarVariables($cuerpo)
    {
        preg_match_all('/\{\{(\w+)\}\}/', (string)$cuerpo, $coincidencias);
        return array_values(array_unique($coincidencias[1]));
    }
}