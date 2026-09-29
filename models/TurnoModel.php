<?php
// =========================================================
// MODELO: TURNOS (TurnoModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'turnos'. Los turnos son fijos y están
// predefinidos por el administrador del sistema.
// =========================================================
class TurnoModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Retorna todos los turnos fijos ordenados por hora de inicio.
     * @return array Turnos [{id_turno, nombre_turno, hora_inicio, hora_fin}]
     */
    public function obtenerTodos()
    {
        return $this->pdo->query("SELECT * FROM turnos ORDER BY hora_inicio ASC")->fetchAll();
    }

    /**
     * Busca un turno por su identificador.
     * @param int $id_turno Identificador del turno
     * @return array|false Fila del turno o false
     */
    public function obtenerPorId($id_turno)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM turnos WHERE id_turno = :id");
        $stmt->execute([':id' => $id_turno]);
        return $stmt->fetch();
    }
}
