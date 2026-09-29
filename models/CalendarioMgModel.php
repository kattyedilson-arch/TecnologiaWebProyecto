<?php
// =========================================================
// MODELO: CALENDARIO DE MODALIDADES DE GRADO (CalendarioMgModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'calendario_mg' (HU-022). Hitos del proceso
// (talleres, informes, defensas, entregas) que pueden estar
// asociados a una cohorte o ser globales a todo el módulo.
// =========================================================
class CalendarioMgModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista los eventos del calendario con la cohorte asociada.
     * @return array Eventos, del más próximo al más lejano
     */
    public function obtenerTodos()
    {
        return $this->pdo->query(
            "SELECT ev.*, c.codigo AS cohorte_codigo, c.nombre AS cohorte_nombre
             FROM calendario_mg ev
             LEFT JOIN cohortes_mg c ON ev.id_cohorte = c.id_cohorte
             ORDER BY ev.fecha ASC, ev.id_evento ASC"
        )->fetchAll();
    }

    /**
     * Lista los eventos asociados a una cohorte concreta.
     * @param int $id_cohorte Identificador de la cohorte
     * @return array Eventos de la cohorte
     */
    public function obtenerPorCohorte($id_cohorte)
    {
        $stmt = $this->pdo->prepare(
            "SELECT ev.*, c.codigo AS cohorte_codigo, c.nombre AS cohorte_nombre
             FROM calendario_mg ev
             LEFT JOIN cohortes_mg c ON ev.id_cohorte = c.id_cohorte
             WHERE ev.id_cohorte = :cohorte
             ORDER BY ev.fecha ASC"
        );
        $stmt->execute([':cohorte' => $id_cohorte]);
        return $stmt->fetchAll();
    }

    /**
     * Busca un evento por su identificador.
     * @param int $id_evento Identificador
     * @return array|false Fila o false
     */
    public function obtenerPorId($id_evento)
    {
        $stmt = $this->pdo->prepare(
            "SELECT ev.*, c.codigo AS cohorte_codigo, c.nombre AS cohorte_nombre
             FROM calendario_mg ev
             LEFT JOIN cohortes_mg c ON ev.id_cohorte = c.id_cohorte
             WHERE ev.id_evento = :id"
        );
        $stmt->execute([':id' => $id_evento]);
        return $stmt->fetch();
    }

    /**
     * Crea un evento del calendario.
     * @param array $datos Campos del evento
     * @return int|false Id insertado o false
     */
    public function crear($datos)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO calendario_mg (id_cohorte, titulo, tipo_hito, fecha, descripcion)
             VALUES (:cohorte, :titulo, :tipo, :fecha, :descripcion)"
        );
        $ok = $stmt->execute([
            ':cohorte'    => !empty($datos['id_cohorte']) ? (int)$datos['id_cohorte'] : null,
            ':titulo'     => trim($datos['titulo']),
            ':tipo'       => $datos['tipo_hito'],
            ':fecha'      => $datos['fecha'],
            ':descripcion'=> trim($datos['descripcion'] ?? '') ?: null,
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    /**
     * Actualiza un evento existente.
     * @param int $id_evento Identificador
     * @param array $datos Campos editables
     * @return bool True si se actualizó
     */
    public function actualizar($id_evento, $datos)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE calendario_mg
             SET id_cohorte = :cohorte, titulo = :titulo, tipo_hito = :tipo,
                 fecha = :fecha, descripcion = :descripcion
             WHERE id_evento = :id"
        );
        return $stmt->execute([
            ':cohorte'    => !empty($datos['id_cohorte']) ? (int)$datos['id_cohorte'] : null,
            ':titulo'     => trim($datos['titulo']),
            ':tipo'       => $datos['tipo_hito'],
            ':fecha'      => $datos['fecha'],
            ':descripcion'=> trim($datos['descripcion'] ?? '') ?: null,
            ':id'         => $id_evento,
        ]);
    }

    /**
     * Elimina un evento del calendario.
     * @param int $id_evento Identificador
     * @return bool True si se eliminó
     */
    public function eliminar($id_evento)
    {
        $stmt = $this->pdo->prepare("DELETE FROM calendario_mg WHERE id_evento = :id");
        return $stmt->execute([':id' => $id_evento]);
    }
}