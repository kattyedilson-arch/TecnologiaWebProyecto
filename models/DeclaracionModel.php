<?php
// =========================================================
// MODELO: DECLARACIÓN DE MODALIDAD (DeclaracionModel.php)
// ---------------------------------------------------------
// Acceso a 'declaraciones_modalidad'. El estudiante declara
// su modalidad de grado en el periodo activo (máximo una por
// periodo) y el equipo MG la revisa siguiendo el flujo:
// borrador -> enviada -> en_revision -> aprobada | rechazada
// =========================================================
class DeclaracionModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Declaraciones de un estudiante con detalles de la modalidad y periodo.
     * @param int $id_estudiante Ficha del estudiante
     * @return array Declaraciones de la más reciente a la más antigua
     */
    public function obtenerPorEstudiante($id_estudiante)
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, m.codigo AS modalidad_codigo, m.nombre AS modalidad_nombre,
                    m.tipo AS modalidad_tipo, p.nombre AS periodo_nombre
             FROM declaraciones_modalidad d
             INNER JOIN modalidades_catalogo m ON d.id_modalidad = m.id_modalidad
             INNER JOIN periodos p ON d.id_periodo = p.id_periodo
             WHERE d.id_estudiante = :id
             ORDER BY d.fecha_creacion DESC"
        );
        $stmt->execute([':id' => $id_estudiante]);
        return $stmt->fetchAll();
    }

    /**
     * Una declaración completa (con estudiante, modalidad y periodo).
     * @param int $id_declaracion Identificador de la declaración
     * @return array|false Fila completa o false
     */
    public function obtenerPorId($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.*, e.id_usuario, u.nombre, u.apellido, u.correo, c.nombre_carrera,
                    m.codigo AS modalidad_codigo, m.nombre AS modalidad_nombre, m.tipo AS modalidad_tipo,
                    p.nombre AS periodo_nombre
             FROM declaraciones_modalidad d
             INNER JOIN estudiantes e ON d.id_estudiante = e.id_estudiante
             INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
             LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
             INNER JOIN modalidades_catalogo m ON d.id_modalidad = m.id_modalidad
             INNER JOIN periodos p ON d.id_periodo = p.id_periodo
             WHERE d.id_declaracion = :id"
        );
        $stmt->execute([':id' => $id_declaracion]);
        return $stmt->fetch();
    }

    /**
     * Declaraciones para la gestión MG con filtro por estado y periodo.
     * @param string $estado Filtro de estado (vacío = todos)
     * @param int|null $id_periodo Filtro de periodo (null = todos)
     * @return array Declaraciones de la más reciente a la más antigua
     */
    public function obtenerLista($estado = '', $id_periodo = null)
    {
        $sql = "SELECT d.*, e.id_usuario, u.nombre, u.apellido, u.correo,
                       c.nombre_carrera,
                       m.codigo AS modalidad_codigo, m.nombre AS modalidad_nombre, m.tipo AS modalidad_tipo,
                       p.nombre AS periodo_nombre
                FROM declaraciones_modalidad d
                INNER JOIN estudiantes e ON d.id_estudiante = e.id_estudiante
                INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
                INNER JOIN modalidades_catalogo m ON d.id_modalidad = m.id_modalidad
                INNER JOIN periodos p ON d.id_periodo = p.id_periodo
                WHERE 1 = 1";
        $params = [];
        if ($estado !== '') {
            $sql .= " AND d.estado = :estado";
            $params[':estado'] = $estado;
        }
        if ($id_periodo !== null) {
            $sql .= " AND d.id_periodo = :periodo";
            $params[':periodo'] = $id_periodo;
        }
        $sql .= " ORDER BY d.fecha_enviada DESC, d.fecha_creacion DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Conteos por estado en un periodo (tarjetas del panel MG).
     * @param int $id_periodo Periodo a analizar
     * @return array Totales por estado
     */
    public function contarPorEstado($id_periodo)
    {
        $stmt = $this->pdo->prepare(
            "SELECT estado, COUNT(*) AS total
             FROM declaraciones_modalidad
             WHERE id_periodo = :periodo
             GROUP BY estado"
        );
        $stmt->execute([':periodo' => $id_periodo]);
        $filas = $stmt->fetchAll();
        $conteo = ['borrador' => 0, 'enviada' => 0, 'en_revision' => 0, 'aprobada' => 0, 'rechazada' => 0, 'cancelada' => 0];
        foreach ($filas as $fila) {
            $conteo[$fila['estado']] = (int)$fila['total'];
        }
        $conteo['total'] = array_sum($conteo);
        return $conteo;
    }

    /**
     * Crea una declaración (borrador) para el estudiante en el periodo.
     * @param int $id_estudiante Ficha del estudiante
     * @param int $id_modalidad Modalidad elegida
     * @param int $id_periodo Periodo activo
     * @return int|false Id insertado o false
     */
    public function crear($id_estudiante, $id_modalidad, $id_periodo)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO declaraciones_modalidad (id_estudiante, id_modalidad, id_periodo, estado)
             VALUES (:estudiante, :modalidad, :periodo, 'borrador')"
        );
        $ok = $stmt->execute([
            ':estudiante' => $id_estudiante,
            ':modalidad'  => $id_modalidad,
            ':periodo'    => $id_periodo,
        ]);
        return $ok ? (int)$this->pdo->lastInsertId() : false;
    }

    /**
     * El estudiante actualiza los datos de su declaración.
     * @param int $id_declaracion Identificador de la declaración
     * @param array $datos Campos editables del formulario
     * @return bool True si la actualización fue exitosa
     */
    public function actualizarDatos($id_declaracion, $datos)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE declaraciones_modalidad
             SET id_modalidad = :modalidad, titulo_proyecto = :titulo,
                 empresa_org = :empresa, tutor_facultativo = :tutor
             WHERE id_declaracion = :id"
        );
        return $stmt->execute([
            ':modalidad' => $datos['id_modalidad'],
            ':titulo'    => trim($datos['titulo_proyecto'] ?? '') ?: null,
            ':empresa'   => trim($datos['empresa_org'] ?? '') ?: null,
            ':tutor'     => trim($datos['tutor_facultativo'] ?? '') ?: null,
            ':id'        => $id_declaracion,
        ]);
    }

    /**
     * El estudiante envía su borrador a revisión (borrador/enviada/rechazada/cancelada).
     * @param int $id_declaracion Identificador de la declaración
     * @return bool True si se actualizó
     */
    public function enviar($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE declaraciones_modalidad
             SET estado = 'enviada', observacion = NULL,
                 fecha_enviada = NOW(), fecha_revision = NULL
             WHERE id_declaracion = :id"
        );
        return $stmt->execute([':id' => $id_declaracion]);
    }

    /**
     * El estudiante cancela su declaración (vuelve a quedar disponible).
     * @param int $id_declaracion Identificador de la declaración
     * @return bool True si se actualizó
     */
    public function cancelar($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE declaraciones_modalidad
             SET estado = 'cancelada', fecha_revision = NOW()
             WHERE id_declaracion = :id"
        );
        return $stmt->execute([':id' => $id_declaracion]);
    }

    /**
     * El equipo MG marca la declaración en revisión.
     * @param int $id_declaracion Identificador de la declaración
     * @return bool True si se actualizó
     */
    public function marcarEnRevision($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE declaraciones_modalidad
             SET estado = 'en_revision', fecha_revision = NOW()
             WHERE id_declaracion = :id"
        );
        return $stmt->execute([':id' => $id_declaracion]);
    }

    /**
     * El coordinador aprueba o rechaza la declaración (con observación opcional).
     * @param int $id_declaracion Identificador de la declaración
     * @param string $estado aprobada|rechazada
     * @param string|null $observacion Motivo u observación
     * @return bool True si se actualizó
     */
    public function resolver($id_declaracion, $estado, $observacion)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE declaraciones_modalidad
             SET estado = :estado, observacion = :observacion,
                 fecha_revision = NOW()
             WHERE id_declaracion = :id"
        );
        return $stmt->execute([
            ':estado'       => $estado,
            ':observacion'  => trim($observacion ?? '') ?: null,
            ':id'           => $id_declaracion,
        ]);
    }
}