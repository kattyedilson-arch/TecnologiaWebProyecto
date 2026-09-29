<?php
// =========================================================
// MODELO: COHORTES DE MODALIDADES DE GRADO (CohorteMgModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'cohortes_mg' (HU-022). Una cohorte agrupa
// a los estudiantes que cursan la modalidad dentro de un
// periodo académico (p. ej. "Cohorte 2026-1" sobre "2026-1").
// =========================================================
class CohorteMgModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista las cohortes con el nombre del periodo al que pertenecen.
     * @return array Cohortes, de la más reciente a la más antigua
     */
    public function obtenerTodas()
    {
        return $this->pdo->query(
            "SELECT c.*, p.nombre AS periodo_nombre
             FROM cohortes_mg c
             LEFT JOIN periodos p ON c.id_periodo = p.id_periodo
             ORDER BY c.fecha_inicio DESC, c.codigo DESC"
        )->fetchAll();
    }

    /**
     * Solo cohortes vigentes (contexto operativo MG).
     * @return array Cohortes en estado 'vigente'
     */
    public function obtenerVigentes()
    {
        return $this->pdo->query(
            "SELECT c.*, p.nombre AS periodo_nombre
             FROM cohortes_mg c
             LEFT JOIN periodos p ON c.id_periodo = p.id_periodo
             WHERE c.estado = 'vigente'
             ORDER BY c.fecha_inicio DESC"
        )->fetchAll();
    }

    /**
     * Busca una cohorte por su identificador.
     * @param int $id_cohorte Identificador
     * @return array|false Fila o false
     */
    public function obtenerPorId($id_cohorte)
    {
        $stmt = $this->pdo->prepare(
            "SELECT c.*, p.nombre AS periodo_nombre
             FROM cohortes_mg c
             LEFT JOIN periodos p ON c.id_periodo = p.id_periodo
             WHERE c.id_cohorte = :id"
        );
        $stmt->execute([':id' => $id_cohorte]);
        return $stmt->fetch();
    }

    /**
     * Verifica si ya existe una cohorte con el mismo código o nombre.
     * @param string $codigo Código corto
     * @param string $nombre Nombre de la cohorte
     * @param int|null $excluirId Cohorte a ignorar (ediciones)
     * @return bool True si ya existe
     */
    public function existe($codigo, $nombre, $excluirId = null)
    {
        $sql = "SELECT COUNT(*) FROM cohortes_mg WHERE codigo = :codigo OR nombre = :nombre";
        $params = [':codigo' => trim($codigo), ':nombre' => trim($nombre)];
        if ($excluirId !== null) {
            $sql .= " AND id_cohorte <> :excluir";
            $params[':excluir'] = $excluirId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Crea una cohorte nueva.
     * @param array $datos Campos de la cohorte
     * @return int|false Id insertado o false
     */
    public function crear($datos)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO cohortes_mg (codigo, nombre, id_periodo, fecha_inicio, fecha_fin, estado)
             VALUES (:codigo, :nombre, :periodo, :inicio, :fin, :estado)"
        );
        $ok = $stmt->execute([
            ':codigo'  => strtoupper(trim($datos['codigo'])),
            ':nombre'  => trim($datos['nombre']),
            ':periodo' => !empty($datos['id_periodo']) ? (int)$datos['id_periodo'] : null,
            ':inicio'  => $datos['fecha_inicio'],
            ':fin'     => $datos['fecha_fin'],
            ':estado'  => $datos['estado'],
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    /**
     * Actualiza una cohorte existente.
     * @param int $id_cohorte Identificador
     * @param array $datos Campos editables
     * @return bool True si se actualizó
     */
    public function actualizar($id_cohorte, $datos)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE cohortes_mg
             SET codigo = :codigo, nombre = :nombre, id_periodo = :periodo,
                 fecha_inicio = :inicio, fecha_fin = :fin, estado = :estado
             WHERE id_cohorte = :id"
        );
        return $stmt->execute([
            ':codigo'  => strtoupper(trim($datos['codigo'])),
            ':nombre'  => trim($datos['nombre']),
            ':periodo' => !empty($datos['id_periodo']) ? (int)$datos['id_periodo'] : null,
            ':inicio'  => $datos['fecha_inicio'],
            ':fin'     => $datos['fecha_fin'],
            ':estado'  => $datos['estado'],
            ':id'      => $id_cohorte,
        ]);
    }

    /**
     * Elimina una cohorte (sus eventos de calendario se borran en cascada).
     * @param int $id_cohorte Identificador
     * @return bool True si se eliminó
     */
    public function eliminar($id_cohorte)
    {
        $stmt = $this->pdo->prepare("DELETE FROM cohortes_mg WHERE id_cohorte = :id");
        return $stmt->execute([':id' => $id_cohorte]);
    }
}