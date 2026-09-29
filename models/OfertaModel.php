<?php
// =========================================================
// MODELO: OFERTAS (OfertaModel.php)
// ---------------------------------------------------------
// Gestiona las ofertas de materias creadas por el administrador
// y las respuestas de los tutores (aceptar/rechazar).
// =========================================================
class OfertaModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todas las ofertas con info de materia, turno y tutor asignado.
     * @param string|null $filtro_estado Filtrar por estado (abierta/asignada/cerrada)
     * @return array Ofertas
     */
    public function obtenerTodas($filtro_estado = null)
    {
        $sql = "SELECT o.*, m.nombre_materia, t.nombre_turno,
                       t.hora_inicio AS turno_hora_inicio, t.hora_fin AS turno_hora_fin,
                       tu.id_tutor, u.nombre AS tut_nombre, u.apellido AS tut_apellido,
                       (SELECT COUNT(*) FROM tutorias t2
                         WHERE t2.id_oferta = o.id_oferta
                           AND t2.estado IN ('pendiente', 'confirmada', 'en_proceso')) AS inscritos,
                       (SELECT MIN(s3.fecha) FROM (
                            SELECT t3.fecha, COUNT(*) AS n
                            FROM tutorias t3
                            WHERE t3.id_oferta = o.id_oferta
                              AND t3.estado IN ('pendiente', 'confirmada', 'en_proceso')
                              AND t3.fecha >= CURDATE()
                            GROUP BY t3.fecha
                            HAVING n < o.cupo
                        ) s3) AS fecha_sesion,
                       (SELECT COUNT(*) FROM solicitudes_interes si
                         WHERE si.id_oferta = o.id_oferta
                           AND si.estado = 'pendiente') AS interesados
                FROM ofertas_admin o
                INNER JOIN materias m ON o.id_materia = m.id_materia
                INNER JOIN turnos t ON o.id_turno = t.id_turno
                LEFT JOIN tutores tu ON o.id_tutor_assigned = tu.id_tutor
                LEFT JOIN usuarios u ON tu.id_usuario = u.id_usuario";

        $params = [];
        if (!empty($filtro_estado)) {
            $sql .= " WHERE o.estado = :estado";
            $params[':estado'] = $filtro_estado;
        }

        $sql .= " ORDER BY FIELD(o.estado, 'abierta', 'asignada', 'cerrada'), m.nombre_materia ASC, t.hora_inicio ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Total de ofertas por estado en una sola consulta agregada
     * (evita traer listas completas solo para contarlas).
     * @return array Mapa estado => cantidad (abierta/asignada/cerrada)
     */
    public function contarPorEstado()
    {
        $totales = ['abierta' => 0, 'asignada' => 0, 'cerrada' => 0];
        $filas = $this->pdo->query("SELECT estado, COUNT(*) AS total FROM ofertas_admin GROUP BY estado")->fetchAll();
        foreach ($filas as $fila) {
            $totales[$fila['estado']] = (int)$fila['total'];
        }
        return $totales;
    }

    /**
     * Ofertas abiertas que un tutor AÚN NO ha respondido.
     * @param int $id_tutor Identificador del tutor
     * @return array Ofertas disponibles para el tutor
     */
    public function obtenerAbiertasParaTutor($id_tutor)
    {
        $sql = "SELECT o.*, m.nombre_materia, t.nombre_turno, t.hora_inicio AS turno_hora_inicio, t.hora_fin AS turno_hora_fin
                FROM ofertas_admin o
                INNER JOIN materias m ON o.id_materia = m.id_materia
                INNER JOIN turnos t ON o.id_turno = t.id_turno
                WHERE o.estado = 'abierta'
                  AND NOT EXISTS (
                    SELECT 1 FROM oferta_respuesta r
                    WHERE r.id_oferta = o.id_oferta AND r.id_tutor = :id_tutor
                  )
ORDER BY m.nombre_materia ASC, t.hora_inicio ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_tutor' => $id_tutor]);
        return $stmt->fetchAll();
    }

    /**
     * Respuestas de un tutor (aceptadas y rechazadas).
     * @param int $id_tutor Identificador del tutor
     * @return array Respuestas con info de oferta, materia y turno
     */
    public function obtenerRespuestasTutor($id_tutor)
    {
        $sql = "SELECT r.*, o.estado AS oferta_estado,
                       m.nombre_materia,
                       t.nombre_turno, t.hora_inicio AS turno_hora_inicio, t.hora_fin AS turno_hora_fin
                FROM oferta_respuesta r
                INNER JOIN ofertas_admin o ON r.id_oferta = o.id_oferta
                INNER JOIN materias m ON o.id_materia = m.id_materia
                INNER JOIN turnos t ON o.id_turno = t.id_turno
                WHERE r.id_tutor = :id_tutor
                ORDER BY FIELD(r.estado, 'aceptada', 'rechazada'), m.nombre_materia ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_tutor' => $id_tutor]);
        return $stmt->fetchAll();
    }

    /**
     * Busca una oferta por ID con info relacionada.
     * @param int $id_oferta Identificador de la oferta
     * @return array|false Fila de la oferta o false
     */
    public function obtenerPorId($id_oferta)
    {
        $sql = "SELECT o.*, m.nombre_materia, t.nombre_turno
                FROM ofertas_admin o
                INNER JOIN materias m ON o.id_materia = m.id_materia
                INNER JOIN turnos t ON o.id_turno = t.id_turno
                WHERE o.id_oferta = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id_oferta]);
        return $stmt->fetch();
    }

    /**
     * Crea una nueva oferta (solo admin).
     * @param int $id_materia Identificador de la materia
     * @param string $nivel_academico Nivel académico (pregrado, invierno, verano o texto libre)
     * @param int $id_turno Identificador del turno
     * @param string $modalidad Modalidad (presencial, virtual)
     * @param string|null $lugar_o_enlace Aula o enlace de la sesión
     * @param int $cupo Estudiantes que caben en la misma sesión (1 = individual)
     * @return bool True si la inserción fue exitosa
     */
    public function crear($id_materia, $nivel_academico, $id_turno, $modalidad = 'presencial', $lugar_o_enlace = null, $cupo = 1)
    {
        $stmt = $this->pdo->prepare("INSERT INTO ofertas_admin (id_materia, nivel_academico, modalidad, lugar_o_enlace, id_turno, cupo) VALUES (:id_materia, :nivel, :modalidad, :lugar, :id_turno, :cupo)");
        return $stmt->execute([
            ':id_materia' => $id_materia,
            ':nivel'      => $nivel_academico,
            ':modalidad'  => $modalidad,
            ':lugar'      => trim($lugar_o_enlace ?? ''),
            ':id_turno'   => $id_turno,
            ':cupo'       => max(1, (int)$cupo)
        ]);
    }

    /**
     * Cupo de una oferta (número de estudiantes por sesión).
     * @param int $id_oferta Identificador de la oferta
     * @return int Cupo configurado; 1 si la oferta no existe
     */
    public function obtenerCupo($id_oferta)
    {
        $stmt = $this->pdo->prepare("SELECT cupo FROM ofertas_admin WHERE id_oferta = :id");
        $stmt->execute([':id' => (int)$id_oferta]);
        $row = $stmt->fetch();
        return $row ? max(1, (int)$row['cupo']) : 1;
    }

    /**
     * Indica si la oferta tiene alguna sesión en la que todavía
     * quede cupo libre. Si es falso, el estudiante debe esperar al
     * día siguiente (o la siguiente sesión) para sumarse al grupo.
     * @param int $id_oferta Identificador de la oferta
     * @param int $cupo Cupo de la oferta
     * @return bool True si queda algún cupo libre en fechas futuras
     */
    public function tieneSesionConCupo($id_oferta, $cupo)
    {
        $stmt = $this->pdo->prepare("SELECT fecha, hora_inicio, COUNT(*) AS inscritos
                FROM tutorias
                WHERE id_oferta = :id
                  AND estado IN ('pendiente', 'confirmada', 'en_proceso')
                  AND fecha >= CURDATE()
                GROUP BY fecha, hora_inicio
                HAVING inscritos < :cupo
                LIMIT 1");
        $stmt->execute([':id' => (int)$id_oferta, ':cupo' => (int)$cupo]);
        return (bool)$stmt->fetch();
    }

    /**
     * Ofertas disponibles para que el ESTUDIANTE solicite una tutoría.
     * Solo ofertas ya ASIGNADAS a un tutor (tienen turno, modalidad
     * y aula preestablecidos por el administrador). El estudiante solo
     * elige la materia y un horario publicado.
     * @param int|null $id_materia Si se pasa, filtra por esa materia
     * @return array Ofertas asignadas con datos del turno y del tutor
     */
    public function obtenerOfertasEstudiante($id_materia = null)
    {
        $sql = "SELECT o.*, m.nombre_materia, c.nombre_carrera,
                       t.nombre_turno, t.hora_inicio AS turno_hora_inicio, t.hora_fin AS turno_hora_fin,
                       u.nombre AS tut_nombre, u.apellido AS tut_apellido,
                       (SELECT COUNT(*) FROM tutorias t2
                         WHERE t2.id_oferta = o.id_oferta
                           AND t2.estado IN ('pendiente', 'confirmada', 'en_proceso')) AS inscritos,
                       (SELECT MIN(s3.fecha) FROM (
                            SELECT t3.fecha, COUNT(*) AS n
                            FROM tutorias t3
                            WHERE t3.id_oferta = o.id_oferta
                              AND t3.estado IN ('pendiente', 'confirmada', 'en_proceso')
                              AND t3.fecha >= CURDATE()
                            GROUP BY t3.fecha
                            HAVING n < o.cupo
                        ) s3) AS fecha_sesion
                FROM ofertas_admin o
                INNER JOIN materias m ON o.id_materia = m.id_materia
                LEFT JOIN carreras c ON m.id_carrera = c.id_carrera
                INNER JOIN turnos t ON o.id_turno = t.id_turno
                INNER JOIN tutores tu ON o.id_tutor_assigned = tu.id_tutor
                INNER JOIN usuarios u ON tu.id_usuario = u.id_usuario
                WHERE o.estado = 'asignada'
                  AND o.id_tutor_assigned IS NOT NULL";

        $params = [];
        if (!empty($id_materia)) {
            $sql .= " AND o.id_materia = :id_materia";
            $params[':id_materia'] = (int)$id_materia;
        }

        $sql .= " ORDER BY m.nombre_materia ASC, t.hora_inicio ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ofertas que el estudiante puede ver en "Materias Disponibles",
     * de las materias de SU carrera y en dos situaciones distintas:
     *
     *   - estado 'asignada': tiene docente, se puede reservar ya.
     *   - estado 'abierta'  : la oferta existe pero NADIE la ha
     *     aceptado todavía, así que no hay tutor que imparta la
     *     sesión. Se muestra igualmente para que el estudiante
     *     deje registrado su interés.
     *
     * Por eso el join con tutores es LEFT: una oferta sin docente
     * devuelve tut_* a NULL en lugar de desaparecer de la lista.
     *
     * Cada fila trae 'sin_docente' (0/1) y 'mi_solicitud'
     * ('pendiente'|'atendida'|''), que es lo que la vista necesita
     * para pintar el botón "Solicitar" y su estado.
     *
     * @param int $id_carrera Carrera de la ficha del estudiante
     * @param int $id_estudiante Ficha del estudiante, para marcar sus intereses
     * @return array Ofertas ordenadas por materia y hora de inicio
     */
    public function obtenerOfertasVisiblesEstudiante($id_carrera, $id_estudiante)
    {
        $sql = "SELECT o.*, m.nombre_materia, c.nombre_carrera,
                       t.nombre_turno, t.hora_inicio AS turno_hora_inicio, t.hora_fin AS turno_hora_fin,
                       u.nombre AS tut_nombre, u.apellido AS tut_apellido,
                       (o.id_tutor_assigned IS NULL) AS sin_docente,
                       COALESCE(mi.estado, '') AS mi_solicitud,
                       (SELECT COUNT(*) FROM tutorias t2
                         WHERE t2.id_oferta = o.id_oferta
                           AND t2.estado IN ('pendiente', 'confirmada', 'en_proceso')) AS inscritos,
                       (SELECT MIN(s3.fecha) FROM (
                            SELECT t3.fecha, COUNT(*) AS n
                            FROM tutorias t3
                            WHERE t3.id_oferta = o.id_oferta
                              AND t3.estado IN ('pendiente', 'confirmada', 'en_proceso')
                              AND t3.fecha >= CURDATE()
                            GROUP BY t3.fecha
                            HAVING n < o.cupo
                        ) s3) AS fecha_sesion,
                       (SELECT COUNT(*) FROM solicitudes_interes si
                         WHERE si.id_oferta = o.id_oferta
                           AND si.estado = 'pendiente') AS interesados
                FROM ofertas_admin o
                INNER JOIN materias m ON o.id_materia = m.id_materia
                LEFT JOIN carreras c  ON m.id_carrera = c.id_carrera
                INNER JOIN turnos t   ON o.id_turno = t.id_turno
                LEFT JOIN tutores tu  ON o.id_tutor_assigned = tu.id_tutor
                LEFT JOIN usuarios u   ON tu.id_usuario = u.id_usuario
                LEFT JOIN solicitudes_interes mi
                       ON mi.id_oferta = o.id_oferta
                      AND mi.id_estudiante = :id_estudiante
                      AND mi.estado <> 'cancelada'
                WHERE o.estado IN ('abierta', 'asignada')
                  AND m.id_carrera = :id_carrera
                ORDER BY m.nombre_materia ASC, t.hora_inicio ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id_carrera'    => (int)$id_carrera,
            ':id_estudiante' => (int)$id_estudiante
        ]);
        return $stmt->fetchAll();
    }

    /**
     * Cierra una oferta (cambia estado a 'cerrada').
     * @param int $id_oferta Identificador de la oferta
     * @return bool True si la actualización fue exitosa
     */
    public function cerrar($id_oferta)
    {
        $stmt = $this->pdo->prepare("UPDATE ofertas_admin SET estado = 'cerrada' WHERE id_oferta = :id AND estado != 'asignada'");
        $ok = $stmt->execute([':id' => $id_oferta]);
        if ($ok) {
            // Un horario cerrado ya no es reservable: los intereses
            // pendientes quedan obsoletos y se cancelan.
            $this->cancelarInteresesPendientes($id_oferta);
        }
        return $ok;
    }

    /**
     * Elimina una oferta (solo si no tiene respuestas aceptadas).
     * @param int $id_oferta Identificador de la oferta
     * @return bool True si se eliminó
     */
    public function eliminar($id_oferta)
    {
        // fk_interes_oferta es ON DELETE CASCADE, pero se cancelan
        // antes para que el estado quede registrado antes de caer.
        $this->cancelarInteresesPendientes($id_oferta);
        $stmt = $this->pdo->prepare("DELETE FROM ofertas_admin WHERE id_oferta = :id AND estado != 'asignada'");
        return $stmt->execute([':id' => $id_oferta]);
    }

    /**
     * Cancela los intereses pendientes de una oferta. Se hace por SQL
     * directo para que OfertaModel no dependa de SolicitudInteresModel.
     * @param int $id_oferta Oferta retirada del circuito
     * @return void
     */
    private function cancelarInteresesPendientes($id_oferta)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE solicitudes_interes
             SET estado = 'cancelada', fecha_respuesta = CURRENT_TIMESTAMP
             WHERE id_oferta = :id_oferta AND estado = 'pendiente'"
        );
        $stmt->execute([':id_oferta' => (int)$id_oferta]);
    }

    /**
     * Registra la respuesta de un tutor (aceptar o rechazar).
     * Si acepta: asigna el tutor a la oferta y crea disponibilidad_tutor.
     * @param int $id_oferta Identificador de la oferta
     * @param int $id_tutor Identificador del tutor
     * @param string $estado 'aceptada' o 'rechazada'
     * @return bool True si la operación fue exitosa
     */
    public function responder($id_oferta, $id_tutor, $estado)
    {
        $this->pdo->beginTransaction();

        try {
            // 1. Insertar la respuesta
            $stmt = $this->pdo->prepare("INSERT INTO oferta_respuesta (id_oferta, id_tutor, estado) VALUES (:id_oferta, :id_tutor, :estado)");
            $stmt->execute([':id_oferta' => $id_oferta, ':id_tutor' => $id_tutor, ':estado' => $estado]);

            // 2. Si acepta, asignar tutor a la oferta y crear disponibilidad
            if ($estado === 'aceptada') {
                $oferta = $this->obtenerPorId($id_oferta);
                if (!$oferta) throw new Exception("Oferta no encontrada");

                // Asignar tutor a la oferta
                $stmt = $this->pdo->prepare("UPDATE ofertas_admin SET id_tutor_assigned = :id_tutor, estado = 'asignada' WHERE id_oferta = :id AND estado = 'abierta'");
                $stmt->execute([':id_tutor' => $id_tutor, ':id' => $id_oferta]);

                // Verificar que no exista ya esa disponibilidad
                $stmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM disponibilidad_tutor WHERE id_tutor = :id_tutor AND id_materia = :id_materia AND id_turno = :id_turno");
                $stmt->execute([':id_tutor' => $id_tutor, ':id_materia' => $oferta['id_materia'], ':id_turno' => $oferta['id_turno']]);
                $existe = (int)$stmt->fetch()['total'] > 0;

                if (!$existe) {
                    // Insertar en disponibilidad_tutor
                    $stmt = $this->pdo->prepare("INSERT INTO disponibilidad_tutor (id_tutor, id_materia, id_turno) VALUES (:id_tutor, :id_materia, :id_turno)");
                    $stmt->execute([':id_tutor' => $id_tutor, ':id_materia' => $oferta['id_materia'], ':id_turno' => $oferta['id_turno']]);
                }

                // Asegurar que la materia esté asignada al tutor en tutor_materia
                $stmt = $this->pdo->prepare("SELECT COUNT(*) AS total FROM tutor_materia WHERE id_tutor = :id_tutor AND id_materia = :id_materia");
                $stmt->execute([':id_tutor' => $id_tutor, ':id_materia' => $oferta['id_materia']]);
                $tieneMateria = (int)$stmt->fetch()['total'] > 0;

                if (!$tieneMateria) {
                    $stmt = $this->pdo->prepare("INSERT INTO tutor_materia (id_tutor, id_materia) VALUES (:id_tutor, :id_materia)");
                    $stmt->execute([':id_tutor' => $id_tutor, ':id_materia' => $oferta['id_materia']]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Cancela la aceptación de un tutor (elimina disponibilidad y respuesta).
     * @param int $id_oferta Identificador de la oferta
     * @param int $id_tutor Identificador del tutor
     * @return bool True si la operación fue exitosa
     */
    public function cancelarAceptacion($id_oferta, $id_tutor)
    {
        $this->pdo->beginTransaction();

        try {
            $oferta = $this->obtenerPorId($id_oferta);
            if (!$oferta) throw new Exception("Oferta no encontrada");

            // Eliminar disponibilidad_tutor
            $stmt = $this->pdo->prepare("DELETE FROM disponibilidad_tutor WHERE id_tutor = :id_tutor AND id_materia = :id_materia AND id_turno = :id_turno");
            $stmt->execute([':id_tutor' => $id_tutor, ':id_materia' => $oferta['id_materia'], ':id_turno' => $oferta['id_turno']]);

            // Eliminar respuesta
            $stmt = $this->pdo->prepare("DELETE FROM oferta_respuesta WHERE id_oferta = :id_oferta AND id_tutor = :id_tutor");
            $stmt->execute([':id_oferta' => $id_oferta, ':id_tutor' => $id_tutor]);

            // Quitar la materia del catálogo del tutor. Sin esto el tutor
            // conservaba la materia asignada aunque ya no imparta el turno.
            $stmt = $this->pdo->prepare(
                "DELETE tm FROM tutor_materia tm
                 LEFT JOIN disponibilidad_tutor dt
                        ON dt.id_tutor = tm.id_tutor AND dt.id_materia = tm.id_materia
                 WHERE tm.id_tutor = :id_tutor
                   AND tm.id_materia = :id_materia
                   AND dt.id_tutor IS NULL"
            );
            $stmt->execute([':id_tutor' => $id_tutor, ':id_materia' => $oferta['id_materia']]);

            // Abrir la oferta nuevamente
            $stmt = $this->pdo->prepare("UPDATE ofertas_admin SET id_tutor_assigned = NULL, estado = 'abierta' WHERE id_oferta = :id AND id_tutor_assigned = :id_tutor");
            $stmt->execute([':id' => $id_oferta, ':id_tutor' => $id_tutor]);

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    /**
     * Verifica si un tutor ya respondió a una oferta.
     * @param int $id_oferta Identificador de la oferta
     * @param int $id_tutor Identificador del tutor
     * @return string|false Estado de la respuesta o false si no respondió
     */
    public function obtenerRespuestaTutor($id_oferta, $id_tutor)
    {
        $stmt = $this->pdo->prepare("SELECT estado FROM oferta_respuesta WHERE id_oferta = :id_oferta AND id_tutor = :id_tutor");
        $stmt->execute([':id_oferta' => $id_oferta, ':id_tutor' => $id_tutor]);
        $row = $stmt->fetch();
        return $row ? $row['estado'] : false;
    }
}
