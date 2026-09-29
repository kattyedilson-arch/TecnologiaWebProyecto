<?php
// =========================================================
// MODELO: CATÁLOGO DE MODALIDADES DE GRADO (ModalidadModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'modalidades_catalogo' (HU-021). El
// catálogo define QUÉ modalidades puede declarar un estudiante
// (proyecto de grado, tesis, trabajo dirigido...). Solo está
// visible para estudiantes mientras tenga estado 'publicada'.
// =========================================================
class ModalidadModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todas las modalidades del catálogo (gestión).
     * @return array Modalidades ordenadas por nombre
     */
    public function obtenerTodas()
    {
        return $this->pdo->query(
            "SELECT * FROM modalidades_catalogo
             ORDER BY nombre ASC"
        )->fetchAll();
    }

    /**
     * Solo modalidades publicadas (las que puede declarar el estudiante).
     * @return array Modalidades visibles en el portal
     */
    public function obtenerPublicadas()
    {
        return $this->pdo->query(
            "SELECT * FROM modalidades_catalogo
             WHERE estado = 'publicada'
             ORDER BY nombre ASC"
        )->fetchAll();
    }

    /**
     * Busca una modalidad por su identificador.
     * @param int $id_modalidad Identificador de la modalidad
     * @return array|false Fila de la modalidad o false
     */
    public function obtenerPorId($id_modalidad)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM modalidades_catalogo WHERE id_modalidad = :id");
        $stmt->execute([':id' => $id_modalidad]);
        return $stmt->fetch();
    }

    /**
     * Verifica si el código o el nombre ya están en uso.
     * @param string $codigo Código corto
     * @param string $nombre Nombre de la modalidad
     * @param int|null $excluirId Modalidad a ignorar (para ediciones)
     * @return bool True si ya existe la modalidad
     */
    public function existe($codigo, $nombre, $excluirId = null)
    {
        // Solo el código es único; el nombre puede repetirse (mismo nombre, objetivos distintos)
        $sql = "SELECT COUNT(*) AS total
                FROM modalidades_catalogo
                WHERE codigo = :codigo";
        $params = [':codigo' => trim($codigo)];
        if ($excluirId !== null) {
            $sql .= " AND id_modalidad <> :excluir";
            $params[':excluir'] = $excluirId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetch()['total'] > 0;
    }

    /**
     * Crea una modalidad en el catálogo.
     * @param array $datos Campos de la modalidad
     * @return int|false Id insertado o false
     */
    public function crear($datos)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO modalidades_catalogo
                (codigo, nombre, descripcion, tipo, requisitos, estado)
             VALUES (:codigo, :nombre, :descripcion, :tipo, :requisitos, :estado)"
        );
        $ok = $stmt->execute([
            ':codigo'      => strtoupper(trim($datos['codigo'])),
            ':nombre'      => trim($datos['nombre']),
            ':descripcion' => trim($datos['descripcion'] ?? ''),
            ':tipo'        => $datos['tipo'],
            ':requisitos'  => trim($datos['requisitos'] ?? ''),
            ':estado'      => $datos['estado'],
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    /**
     * Actualiza una modalidad existente.
     * @param int $id_modalidad Identificador de la modalidad
     * @param array $datos Campos editables
     * @return bool True si la actualización fue exitosa (al menos 1 fila afectada)
     * @throws PDOException Si el ID no existe (0 filas afectadas)
     */
    public function actualizar($id_modalidad, $datos)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE modalidades_catalogo
             SET codigo = :codigo, nombre = :nombre, descripcion = :descripcion,
                 tipo = :tipo, requisitos = :requisitos, estado = :estado
             WHERE id_modalidad = :id"
        );
        $ok = $stmt->execute([
            ':codigo'      => strtoupper(trim($datos['codigo'])),
            ':nombre'      => trim($datos['nombre']),
            ':descripcion' => trim($datos['descripcion'] ?? ''),
            ':tipo'        => $datos['tipo'],
            ':requisitos'  => trim($datos['requisitos'] ?? ''),
            ':estado'      => $datos['estado'],
            ':id'          => $id_modalidad,
        ]);
        if ($ok && $stmt->rowCount() === 0) {
            throw new PDOException("No existe modalidad con id_modalidad = $id_modalidad");
        }
        return $ok;
    }

    /**
     * Elimina una modalidad del catálogo (fallará si hay declaraciones asociadas).
     * @param int $id_modalidad Identificador de la modalidad
     * @return bool True si se eliminó
     */
    public function eliminar($id_modalidad)
    {
        $stmt = $this->pdo->prepare("DELETE FROM modalidades_catalogo WHERE id_modalidad = :id");
        return $stmt->execute([':id' => $id_modalidad]);
    }
}