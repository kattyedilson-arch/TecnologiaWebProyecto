<?php
// =========================================================
// MODELO: TUTORÍAS (TutoriaModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'tutorias', el corazón del sistema.
// Una tutoría vincula a un ESTUDIANTE con un TUTOR para una
// MATERIA concreta, en una fecha/hora, con modalidad presencial
// o virtual. Ciclo de vida: pendiente -> confirmada -> realizada
// (o cancelada), y opcionalmente evaluación al terminar.
// =========================================================
class TutoriaModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista tutorías con nombres de estudiante, tutor, materia, carrera y evaluación.
     * @param string|null $filtro_estado Si se pasa (pendiente/confirmada/realizada/cancelada) filtra por estado
     * @return array Tutorías ordenadas por fecha/hora
     */
    public function obtenerTodas($filtro_estado = null)
    {
        $sql = "SELECT tu.*,
                       ue.nombre AS est_nombre, ue.apellido AS est_apellido, ue.correo AS est_correo,
                       ut.nombre AS tut_nombre, ut.apellido AS tut_apellido, ut.correo AS tut_correo,
                       m.nombre_materia, c.nombre_carrera,
                       ev.calificacion, ev.comentario AS ev_comentario
                FROM tutorias tu
                INNER JOIN estudiantes e ON tu.id_estudiante = e.id_estudiante
                INNER JOIN usuarios ue ON e.id_usuario = ue.id_usuario
                INNER JOIN tutores t ON tu.id_tutor = t.id_tutor
                INNER JOIN usuarios ut ON t.id_usuario = ut.id_usuario
                INNER JOIN materias m ON tu.id_materia = m.id_materia
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                LEFT JOIN evaluaciones_tutoria ev ON tu.id_tutoria = ev.id_tutoria";

        $params = [];
        if (!empty($filtro_estado)) {
            $sql .= " WHERE tu.estado = :estado";
            $params[':estado'] = $filtro_estado;
        }

        $sql .= " ORDER BY tu.fecha DESC, tu.hora_inicio DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Busca una tutoría por su identificador (con todos los datos relacionados).
     * @param int $id_tutoria Identificador de la tutoría
     * @return array|false Fila de la tutoría o false
     */
    public function obtenerPorId($id_tutoria)
    {
        $sql = "SELECT tu.*,
                       ue.nombre AS est_nombre, ue.apellido AS est_apellido, ue.correo AS est_correo, ue.telefono AS est_telefono,
                       ut.nombre AS tut_nombre, ut.apellido AS tut_apellido, ut.correo AS tut_correo, ut.telefono AS tut_telefono,
                       m.nombre_materia, c.nombre_carrera,
                       ev.calificacion, ev.comentario AS ev_comentario
                FROM tutorias tu
                INNER JOIN estudiantes e ON tu.id_estudiante = e.id_estudiante
                INNER JOIN usuarios ue ON e.id_usuario = ue.id_usuario
                INNER JOIN tutores t ON tu.id_tutor = t.id_tutor
                INNER JOIN usuarios ut ON t.id_usuario = ut.id_usuario
                INNER JOIN materias m ON tu.id_materia = m.id_materia
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                LEFT JOIN evaluaciones_tutoria ev ON tu.id_tutoria = ev.id_tutoria
                WHERE tu.id_tutoria = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id_tutoria]);
        return $stmt->fetch();
    }

    /**
     * Tutorías solicitadas por un estudiante en concreto (panel del estudiante).
     * @param int $id_estudiante Identificador del estudiante
     * @return array Tutorías del estudiante
     */
    public function obtenerPorEstudiante($id_estudiante)
    {
        $sql = "SELECT tu.*,
                       ut.nombre AS tut_nombre, ut.apellido AS tut_apellido, ut.correo AS tut_correo, ut.foto_perfil AS tut_foto,
                       m.nombre_materia, c.nombre_carrera,
                       ev.calificacion, ev.comentario AS ev_comentario
                FROM tutorias tu
                INNER JOIN tutores t ON tu.id_tutor = t.id_tutor
                INNER JOIN usuarios ut ON t.id_usuario = ut.id_usuario
                INNER JOIN materias m ON tu.id_materia = m.id_materia
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                LEFT JOIN evaluaciones_tutoria ev ON tu.id_tutoria = ev.id_tutoria
                WHERE tu.id_estudiante = :id_est
                ORDER BY tu.fecha DESC, tu.hora_inicio DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_est' => $id_estudiante]);
        return $stmt->fetchAll();
    }

    /**
     * Tutorías asignadas a un tutor en concreto (panel del tutor).
     * @param int $id_tutor Identificador del tutor
     * @return array Tutorías del tutor
     */
    public function obtenerPorTutor($id_tutor)
    {
        $sql = "SELECT tu.*,
                       ue.nombre AS est_nombre, ue.apellido AS est_apellido, ue.correo AS est_correo, ue.foto_perfil AS est_foto,
                       m.nombre_materia, c.nombre_carrera,
                       ev.calificacion, ev.comentario AS ev_comentario
                FROM tutorias tu
                INNER JOIN estudiantes e ON tu.id_estudiante = e.id_estudiante
                INNER JOIN usuarios ue ON e.id_usuario = ue.id_usuario
                INNER JOIN materias m ON tu.id_materia = m.id_materia
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                LEFT JOIN evaluaciones_tutoria ev ON tu.id_tutoria = ev.id_tutoria
                WHERE tu.id_tutor = :id_tutor
                ORDER BY tu.fecha DESC, tu.hora_inicio DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_tutor' => $id_tutor]);
        return $stmt->fetchAll();
    }

    /**
     * Crea una nueva tutoria siempre con estado 'pendiente'.
     * @param array $datos id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin, modalidad, nivel_academico, lugar_o_enlace, observaciones
     * @return bool True si la inserción fue exitosa
     */
    public function crear($datos)
    {
        $sql = "INSERT INTO tutorias (id_estudiante, id_tutor, id_materia, fecha, hora_inicio, hora_fin, modalidad, nivel_academico, lugar_o_enlace, estado, observaciones)
                VALUES (:id_estudiante, :id_tutor, :id_materia, :fecha, :hora_inicio, :hora_fin, :modalidad, :nivel_academico, :lugar_o_enlace, 'pendiente', :observaciones)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_estudiante'   => $datos['id_estudiante'],
            ':id_tutor'        => $datos['id_tutor'],
            ':id_materia'      => $datos['id_materia'],
            ':fecha'           => $datos['fecha'],
            ':hora_inicio'     => $datos['hora_inicio'],
            ':hora_fin'        => $datos['hora_fin'],
            ':modalidad'       => $datos['modalidad'] ?? 'presencial',
            ':nivel_academico' => $datos['nivel_academico'] ?? 'pregrado',
            ':lugar_o_enlace'  => trim($datos['lugar_o_enlace'] ?? ''),
            ':observaciones'   => trim($datos['observaciones'] ?? '')
        ]);
    }

    /**
     * Cambia el estado de la tutoria (y opcionalmente actualiza las observaciones).
     * Estados válidos: pendiente, confirmada, en_proceso, realizada, cancelada.
     * @param int $id_tutoria Identificador de la tutoría
     * @param string $nuevo_estado Nuevo estado
     * @param string|null $observaciones Observaciones a guardar (null = no tocar)
     * @return bool True si la actualización fue exitosa
     */
    public function actualizarEstado($id_tutoria, $nuevo_estado, $observaciones = null)
    {
        if ($observaciones !== null) {
            $sql = "UPDATE tutorias SET estado = :estado, observaciones = :obs WHERE id_tutoria = :id";
            $params = [
                ':estado' => $nuevo_estado,
                ':obs'    => trim($observaciones),
                ':id'     => $id_tutoria
            ];
        } else {
            $sql = "UPDATE tutorias SET estado = :estado WHERE id_tutoria = :id";
            $params = [
                ':estado' => $nuevo_estado,
                ':id'     => $id_tutoria
            ];
        }
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Verifica solapamiento de horario al AGENDAR una tutoria.
     * Evita que un tutor tenga dos sesiones activas a la misma hora.
     * Solo cuentan las tutorías en estado pendiente o confirmada.
     * @param int $id_tutor Identificador del tutor
     * @param string $fecha Fecha de la nueva sesión (Y-m-d)
     * @param string $hora_inicio Hora inicial del nuevo rango
     * @param string $hora_fin Hora final del nuevo rango
     * @return bool True si existe conflicto (no se debe registrar)
     */
    public function existeConflictoHorario($id_tutor, $fecha, $hora_inicio, $hora_fin)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM tutorias
                WHERE id_tutor = :id_tutor
                  AND fecha = :fecha
                  AND estado IN ('pendiente', 'confirmada', 'en_proceso')
                  AND (hora_inicio < :fin AND hora_fin > :inicio)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_tutor' => $id_tutor,
            ':fecha'    => $fecha,
            ':inicio'   => $hora_inicio,
            ':fin'      => $hora_fin
        ]);
        return (int)$stmt->fetch()['total'] > 0;
    }

    /**
     * Verifica solapamiento de horario para el ESTUDIANTE al AGENDAR.
     * Impide que un estudiante tenga dos sesiones activas a la misma hora.
     * @param int $id_estudiante Identificador del estudiante
     * @param string $fecha Fecha de la nueva sesión (Y-m-d)
     * @param string $hora_inicio Hora inicial del nuevo rango
     * @param string $hora_fin Hora final del nuevo rango
     * @return bool True si existe conflicto (no se debe registrar)
     */
    public function existeConflictoHorarioEstudiante($id_estudiante, $fecha, $hora_inicio, $hora_fin)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM tutorias
                WHERE id_estudiante = :id_estudiante
                  AND fecha = :fecha
                  AND estado IN ('pendiente', 'confirmada', 'en_proceso')
                  AND (hora_inicio < :fin AND hora_fin > :inicio)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_estudiante' => $id_estudiante,
            ':fecha'         => $fecha,
            ':inicio'        => $hora_inicio,
            ':fin'           => $hora_fin
        ]);
        return (int)$stmt->fetch()['total'] > 0;
    }

    /**
     * Indica si el estudiante ya tiene una solicitud/tutoría ACTIVA para una
     * misma materia (pendiente, confirmada o en proceso), sin importar el mes.
     * Las canceladas y realizadas no bloquean la inscripción.
     * @param int $id_estudiante Identificador del estudiante
     * @param int $id_materia Identificador de la materia
     * @return bool True si ya existe una solicitud/tutoría activa de esa materia
     */
    public function tieneMateriaActiva($id_estudiante, $id_materia)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM tutorias
                WHERE id_estudiante = :id_estudiante
                  AND id_materia = :id_materia
                  AND estado IN ('pendiente', 'confirmada', 'en_proceso')";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_estudiante' => $id_estudiante,
            ':id_materia'    => $id_materia
        ]);
        return (int)$stmt->fetch()['total'] > 0;
    }

    /**
     * Calcula automáticamente la PRÓXIMA fecha disponible para una sesión,
     * partiendo de hoy (o mañana si el turno ya comenzó) y avanzando día a día.
     * Reutiliza las validaciones de conflicto de horario del tutor y del
     * estudiante para devolver el primer día sin cruces.
     * @param int $id_tutor Identificador del tutor
     * @param int $id_estudiante Identificador del estudiante
     * @param string $hora_inicio Hora inicial del turno (H:i:s o H:i)
     * @param string $hora_fin Hora final del turno (H:i:s o H:i)
     * @param int $maxDias Máximo de días a la fecha para buscar
     * @return string|false Fecha 'Y-m-d' libre de conflictos, o false si no hay
     */
    public function proximaFechaDisponible($id_tutor, $id_estudiante, $hora_inicio, $hora_fin, $maxDias = 180)
    {
        $fecha = date('Y-m-d');
        if ($hora_inicio <= date('H:i:s')) {
            $fecha = date('Y-m-d', strtotime($fecha . ' +1 day'));
        }
        for ($i = 0; $i < $maxDias; $i++) {
            if (!$this->existeConflictoHorario($id_tutor, $fecha, $hora_inicio, $hora_fin)
                && !$this->existeConflictoHorarioEstudiante($id_estudiante, $fecha, $hora_inicio, $hora_fin)) {
                return $fecha;
            }
            $fecha = date('Y-m-d', strtotime($fecha . ' +1 day'));
        }
        return false;
    }

    /**
     * Métricas globales por estado (usadas en el listado de tutorías y dashboard).
     * @return array Fila con total y contadores por estado incluyendo en_proceso
     */
    public function obtenerMetricasGlobales()
    {
        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pendientes,
                    SUM(CASE WHEN estado = 'confirmada' THEN 1 ELSE 0 END) AS confirmadas,
                    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) AS en_proceso,
                    SUM(CASE WHEN estado = 'realizada' THEN 1 ELSE 0 END) AS realizadas,
                    SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) AS canceladas
                FROM tutorias";
        return $this->pdo->query($sql)->fetch();
    }
}