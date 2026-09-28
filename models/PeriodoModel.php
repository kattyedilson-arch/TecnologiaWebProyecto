<?php
// =========================================================
// MODELO: PERIODOS ACADÉMICOS (PeriodoModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'periodos' (HU-044). Un periodo es una
// ventana académica (p. ej. "2026-1") que encuadra las
// Modalidades de Grado: mientras esté 'abierto' se aceptan
// declaraciones, avales y defensas. CRUD básico gestionado
// por administrador y coordinador de MG.
// =========================================================
class PeriodoModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todos los periodos, del más reciente al más antiguo,
     * con la cantidad de días restantes del vigente.
     * @return array Periodos del catálogo
     */
    public function obtenerTodos()
    {
        $sql = "SELECT p.*,
                       DATEDIFF(p.fecha_fin, CURDATE()) AS dias_restantes
                FROM periodos p
                ORDER BY p.fecha_inicio DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Busca un periodo por su identificador.
     * @param int $id_periodo Identificador del periodo
     * @return array|false Fila del periodo o false
     */
    public function obtenerPorId($id_periodo)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM periodos WHERE id_periodo = :id");
        $stmt->execute([':id' => $id_periodo]);
        return $stmt->fetch();
    }

    /**
     * Periodo actualmente abierto (o el más reciente si ninguno lo está).
     * Usado como contexto activo en el flujo de Modalidades de Grado.
     * @return array|false Periodo vigente o false si no hay ninguno
     */
    public function obtenerActivo()
    {
        $sql = "SELECT * FROM periodos
                WHERE estado = 'abierto' AND fecha_fin >= CURDATE()
                ORDER BY fecha_inicio DESC LIMIT 1";
        $periodo = $this->pdo->query($sql)->fetch();
        if ($periodo) {
            return $periodo;
        }
        $sql = "SELECT * FROM periodos ORDER BY fecha_inicio DESC LIMIT 1";
        return $this->pdo->query($sql)->fetch();
    }

    /**
     * Verifica si ya existe un periodo con el mismo nombre.
     * @param string $nombre Nombre del periodo a verificar
     * @param int|null $excluirId Periodo a ignorar (para ediciones)
     * @return bool True si el nombre ya está registrado
     */
    public function existeNombre($nombre, $excluirId = null)
    {
        $sql = "SELECT COUNT(*) AS total FROM periodos WHERE nombre = :nombre";
        $params = [':nombre' => trim($nombre)];
        if ($excluirId !== null) {
            $sql .= " AND id_periodo <> :excluir";
            $params[':excluir'] = $excluirId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetch()['total'] > 0;
    }

    /**
     * Crea un nuevo periodo académico.
     * @param array $datos nombre, fecha_inicio, fecha_fin, estado, descripcion
     * @return int|false Id del periodo insertado o false si falló
     */
    public function crear($datos)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO periodos (nombre, fecha_inicio, fecha_fin, estado, descripcion)
             VALUES (:nombre, :inicio, :fin, :estado, :descripcion)"
        );
        $ok = $stmt->execute([
            ':nombre'     => trim($datos['nombre']),
            ':inicio'     => $datos['fecha_inicio'],
            ':fin'        => $datos['fecha_fin'],
            ':estado'     => $datos['estado'],
            ':descripcion'=> trim($datos['descripcion'] ?? '')
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    /**
     * Actualiza un periodo existente (encuadra el periodo como 'oferta' cuando
     * el administrador lo cierra).
     * @param int $id_periodo Identificador del periodo
     * @param array $datos Campos editables
     * @return bool True si la actualización fue exitosa
     */
    public function actualizar($id_periodo, $datos)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE periodos
             SET nombre = :nombre, fecha_inicio = :inicio, fecha_fin = :fin,
                 estado = :estado, descripcion = :descripcion
             WHERE id_periodo = :id"
        );
        return $stmt->execute([
            ':nombre'     => trim($datos['nombre']),
            ':inicio'     => $datos['fecha_inicio'],
            ':fin'        => $datos['fecha_fin'],
            ':estado'     => $datos['estado'],
            ':descripcion'=> trim($datos['descripcion'] ?? ''),
            ':id'         => $id_periodo
        ]);
    }

    /**
     * Elimina un periodo del catálogo.
     * @param int $id_periodo Identificador del periodo
     * @return bool True si la eliminación fue exitosa
     */
    public function eliminar($id_periodo)
    {
        $stmt = $this->pdo->prepare("DELETE FROM periodos WHERE id_periodo = :id");
        return $stmt->execute([':id' => $id_periodo]);
    }
}