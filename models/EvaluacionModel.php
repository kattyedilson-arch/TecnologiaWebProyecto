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
                AS nuevo
                ON DUPLICATE KEY UPDATE calificacion = nuevo.calificacion, comentario = nuevo.comentario";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([
            ':id_tutoria' => $id_tutoria,
            ':calif'      => (int)$calificacion,
            ':coment'     => trim($comentario)
        ]);
    }
}