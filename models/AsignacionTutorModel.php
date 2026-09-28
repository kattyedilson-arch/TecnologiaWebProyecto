<?php
// =========================================================
// MODELO: ASIGNACIÓN DE TUTOR MG (AsignacionTutorModel.php)
// ---------------------------------------------------------
// HU-025 / HU-026. Historial de asignaciones de tutor sobre
// un expediente (declaraciones_modalidad). Nunca se borra:
// cada registro se cierra como 'reemplazada' (cambio/renuncia)
// o 'finalizada' y se abre uno nuevo. Solo existe UNA
// asignación 'vigente' por expediente a la vez.
// =========================================================
class AsignacionTutorModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Historial completo de asignaciones de un expediente.
     * @param int $id_declaracion Identificador de la declaración
     * @return array Asignaciones de la más reciente a la más antigua
     */
    public function obtenerHistorial($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, t.id_usuario AS tutor_id_usuario, t.especialidad,
                    u.nombre AS tutor_nombre, u.apellido AS tutor_apellido, u.correo AS tutor_correo,
                    r.nombre AS registrado_nombre, s.apellido AS registrado_apellido
             FROM asignaciones_tutor a
             INNER JOIN tutores t ON a.id_tutor = t.id_tutor
             INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
             LEFT JOIN usuarios r ON a.registrado_por = r.id_usuario
             LEFT JOIN usuarios s ON a.registrado_por = s.id_usuario
             WHERE a.id_declaracion = :id
             ORDER BY a.id_asignacion DESC"
        );
        $stmt->execute([':id' => $id_declaracion]);
        return $stmt->fetchAll();
    }

    /**
     * La asignación vigente de un expediente.
     * @param int $id_declaracion Identificador de la declaración
     * @return array|false Asignación vigente o false
     */
    public function obtenerVigente($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, t.id_usuario AS tutor_id_usuario, t.especialidad,
                    u.nombre AS tutor_nombre, u.apellido AS tutor_apellido, u.correo AS tutor_correo
             FROM asignaciones_tutor a
             INNER JOIN tutores t ON a.id_tutor = t.id_tutor
             INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
             WHERE a.id_declaracion = :id AND a.estado = 'vigente'
             ORDER BY a.id_asignacion DESC
             LIMIT 1"
        );
        $stmt->execute([':id' => $id_declaracion]);
        return $stmt->fetch();
    }

    /**
     * Una asignación por su identificador (con tutor).
     * @param int $id_asignacion Identificador de la asignación
     * @return array|false Fila o false
     */
    public function obtenerPorId($id_asignacion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.*, t.id_usuario AS tutor_id_usuario, t.especialidad,
                    u.nombre AS tutor_nombre, u.apellido AS tutor_apellido, u.correo AS tutor_correo
             FROM asignaciones_tutor a
             INNER JOIN tutores t ON a.id_tutor = t.id_tutor
             INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
             WHERE a.id_asignacion = :id"
        );
        $stmt->execute([':id' => $id_asignacion]);
        return $stmt->fetch();
    }

    /**
     * Crea la primera asignación de tutor de un expediente (HU-025).
     * No permite duplicar una asignación vigente.
     * @param int $id_declaracion Identificador de la declaración
     * @param int $id_tutor Tutor elegido
     * @param array $datos Opciones: referencia_decanatura, disponibilidad_consultada, observaciones
     * @param int $registradoPor Usuario que asigna
     * @return int|false Id de la asignación o false
     */
    public function asignar($id_declaracion, $id_tutor, $datos = [], $registradoPor = null)
    {
        try {
            $this->pdo->beginTransaction();

            $vigente = $this->obtenerVigente($id_declaracion);
            if ($vigente) {
                $this->pdo->rollBack();
                return false;
            }

            $stmt = $this->pdo->prepare(
                "INSERT INTO asignaciones_tutor
                    (id_declaracion, id_tutor, estado, referencia_decanatura,
                     disponibilidad_consultada, observaciones, registrado_por)
                 VALUES (:declaracion, :tutor, 'vigente', :referencia,
                         :disponibilidad, :observaciones, :registrado)"
            );
            $ok = $stmt->execute([
                ':declaracion'      => $id_declaracion,
                ':tutor'            => $id_tutor,
                ':referencia'       => trim($datos['referencia_decanatura'] ?? '') ?: null,
                ':disponibilidad'   => !empty($datos['disponibilidad_consultada']) ? 1 : 0,
                ':observaciones'    => trim($datos['observaciones'] ?? '') ?: null,
                ':registrado'       => $registradoPor ?: null,
            ]);
            if (!$ok) {
                $this->pdo->rollBack();
                return false;
            }

            $id = (int)$this->pdo->lastInsertId();
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
     * Cambio o renuncia de tutor (HU-026): cierra la vigente con
     * motivo y referencia de Decanatura, y crea la nueva asignación.
     * @param int $id_declaracion Identificador de la declaración
     * @param int $id_tutorNuevo Nuevo tutor
     * @param array $datos motivo_fin, referencia_decanatura, disponibilidad_consultada, observaciones
     * @param int $registradoPor Usuario que ejecuta el cambio
     * @return int|false Id de la nueva asignación o false
     */
    public function reemplazar($id_declaracion, $id_tutorNuevo, $datos = [], $registradoPor = null)
    {
        try {
            $this->pdo->beginTransaction();

            $vigente = $this->obtenerVigente($id_declaracion);
            if (!$vigente) {
                $this->pdo->rollBack();
                return false;
            }

            // Cierra la vigente como 'reemplazada' conservando su propia referencia
            $cierre = $this->pdo->prepare(
                "UPDATE asignaciones_tutor
                 SET estado = 'reemplazada', fecha_fin = NOW(), motivo_fin = :motivo
                 WHERE id_asignacion = :id"
            );
            $cierre->execute([
                ':motivo' => trim($datos['motivo_fin'] ?? '') ?: null,
                ':id'     => $vigente['id_asignacion'],
            ]);

            // Nueva asignación vigente
            $stmt = $this->pdo->prepare(
                "INSERT INTO asignaciones_tutor
                    (id_declaracion, id_tutor, estado, referencia_decanatura,
                     disponibilidad_consultada, observaciones, registrado_por)
                 VALUES (:declaracion, :tutor, 'vigente', :referencia,
                         :disponibilidad, :observaciones, :registrado)"
            );
            $ok = $stmt->execute([
                ':declaracion'      => $id_declaracion,
                ':tutor'            => $id_tutorNuevo,
                ':referencia'       => trim($datos['referencia_decanatura'] ?? '') ?: null,
                ':disponibilidad'   => !empty($datos['disponibilidad_consultada']) ? 1 : 0,
                ':observaciones'    => trim($datos['observaciones'] ?? '') ?: null,
                ':registrado'       => $registradoPor ?: null,
            ]);
            if (!$ok) {
                $this->pdo->rollBack();
                return false;
            }

            $id = (int)$this->pdo->lastInsertId();
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
     * Renuncia definitiva: finaliza la asignación vigente sin crear otra.
     * @param int $id_declaracion Identificador de la declaración
     * @param string $motivo Motivo de la renuncia
     * @param string|null $referencia Referencia de Decanatura
     * @return bool True si se finalizó
     */
    public function finalizar($id_declaracion, $motivo, $referencia = null)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE asignaciones_tutor
             SET estado = 'finalizada', fecha_fin = NOW(),
                 motivo_fin = :motivo, referencia_decanatura = COALESCE(:referencia, referencia_decanatura)
             WHERE id_declaracion = :declaracion AND estado = 'vigente'"
        );
        return $stmt->execute([
            ':motivo'      => trim($motivo ?? ''),
            ':referencia'  => trim($referencia ?? '') ?: null,
            ':declaracion' => $id_declaracion,
        ]);
    }

    /**
     * Vincula el número de carta de asignación a una asignación.
     * @param int $id_asignacion Identificador de la asignación
     * @param string $numeroCarta Número de correlativo generado
     * @return bool True si se actualizó
     */
    public function registrarCarta($id_asignacion, $numeroCarta)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE asignaciones_tutor SET numero_carta = :carta WHERE id_asignacion = :id"
        );
        return $stmt->execute([':carta' => $numeroCarta, ':id' => $id_asignacion]);
    }

    /**
     * Cantidad de expedientes vigentes asignados a un tutor (RN-MG-08).
     * @param int $id_tutor Identificador del tutor
     * @return int Total de asignaciones vigentes
     */
    public function contarVigentesPorTutor($id_tutor)
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM asignaciones_tutor
             WHERE id_tutor = :id AND estado = 'vigente'"
        );
        $stmt->execute([':id' => $id_tutor]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Asignaciones vigentes de un tutor con el detalle del expediente
     * (estudiante, modalidad y estado) para el panel del docente.
     * @param int $id_tutor Identificador del tutor
     * @return array Asignaciones vigentes
     */
    public function obtenerVigentesPorTutor($id_tutor)
    {
        $stmt = $this->pdo->prepare(
            "SELECT a.id_asignacion, a.id_declaracion AS id_expediente,
                    a.referencia_decanatura, a.numero_carta, a.fecha_asignacion,
                    d.estado AS estado_expediente, d.etapa_actual,
                    m.nombre AS modalidad_nombre, m.codigo AS modalidad_codigo,
                    es.id_usuario AS estudiante_id_usuario,
                    eu.nombre AS estudiante_nombre, eu.apellido AS estudiante_apellido,
                    eu.correo AS estudiante_correo
             FROM asignaciones_tutor a
             INNER JOIN declaraciones_modalidad d ON a.id_declaracion = d.id_declaracion
             INNER JOIN modalidades_catalogo m ON d.id_modalidad = m.id_modalidad
             INNER JOIN estudiantes es ON d.id_estudiante = es.id_estudiante
             INNER JOIN usuarios eu ON es.id_usuario = eu.id_usuario
             WHERE a.id_tutor = :id AND a.estado = 'vigente'
             ORDER BY a.fecha_asignacion DESC"
        );
        $stmt->execute([':id' => $id_tutor]);
        return $stmt->fetchAll();
    }

    /**
     * Tutores con su carga actual de expedientes (para el selector
     * de asignación y la advertencia de RN-MG-08).
     * @return array Tutores con carga, especialidad y disponibilidad
     */
    public function listarTutoresConCarga()
    {
        return $this->pdo->query(
            "SELECT t.id_tutor, t.id_usuario, t.especialidad, u.nombre, u.apellido, u.correo,
                    (SELECT COUNT(*) FROM asignaciones_tutor a
                     WHERE a.id_tutor = t.id_tutor AND a.estado = 'vigente') AS carga_actual,
                    (SELECT COUNT(*) FROM disponibilidad_tutor d
                     WHERE d.id_tutor = t.id_tutor) AS disponibilidad_registrada
             FROM tutores t
             INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
             WHERE u.estado = 'activo'
             ORDER BY u.nombre ASC, u.apellido ASC"
        )->fetchAll();
    }
}