<?php
// =========================================================
// MODELO: DASHBOARD (DashboardModel.php)
// ---------------------------------------------------------
// Consultas de lectura para el panel de control del
// ADMINISTRADOR: resumen global del sistema, últimas tutorías,
// ranking de tutores y materias más tutoradas.
// =========================================================
class DashboardModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Resumen general de todo el sistema en un solo SELECT con subconsultas.
     * Devuelve: totales de usuarios, estudiantes, tutores, materias, carreras,
     * tutorías por estado, y el promedio/ total de evaluaciones.
     * @return array Fila única con todos los indicadores
     */
    public function obtenerResumenGlobal()
    {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM usuarios) AS total_usuarios,
                    (SELECT COUNT(*) FROM usuarios WHERE estado = 'activo') AS usuarios_activos,
                    (SELECT COUNT(*) FROM estudiantes) AS total_estudiantes,
                    (SELECT COUNT(*) FROM tutores) AS total_tutores,
                    (SELECT COUNT(*) FROM materias) AS total_materias,
                    (SELECT COUNT(*) FROM carreras) AS total_carreras,
                    (SELECT COUNT(*) FROM tutorias) AS total_tutorias,
                    (SELECT COUNT(*) FROM tutorias WHERE estado = 'pendiente') AS pendientes,
                    (SELECT COUNT(*) FROM tutorias WHERE estado = 'confirmada') AS confirmadas,
                    (SELECT COUNT(*) FROM tutorias WHERE estado = 'en_proceso') AS en_proceso,
                    (SELECT COUNT(*) FROM tutorias WHERE estado = 'realizada') AS realizadas,
                    (SELECT COUNT(*) FROM tutorias WHERE estado = 'cancelada') AS canceladas,
                    (SELECT ROUND(AVG(calificacion), 2) FROM evaluaciones_tutoria) AS promedio_evaluaciones,
                    (SELECT COUNT(*) FROM evaluaciones_tutoria) AS total_evaluaciones";
        return $this->pdo->query($sql)->fetch();
    }

    /**
     * Últimas tutorías registradas (para la tabla "Actividad reciente").
     * @param int $limite Cantidad máxima de filas (6 por defecto)
     * @return array Tutorías recientes con estudiante, tutor, materia y calificación
     */
    public function obtenerUltimasTutorias($limite = 6)
    {
        $sql = "SELECT tu.*,
                       ue.nombre AS est_nombre, ue.apellido AS est_apellido,
                       ut.nombre AS tut_nombre, ut.apellido AS tut_apellido,
                       m.nombre_materia, ev.calificacion,
                       tu.nivel_academico
                FROM tutorias tu
                INNER JOIN estudiantes e ON tu.id_estudiante = e.id_estudiante
                INNER JOIN usuarios ue ON e.id_usuario = ue.id_usuario
                INNER JOIN tutores t ON tu.id_tutor = t.id_tutor
                INNER JOIN usuarios ut ON t.id_usuario = ut.id_usuario
                INNER JOIN materias m ON tu.id_materia = m.id_materia
                LEFT JOIN evaluaciones_tutoria ev ON tu.id_tutoria = ev.id_tutoria
                ORDER BY tu.fecha_solicitud DESC, tu.id_tutoria DESC
                LIMIT :limite";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Ranking de los tutores MEJOR evaluados (promedio de estrellas recibidas).
     * @param int $limite Cantidad máxima de tutores (5 por defecto)
     * @return array Tutores con su promedio, nº de evaluaciones y nº de tutorías
     */
    public function obtenerTopTutores($limite = 5)
    {
        $sql = "SELECT t.id_tutor, u.nombre, u.apellido, t.especialidad,
                       ROUND(AVG(ev.calificacion), 2) AS promedio,
                       COUNT(ev.id_evaluacion) AS total_evaluaciones,
                       (SELECT COUNT(*) FROM tutorias tu2 WHERE tu2.id_tutor = t.id_tutor) AS total_tutorias
                FROM tutores t
                INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
                LEFT JOIN tutorias tu ON tu.id_tutor = t.id_tutor
                LEFT JOIN evaluaciones_tutoria ev ON ev.id_tutoria = tu.id_tutoria
                GROUP BY t.id_tutor, u.nombre, u.apellido, t.especialidad
                HAVING AVG(ev.calificacion) IS NOT NULL
                ORDER BY promedio DESC, total_evaluaciones DESC
                LIMIT :limite";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Materias más solicitadas para tutoría (top del catálogo).
     * @param int $limite Cantidad máxima de materias (5 por defecto)
     * @return array Materias con su contador de tutorías
     */
    public function obtenerMateriasTop($limite = 5)
    {
        $sql = "SELECT m.id_materia, m.nombre_materia,
                       COUNT(tu.id_tutoria) AS total_tutorias
                FROM materias m
                LEFT JOIN tutorias tu ON tu.id_materia = m.id_materia
                GROUP BY m.id_materia, m.nombre_materia
                ORDER BY total_tutorias DESC
                LIMIT :limite";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Desglose de tutorías por nivel académico (pregrado, posgrado, invierno, verano).
     * @return array Filas con nivel_academico y total
     */
    public function obtenerDesgloseNivelAcademico()
    {
        $sql = "SELECT nivel_academico, COUNT(*) AS total
                FROM tutorias
                GROUP BY nivel_academico
                ORDER BY total DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Resumen de ofertas creadas por el administrador.
     * @return array Fila con contadores por estado
     */
    public function obtenerResumenOfertas()
    {
        $sql = "SELECT
                    (SELECT COUNT(*) FROM ofertas_admin WHERE estado = 'abierta') AS activas,
                    (SELECT COUNT(*) FROM ofertas_admin WHERE estado = 'cerrada') AS cerradas,
                    (SELECT COUNT(*) FROM oferta_respuesta WHERE estado = 'aceptada') AS aceptadas,
                    (SELECT COUNT(*) FROM ofertas_admin o
                     WHERE o.estado = 'abierta'
                       AND NOT EXISTS (
                         SELECT 1 FROM oferta_respuesta r WHERE r.id_oferta = o.id_oferta
                       )) AS pendientes_respuesta";
        return $this->pdo->query($sql)->fetch();
    }

    /**
     * Resumen de disponibilidad de tutores por turno.
     * Muestra cuántos tutores tienen disponibilidad en cada turno.
     * @return array Filas con datos del turno y conteo de tutores
     */
    public function obtenerResumenTurnos()
    {
        $sql = "SELECT t.id_turno, t.nombre_turno, t.hora_inicio, t.hora_fin,
                       COUNT(DISTINCT dt.id_tutor) AS total_tutores
                FROM turnos t
                LEFT JOIN disponibilidad_tutor dt ON t.id_turno = dt.id_turno
                GROUP BY t.id_turno, t.nombre_turno, t.hora_inicio, t.hora_fin
                ORDER BY t.hora_inicio ASC";
        return $this->pdo->query($sql)->fetchAll();
    }
}