<?php
// =========================================================
// MODELO: REPORTES MG (ReporteModel.php)
// ---------------------------------------------------------
// Estadísticas agregadas del módulo de modalidades de grado
// para el panel de reportes del equipo MG. Todas las consultas
// aceptan un periodo opcional (null = todos los periodos).
// =========================================================
class ReporteModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Resumen general del flujo de declaraciones de modalidad.
     * @param int|null $id_periodo Periodo a filtrar (null = todos)
     * @return array Conteos y métricas agregadas
     */
    public function resumen($id_periodo = null)
    {
        $resumen = [];
        $filtro = $this->filtroPeriodo($id_periodo);

        // 1) Declaraciones por estado (flujo de revisión)
        $estados = ['borrador', 'enviada', 'en_revision', 'aprobada', 'rechazada', 'cancelada'];
        $conteo = array_fill_keys($estados, 0);
        $stmt = $this->pdo->prepare(
            "SELECT d.estado, COUNT(*) AS total
             FROM declaraciones_modalidad d
             WHERE 1 = 1" . $filtro['sql'] . "
             GROUP BY d.estado"
        );
        $stmt->execute($filtro['params']);
        foreach ($stmt->fetchAll() as $fila) {
            $conteo[$fila['estado']] = (int)$fila['total'];
        }
        $conteo['total'] = array_sum($conteo);
        $resumen['conteo_estado'] = $conteo;

        // 2) Estudiantes que declararon (distintos)
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(DISTINCT d.id_estudiante) AS total
             FROM declaraciones_modalidad d
             WHERE 1 = 1" . $filtro['sql']
        );
        $stmt->execute($filtro['params']);
        $resumen['estudiantes_con_declaracion'] = (int)$stmt->fetchColumn();

        // 3) Aprobadas que aún no tienen tribunal
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total
             FROM declaraciones_modalidad d
             WHERE d.estado = 'aprobada' AND NOT EXISTS (
                 SELECT 1 FROM jurados_declaracion j WHERE j.id_declaracion = d.id_declaracion
             )" . $filtro['sql']
        );
        $stmt->execute($filtro['params']);
        $resumen['aprobadas_sin_jurado'] = (int)$stmt->fetchColumn();

        // 4) Aprobadas que aún no tienen acta
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total
             FROM declaraciones_modalidad d
             WHERE d.estado = 'aprobada' AND NOT EXISTS (
                 SELECT 1 FROM actas_calificacion ac WHERE ac.id_declaracion = d.id_declaracion AND ac.estado = 'firmada'
             )" . $filtro['sql']
        );
        $stmt->execute($filtro['params']);
        $resumen['aprobadas_sin_acta'] = (int)$stmt->fetchColumn();

        // 5) Avance de avales (pendientes/entregados/observados)
        $stmt = $this->pdo->prepare(
            "SELECT
                 COUNT(*) AS total,
                 COALESCE(SUM(a.estado = 'pendiente'), 0) AS pendientes,
                 COALESCE(SUM(a.estado = 'entregado'), 0) AS entregados,
                 COALESCE(SUM(a.estado = 'observado'), 0) AS observados
             FROM avales_declaracion a
             INNER JOIN declaraciones_modalidad d ON a.id_declaracion = d.id_declaracion
             WHERE d.estado = 'aprobada'" . $filtro['sql']
        );
        $stmt->execute($filtro['params']);
        $resumen['avales'] = $stmt->fetch();

        // 6) Acta: emitidas, abiertas y nota promedio
        $stmt = $this->pdo->prepare(
            "SELECT
                 COALESCE(SUM(ac.estado = 'firmada'), 0)  AS firmadas,
                 COALESCE(SUM(ac.estado = 'abierta'), 0)  AS abiertas,
                 ROUND(AVG(CASE WHEN ac.nota_final IS NOT NULL THEN ac.nota_final END), 2) AS nota_promedio,
                 COALESCE(SUM(ac.resultado = 'aprobado'), 0)  AS aprobados,
                 COALESCE(SUM(ac.resultado = 'reprobado'), 0) AS reprobados
             FROM actas_calificacion ac
             INNER JOIN declaraciones_modalidad d ON ac.id_declaracion = d.id_declaracion
             WHERE 1 = 1" . $filtro['sql']
        );
        $stmt->execute($filtro['params']);
        $resumen['acta'] = $stmt->fetch();

        return $resumen;
    }

    /**
     * Modalidades más elegidas por los estudiantes.
     * @param int|null $id_periodo Periodo a filtrar (null = todos)
     * @param int $limite Cantidad de filas
     * @return array Código, nombre y cantidad
     */
    public function topModalidades($id_periodo = null, $limite = 6)
    {
        $filtro = $this->filtroPeriodo($id_periodo);
        $stmt = $this->pdo->prepare(
            "SELECT m.codigo, m.nombre, COUNT(*) AS total
             FROM declaraciones_modalidad d
             INNER JOIN modalidades_catalogo m ON d.id_modalidad = m.id_modalidad
             WHERE 1 = 1" . $filtro['sql'] . "
             GROUP BY m.id_modalidad, m.codigo, m.nombre
             ORDER BY total DESC, m.nombre ASC
             LIMIT " . (int)$limite
        );
        $stmt->execute($filtro['params']);
        return $stmt->fetchAll();
    }

    /**
     * Aprobadas sin tribunal (para priorizar gestión).
     * @param int|null $id_periodo Periodo a filtrar (null = todos)
     * @param int $limite Cantidad de filas
     * @return array Declaraciones aprobadas sin jurado
     */
    public function aprobadasSinJuradoLista($id_periodo = null, $limite = 8)
    {
        $filtro = $this->filtroPeriodo($id_periodo);
        $stmt = $this->pdo->prepare(
            "SELECT d.id_declaracion, u.nombre, u.apellido, c.nombre_carrera,
                    m.codigo AS modalidad_codigo, m.nombre AS modalidad_nombre,
                    (SELECT COUNT(*) FROM avales_declaracion a WHERE a.id_declaracion = d.id_declaracion AND a.estado = 'entregado') AS avales_entregados,
                    (SELECT COUNT(*) FROM avales_declaracion a WHERE a.id_declaracion = d.id_declaracion) AS avales_total
             FROM declaraciones_modalidad d
             INNER JOIN estudiantes e ON d.id_estudiante = e.id_estudiante
             INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
             LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
             INNER JOIN modalidades_catalogo m ON d.id_modalidad = m.id_modalidad
             WHERE d.estado = 'aprobada' AND NOT EXISTS (
                 SELECT 1 FROM jurados_declaracion j WHERE j.id_declaracion = d.id_declaracion
             )" . $filtro['sql'] . "
             ORDER BY d.fecha_revision DESC
             LIMIT " . (int)$limite
        );
        $stmt->execute($filtro['params']);
        return $stmt->fetchAll();
    }

    /**
     * Actividad reciente del flujo MG (últimas declaraciones tocadas).
     * @param int|null $id_periodo Periodo a filtrar (null = todos)
     * @param int $limite Cantidad de filas
     * @return array Declaraciones recientes
     */
    public function actividadReciente($id_periodo = null, $limite = 6)
    {
        $filtro = $this->filtroPeriodo($id_periodo);
        $stmt = $this->pdo->prepare(
            "SELECT d.id_declaracion, d.estado, d.fecha_revision, d.fecha_creacion,
                    u.nombre, u.apellido, m.codigo AS modalidad_codigo, m.nombre AS modalidad_nombre,
                    p.nombre AS periodo_nombre
             FROM declaraciones_modalidad d
             INNER JOIN estudiantes e ON d.id_estudiante = e.id_estudiante
             INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
             INNER JOIN modalidades_catalogo m ON d.id_modalidad = m.id_modalidad
             INNER JOIN periodos p ON d.id_periodo = p.id_periodo
             WHERE 1 = 1" . $filtro['sql'] . "
             ORDER BY GREATEST(COALESCE(d.fecha_revision, '1970-01-01'), d.fecha_creacion) DESC
             LIMIT " . (int)$limite
        );
        $stmt->execute($filtro['params']);
        return $stmt->fetchAll();
    }

    /**
     * Construye la cláusula y parámetros de filtro por periodo.
     * @param int|null $id_periodo Periodo o null
     * @return array ['sql' => string, 'params' => array]
     */
    private function filtroPeriodo($id_periodo)
    {
        if (!$id_periodo) {
            return ['sql' => '', 'params' => []];
        }
        return ['sql' => ' AND d.id_periodo = :id_periodo', 'params' => [':id_periodo' => (int)$id_periodo]];
    }
}