<?php
// =========================================================
// MODELO: CARRERAS (CarreraModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'carreras' de la base de datos.
// Una carrera agrupa materias y a los estudiantes inscritos.
// =========================================================
class CarreraModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todas las carreras con el conteo de materias y estudiantes de cada una.
     * @return array Lista de carreras con estadísticas
     */
    public function obtenerTodas()
    {
        $sql = "SELECT c.id_carrera, c.nombre_carrera,
                       (SELECT COUNT(*) FROM materias m WHERE m.id_carrera = c.id_carrera) AS total_materias,
                       (SELECT COUNT(*) FROM estudiantes e WHERE e.id_carrera = c.id_carrera) AS total_estudiantes
                FROM carreras c
                ORDER BY c.nombre_carrera ASC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Busca una carrera por su identificador.
     * @param int $id Identificador de la carrera
     * @return array|false Fila de la carrera o false si no existe
     */
    public function obtenerPorId($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM carreras WHERE id_carrera = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Verifica si ya existe una carrera con el mismo nombre (ignora mayúsculas/minúsculas).
     * @param string $nombre Nombre a comprobar
     * @param int|null $exceptoId ID a excluir al editar (evita un falso duplicado)
     * @return bool True si el nombre ya está en uso
     */
    public function existeNombre($nombre, $exceptoId = null)
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM carreras WHERE nombre_carrera = :nombre AND id_carrera != :excepto");
        $stmt->execute([':nombre' => trim($nombre), ':excepto' => (int)$exceptoId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Registra una nueva carrera.
     * @param string $nombre Nombre de la carrera
     * @return bool True si la inserción fue exitosa
     */
    public function crear($nombre)
    {
        $stmt = $this->pdo->prepare("INSERT INTO carreras (nombre_carrera) VALUES (:nombre)");
        return $stmt->execute([':nombre' => trim($nombre)]);
    }

    /**
     * Actualiza el nombre de una carrera existente.
     * @param int $id Identificador de la carrera
     * @param string $nombre Nuevo nombre
     * @return bool True si la actualización fue exitosa
     */
    public function actualizar($id, $nombre)
    {
        $stmt = $this->pdo->prepare("UPDATE carreras SET nombre_carrera = :nombre WHERE id_carrera = :id");
        return $stmt->execute([
            ':nombre' => trim($nombre),
            ':id'     => $id
        ]);
    }

    /**
     * Elimina una carrera (fallará si tiene materias/estudiantes vinculados por FK).
     * @param int $id Identificador de la carrera
     * @return bool True si la eliminación fue exitosa (de lo contrario lanza PDOException)
     */
    public function eliminar($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM carreras WHERE id_carrera = :id");
        return $stmt->execute([':id' => $id]);
    }
}