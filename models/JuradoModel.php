<?php
// =========================================================
// MODELO: JURADO DE DECLARACIÓN (JuradoModel.php)
// ---------------------------------------------------------
// Acceso a 'jurados_declaracion'. El coordinador asigna el
// tribunal de docentes que evaluará la defensa de cada
// declaración aprobada (presidente, titulares y suplente).
// =========================================================
require_once __DIR__ . '/ParametroModel.php';

class JuradoModel
{
    /** Mismo umbral de aprobación que ActaModel::APROBADO_MIN (para recalcular). */
    const APROBADO_MIN = 51;

    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Nota mínima configurable para aprobar (parametros_mg: APROBADO_MIN).
     * @return float Umbral de aprobación
     */
    private function aprobadoMin()
    {
        $valor = (new ParametroModel($this->pdo))->obtener('APROBADO_MIN', null);
        return $valor !== null ? (float)$valor : (float)self::APROBADO_MIN;
    }

    /**
     * Miembros del jurado de una declaración.
     * @param int $id_declaracion Identificador de la declaración
     * @return array Miembros con datos del docente
     */
    public function obtenerPorDeclaracion($id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "SELECT j.*, u.nombre, u.apellido, u.correo, u.foto_perfil,
                    tu.especialidad
             FROM jurados_declaracion j
             INNER JOIN usuarios u ON j.id_usuario = u.id_usuario
             LEFT JOIN tutores tu ON tu.id_usuario = u.id_usuario
             WHERE j.id_declaracion = :id
             ORDER BY FIELD(j.rol_jurado, 'presidente', 'titular', 'suplente'), u.nombre ASC"
        );
        $stmt->execute([':id' => $id_declaracion]);
        return $stmt->fetchAll();
    }

    /**
     * Reemplaza el jurado completo de una declaración por la nueva lista.
     * Conserva la integridad del acta de calificación:
     *   1. Si el acta ya está FIRMADA, se impide cualquier cambio de tribunal.
     *   2. Al reemplazar, las notas por jurado ya registradas se conservan
     *      (se reinsertan con el nuevo id_jurado del mismo docente); si un
     *      docente sale del tribunal, su nota se retira y se recalcula la
     *      nota final del acta (si está abierta).
     * @param int $id_declaracion Identificador de la declaración
     * @param array $miembros Lista de ['id_usuario' => int, 'rol_jurado' => string]
     * @return true
     * @throws RuntimeException Si el acta está firmada
     */
    public function asignar($id_declaracion, array $miembros)
    {
        // Un acta firmada es definitiva: su tribunal no puede alterarse
        $stmtActa = $this->pdo->prepare(
            "SELECT id_acta, estado, nota_final FROM actas_calificacion
             WHERE id_declaracion = :id LIMIT 1"
        );
        $stmtActa->execute([':id' => $id_declaracion]);
        $acta = $stmtActa->fetch();

        if ($acta && $acta['estado'] === 'firmada') {
            throw new RuntimeException('El acta ya está firmada; no se puede modificar el tribunal.');
        }

        // Notas actuales por jurado (se reinsertarán con el nuevo id_jurado).
        // Se capturan ANTES del DELETE porque la FK fk_acta_jurado_jurado
        // usa ON DELETE CASCADE y borraría las filas de notas.
        $notasViejas = [];
        if ($acta) {
            $stmtNotasViejas = $this->pdo->prepare(
                "SELECT nc.id_jurado, nc.nota, nc.comentario, j.id_usuario
                 FROM acta_calificaciones_jurado nc
                 INNER JOIN jurados_declaracion j ON nc.id_jurado = j.id_jurado
                 WHERE j.id_declaracion = :id"
            );
            $stmtNotasViejas->execute([':id' => $id_declaracion]);
            $notasViejas = $stmtNotasViejas->fetchAll();
        }

        $this->pdo->beginTransaction();
        try {
            $this->pdo->prepare("DELETE FROM jurados_declaracion WHERE id_declaracion = :id")
                ->execute([':id' => $id_declaracion]);

            $stmt = $this->pdo->prepare(
                "INSERT INTO jurados_declaracion (id_declaracion, id_usuario, rol_jurado)
                 VALUES (:id, :usuario, :rol)"
            );
            $nuevosIds = [];
            foreach ($miembros as $miembro) {
                $stmt->execute([
                    ':id'      => $id_declaracion,
                    ':usuario' => (int)$miembro['id_usuario'],
                    ':rol'     => $miembro['rol_jurado'],
                ]);
                $nuevosIds[(int)$miembro['id_usuario']] = (int)$this->pdo->lastInsertId();
            }

            // Reinserta las notas de los docentes que permanecen en el tribunal;
            // el que sale del tribunal pierde su calificación.
            if ($acta && $notasViejas) {
                $stmtNotaIns = $this->pdo->prepare(
                    "INSERT INTO acta_calificaciones_jurado (id_acta, id_jurado, nota, comentario)
                     VALUES (:acta, :jurado, :nota, :comentario)"
                );
                foreach ($notasViejas as $nv) {
                    $nuevoId = $nuevosIds[(int)$nv['id_usuario']] ?? null;
                    if ($nuevoId === null) {
                        continue;
                    }
                    $stmtNotaIns->execute([
                        ':acta'      => (int)$acta['id_acta'],
                        ':jurado'    => $nuevoId,
                        ':nota'      => $nv['nota'],
                        ':comentario'=> $nv['comentario'],
                    ]);
                }
            }

            // Si el acta está abierta y tenía notas, el reemplazo pudo cambiar
            // el promedio: se recalcula la nota final y el resultado.
            if ($acta && $acta['estado'] === 'abierta' && $notasViejas) {
                $stmtProm = $this->pdo->prepare(
                    "SELECT ROUND(AVG(nota), 2) FROM acta_calificaciones_jurado WHERE id_acta = :acta"
                );
                $stmtProm->execute([':acta' => (int)$acta['id_acta']]);
                $notaFinal = $stmtProm->fetchColumn();
                $notaFinal = $notaFinal === null ? null : (float)$notaFinal;
                $resultado = $notaFinal === null ? 'pendiente' : ($notaFinal >= $this->aprobadoMin() ? 'aprobado' : 'reprobado');

                $this->pdo->prepare(
                    "UPDATE actas_calificacion SET nota_final = :nf, resultado = :r WHERE id_acta = :acta"
                )->execute([
                    ':nf'   => $notaFinal,
                    ':r'    => $resultado,
                    ':acta' => (int)$acta['id_acta'],
                ]);
            }

            $this->pdo->commit();
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return true;
    }

    /**
     * Elimina un solo miembro del jurado.
     * @param int $id_jurado Identificador de la fila
     * @param int $id_declaracion Seguridad: pertenencia
     * @return bool True si se eliminó
     */
    public function eliminarMiembro($id_jurado, $id_declaracion)
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM jurados_declaracion WHERE id_jurado = :id AND id_declaracion = :declaracion"
        );
        return $stmt->execute([':id' => $id_jurado, ':declaracion' => $id_declaracion]);
    }

    /**
     * Defensas asignadas a un docente (donde es miembro del jurado).
     * Incluye datos del estudiante, modalidad, avales y el estado del
     * acta de calificación para mostrar la bandeja del docente.
     * @param int $id_usuario Docente en sesión
     * @return array Defensas del de la menos avanzada a la más avanzada
     */
    public function obtenerPorDocente($id_usuario)
    {
        $stmt = $this->pdo->prepare(
            "SELECT d.id_declaracion, d.titulo_proyecto, d.fecha_revision,
                    j.rol_jurado, j.fecha_asignacion,
                    e.id_usuario AS id_estudiante_usuario, u.nombre, u.apellido,
                    c.nombre_carrera,
                    m.codigo AS modalidad_codigo, m.nombre AS modalidad_nombre,
                    p.nombre AS periodo_nombre,
                    ac.estado AS acta_estado, ac.nota_final, ac.resultado,
                    ac.fecha_defensa, ac.lugar,
                    (SELECT COUNT(*) FROM avales_declaracion a WHERE a.id_declaracion = d.id_declaracion AND a.estado = 'entregado') AS avales_entregados,
                    (SELECT COUNT(*) FROM avales_declaracion a WHERE a.id_declaracion = d.id_declaracion) AS avales_total
             FROM jurados_declaracion j
             INNER JOIN declaraciones_modalidad d ON j.id_declaracion = d.id_declaracion
             INNER JOIN estudiantes e ON d.id_estudiante = e.id_estudiante
             INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
             LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
             INNER JOIN modalidades_catalogo m ON d.id_modalidad = m.id_modalidad
             INNER JOIN periodos p ON d.id_periodo = p.id_periodo
             LEFT JOIN actas_calificacion ac ON ac.id_declaracion = d.id_declaracion
             WHERE j.id_usuario = :id
             ORDER BY (ac.estado = 'firmada') ASC, d.fecha_revision DESC"
        );
        $stmt->execute([':id' => $id_usuario]);
        return $stmt->fetchAll();
    }

    /**
     * Docentes (usuarios con rol tutor y activos) candidatos a jurado.
     * @return array Docentes ordenados alfabéticamente
     */
    public function obtenerDocentes()
    {
        return $this->pdo->query(
            "SELECT u.id_usuario, u.nombre, u.apellido, u.correo, tu.especialidad
             FROM usuarios u
             INNER JOIN roles r ON u.id_rol = r.id_rol
             LEFT JOIN tutores tu ON tu.id_usuario = u.id_usuario
             WHERE r.nombre_rol = 'tutor' AND u.estado = 'activo'
             ORDER BY u.nombre ASC"
        )->fetchAll();
    }

    /**
     * Estado del jurado de cada declaración aprobada (para el listado MG).
     * @return array Declaraciones aprobadas con conteo de jurado y avales
     */
    public function obtenerResumenAprobadas()
    {
        return $this->pdo->query(
            "SELECT d.*, e.id_usuario, u.nombre, u.apellido, c.nombre_carrera,
                    m.nombre AS modalidad_nombre, m.codigo AS modalidad_codigo,
                    p.nombre AS periodo_nombre,
                    (SELECT COUNT(*) FROM jurados_declaracion j WHERE j.id_declaracion = d.id_declaracion) AS total_jurado,
                    (SELECT COUNT(*) FROM avales_declaracion a WHERE a.id_declaracion = d.id_declaracion AND a.estado = 'entregado') AS avales_entregados,
                    (SELECT COUNT(*) FROM avales_declaracion a WHERE a.id_declaracion = d.id_declaracion) AS avales_total,
                    (SELECT a2.estado FROM actas_calificacion a2 WHERE a2.id_declaracion = d.id_declaracion) AS acta_estado,
                    (SELECT a3.nota_final FROM actas_calificacion a3 WHERE a3.id_declaracion = d.id_declaracion) AS acta_nota
             FROM declaraciones_modalidad d
             INNER JOIN estudiantes e ON d.id_estudiante = e.id_estudiante
             INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
             LEFT JOIN carreras c ON e.id_carrera = c.id_carrera
             INNER JOIN modalidades_catalogo m ON d.id_modalidad = m.id_modalidad
             INNER JOIN periodos p ON d.id_periodo = p.id_periodo
             WHERE d.estado = 'aprobada'
             ORDER BY d.fecha_revision DESC"
        )->fetchAll();
    }
}