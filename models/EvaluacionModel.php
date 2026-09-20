<?php
// =========================================================
// MODELO: EVALUACIONES DE TUTORÍA (EvaluacionModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'evaluaciones_tutoria'.
// Cuando una tutoría se marca como 'realizada', el estudiante
// puede calificar la sesión (1 a 5 estrellas) y dejar un comentario.
// =========================================================
class EvaluacionModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Obtiene la evaluación de una tutoría concreta (si ya fue evaluada).
     * @param int $id_tutoria Identificador de la tutoría
     * @return array|false Evaluación existente o false
     */
    public function obtenerPorTutoria($id_tutoria)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM evaluaciones_tutoria WHERE id_tutoria = :id");
        $stmt->execute([':id' => $id_tutoria]);
        return $stmt->fetch();
    }

    /**
     * Registra o actualiza la evaluación de una tutoría.
     * Usa ON DUPLICATE KEY: cada tutoría solo puede tener UNA
     * evaluación (columna id_tutoria con índice único).
     * @param int $id_tutoria Identificador de la tutoría
     * @param int $calificacion Nota entre 1 y 5
     * @param string $comentario Comentario opcional del estudiante
     * @return bool True si el registro fue exitoso
     */
    public function registrar($id_tutoria, $calificacion, $comentario = '')
    {
        $sql = "INSERT INTO evaluaciones_tutoria (id_tutoria, calificacion, comentario)
                VALUES (:id_tutoria, :calif, :coment)
                ON DUPLICATE KEY UPDATE calificacion = VALUES(calificacion), comentario = VALUES(comentario)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_tutoria' => $id_tutoria,
            ':calif'      => (int)$calificacion,
            ':coment'     => trim($comentario)
        ]);
    }

    /**
     * Promedio de calificaciones recibidas por un tutor (usado en el dashboard).
     * @param int $id_tutor Identificador del tutor
     * @return array Fila con 'promedio' (ROUND/AVG) y 'total_evaluaciones'
     */
    public function obtenerPromedioPorTutor($id_tutor)
    {
        $sql = "SELECT AVG(ev.calificacion) AS promedio, COUNT(ev.id_evaluacion) AS total_evaluaciones
                FROM evaluaciones_tutoria ev
                INNER JOIN tutorias tu ON ev.id_tutoria = tu.id_tutoria
                WHERE tu.id_tutor = :id_tutor";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_tutor' => $id_tutor]);
        return $stmt->fetch();
    }
}