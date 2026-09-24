<?php
// =========================================================
// MODELO: TUTORES (TutorModel.php)
// ---------------------------------------------------------
// Acceso a las tablas 'tutores', 'tutor_materia' y
// 'disponibilidad_tutor'. Un tutor extiende un usuario con
// una especialidad, biografía, las materias que imparte y sus
// bloques de horarios semanales para atender tutorías.
// =========================================================
class TutorModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todos los tutores con sus estadísticas (materias y horarios asignados).
     * @return array Tutores ordenados alfabéticamente por nombre
     */
    public function obtenerTodos()
    {
        $sql = "SELECT t.id_tutor, t.id_usuario, t.especialidad, t.biografia,
                       u.nombre, u.apellido, u.correo, u.telefono, u.usuario, u.estado, u.foto_perfil,
                       (SELECT COUNT(*) FROM tutor_materia tm WHERE tm.id_tutor = t.id_tutor) AS total_materias,
                       (SELECT COUNT(*) FROM disponibilidad_tutor dt WHERE dt.id_tutor = t.id_tutor) AS total_horarios
                FROM tutores t
                INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                ORDER BY u.nombre ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Reseñas (evaluaciones) recibidas por TODOS los tutores en una sola
     * consulta (evita el patrón N+1 al listar tutores). Cada fila incluye
     * el id_tutor para agruparlas en PHP.
     * @return array Reseñas de todos los tutores (incluye id_tutor)
     */
    public function obtenerResenasDeTodos()
    {
        $sql = "SELECT tu.id_tutor,
                       ev.calificacion, ev.comentario, ev.fecha_evaluacion,
                       m.nombre_materia,
                       ue.nombre AS est_nombre, ue.apellido AS est_apellido
                FROM evaluaciones_tutoria ev
                INNER JOIN tutorias tu ON ev.id_tutoria = tu.id_tutoria
                INNER JOIN estudiantes e ON tu.id_estudiante = e.id_estudiante
                INNER JOIN usuarios ue ON e.id_usuario = ue.id_usuario
                INNER JOIN materias m ON tu.id_materia = m.id_materia
                ORDER BY ev.fecha_evaluacion DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Busca un tutor por su identificador (incluye datos del usuario).
     * @param int $id_tutor Identificador del tutor
     * @return array|false Fila del tutor o false
     */
    public function obtenerPorId($id_tutor)
    {
        $sql = "SELECT t.*, u.nombre, u.apellido, u.correo, u.telefono, u.usuario, u.estado
                FROM tutores t
                INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                WHERE t.id_tutor = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id_tutor]);
        return $stmt->fetch();
    }

    /**
     * Busca el perfil de tutor de un usuario concreto (usado en su panel).
     * @param int $id_usuario Identificador del usuario en sesión
     * @return array|false Fila del tutor o false
     */
    public function obtenerPorUsuario($id_usuario)
    {
        $sql = "SELECT t.*, u.nombre, u.apellido, u.correo, u.telefono
                FROM tutores t
                INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                WHERE t.id_usuario = :id_usuario";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_usuario' => $id_usuario]);
        return $stmt->fetch();
    }

    /**
     * Actualiza la especialidad y biografía del perfil docente.
     * @param int $id_tutor Identificador del tutor
     * @param string $especialidad Especialidad profesional
     * @param string $biografia Presentación o experiencia
     * @return bool True si la actualización fue exitosa
     */
    public function actualizarPerfil($id_tutor, $especialidad, $biografia)
    {
        $stmt = $this->pdo->prepare("UPDATE tutores SET especialidad = :esp, biografia = :bio WHERE id_tutor = :id");
        return $stmt->execute([
            ':esp' => trim($especialidad),
            ':bio' => trim($biografia),
            ':id'  => $id_tutor
        ]);
    }

    /**
     * Materias que domina/imparte un tutor (tabla intermedia tutor_materia).
     * @param int $id_tutor Identificador del tutor
     * @return array Materias del tutor con su carrera
     */
    public function obtenerMaterias($id_tutor)
    {
        $sql = "SELECT m.id_materia, m.nombre_materia, c.nombre_carrera
                FROM materias m
                INNER JOIN tutor_materia tm ON m.id_materia = tm.id_materia
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                WHERE tm.id_tutor = :id_tutor
                ORDER BY m.nombre_materia ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_tutor' => $id_tutor]);
        return $stmt->fetchAll();
    }

    /**
     * Reemplaza TODAS las materias asignadas a un tutor por la lista dada.
     * Borra las anteriores y registra las nuevas.
     * @param int $id_tutor Identificador del tutor
     * @param array $materias_ids Lista de ids de materias seleccionadas
     * @return bool True siempre (el DELETE se usa para no dejar huérfanas)
     */
    public function asignarMaterias($id_tutor, $materias_ids = [])
    {
        $this->pdo->prepare("DELETE FROM tutor_materia WHERE id_tutor = :id_tutor")->execute([':id_tutor' => $id_tutor]);
        if (!empty($materias_ids)) {
            $stmt = $this->pdo->prepare("INSERT INTO tutor_materia (id_tutor, id_materia) VALUES (:id_tutor, :id_materia)");
            foreach ($materias_ids as $id_materia) {
                $stmt->execute([
                    ':id_tutor'   => $id_tutor,
                    ':id_materia' => $id_materia
                ]);
            }
        }
        return true;
    }

    /**
     * Tutores ACTIVOS que imparten una materia específica (para agendar tutorías).
     * @param int $id_materia Identificador de la materia
     * @return array Tutores hábiles para esa materia
     */
    public function obtenerTutoresPorMateria($id_materia)
    {
        $sql = "SELECT t.id_tutor, u.nombre, u.apellido, t.especialidad
                FROM tutores t
                INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                INNER JOIN tutor_materia tm ON t.id_tutor = tm.id_tutor
                WHERE tm.id_materia = :id_materia AND u.estado = 'activo'
                ORDER BY u.nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_materia' => $id_materia]);
        return $stmt->fetchAll();
    }

    /**
     * Bloques de disponibilidad del tutor (materia + turno), en orden de hora.
     * @param int $id_tutor Identificador del tutor
     * @return array Horarios [id_materia, nombre_materia, id_turno, nombre_turno, hora_inicio, hora_fin, ...]
     */
    public function obtenerDisponibilidad($id_tutor)
    {
        $sql = "SELECT dt.*, m.nombre_materia, t.nombre_turno, t.hora_inicio AS turno_hora_inicio, t.hora_fin AS turno_hora_fin
                FROM disponibilidad_tutor dt
                INNER JOIN materias m ON dt.id_materia = m.id_materia
                INNER JOIN turnos t ON dt.id_turno = t.id_turno
                WHERE dt.id_tutor = :id_tutor
                ORDER BY t.hora_inicio ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_tutor' => $id_tutor]);
        return $stmt->fetchAll();
    }

    /**
     * Agrega un nuevo bloque de disponibilidad para el tutor, indicando
     * la materia y el turno fijo asignado.
     * @param int $id_tutor Identificador del tutor
     * @param int $id_turno Identificador del turno fijo
     * @param int $id_materia Materia que se impartirá en el turno
     * @return bool True si la inserción fue exitosa
     */
    public function agregarDisponibilidad($id_tutor, $id_turno, $id_materia)
    {
        $stmt = $this->pdo->prepare("INSERT INTO disponibilidad_tutor (id_tutor, id_materia, id_turno) 
                                     VALUES (:id_tutor, :id_materia, :id_turno)");
        return $stmt->execute([
            ':id_tutor'   => $id_tutor,
            ':id_materia' => $id_materia,
            ':id_turno'   => $id_turno
        ]);
    }

    /**
     * Elimina un bloque horario, verificando que pertenezca a este tutor.
     * @param int $id_disp Identificador del bloque
     * @param int $id_tutor Identificador del tutor (seguridad)
     * @return bool True si se eliminó
     */
    public function eliminarDisponibilidad($id_disp, $id_tutor)
    {
        $stmt = $this->pdo->prepare("DELETE FROM disponibilidad_tutor WHERE id_disponibilidad = :id AND id_tutor = :id_tutor");
        return $stmt->execute([
            ':id'       => $id_disp,
            ':id_tutor' => $id_tutor
        ]);
    }

    /**
     * Verifica si el tutor ya tiene asignado un turno para esa materia.
     * Un tutor no puede tener el mismo turno dos veces para la misma materia.
     * @param int $id_tutor Identificador del tutor
     * @param int $id_turno Identificador del turno
     * @param int $id_materia Identificador de la materia
     * @return bool True si existe conflicto (no se debe guardar)
     */
    public function existeConflictoDisponibilidad($id_tutor, $id_turno, $id_materia)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM disponibilidad_tutor
                WHERE id_tutor = :id_tutor
                  AND id_turno = :id_turno
                  AND id_materia = :id_materia";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_tutor'   => $id_tutor,
            ':id_turno'   => $id_turno,
            ':id_materia' => $id_materia
        ]);
        return (int)$stmt->fetch()['total'] > 0;
    }
}