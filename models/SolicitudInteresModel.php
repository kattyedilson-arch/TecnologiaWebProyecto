<?php
// =========================================================
// MODELO: SOLICITUDES DE INTERÉS (SolicitudInteresModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'solicitudes_interes'.
// Una oferta puede estar publicada y aun así no tener docente:
// el administrador define turno/modalidad/aula y es el tutor quien
// la acepta después. Hasta entonces ofertas_admin.id_tutor_assigned
// es NULL y el estudiante NO puede agendar, porque
// tutorias.id_tutor es NOT NULL.
//
// Este modelo registra el INTERÉS del estudiante por ese horario.
// No crea una sesión: deja constancia de la demanda para que la
// administración sepa que la materia tiene público y para que el
// estudiante pueda enterarse en cuanto haya docente.
//
// Estados:
//   pendiente  el estudiante registró su interés y espera docente
//   atendida   un tutor aceptó la oferta; ya se puede reservar
//   cancelada  el estudiante la retiró, o la oferta se cerró/eliminó
// =========================================================
class SolicitudInteresModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra el interés del estudiante por una oferta sin docente.
     * Reanuda una solicitud previa cancelada; si la existente está
     * 'atendida' se deja como está, porque en ese caso la oferta ya
     * tiene docente y el estudiante debe reservarla, no volver a esperar.
     * @param int $idEstudiante Ficha del estudiante
     * @param int $idMateria Materia de la oferta
     * @param int $idOferta Oferta concreta (turno/modalidad/aula)
     * @param string $mensaje Tema que el estudiante quiere tratar (opcional)
     * @return int|false id_solicitud creada o reactivada, false si falló
     */
    public function registrar($idEstudiante, $idMateria, $idOferta, $mensaje = '')
    {
        // MySQL evalúa las asignaciones de ON DUPLICATE KEY UPDATE en
        // orden, así que 'estado' va AL FINAL: las demás leen el valor
        // anterior para distinguir una cancelación de una fila viva.
        $sql = "INSERT INTO solicitudes_interes (id_estudiante, id_materia, id_oferta, estado, mensaje)
                VALUES (:id_estudiante, :id_materia, :id_oferta, 'pendiente', :mensaje)
                AS nuevo
                ON DUPLICATE KEY UPDATE
                    fecha_respuesta = IF(solicitudes_interes.estado = 'cancelada', NULL, solicitudes_interes.fecha_respuesta),
                    fecha_creacion  = IF(solicitudes_interes.estado = 'cancelada', CURRENT_TIMESTAMP, solicitudes_interes.fecha_creacion),
                    mensaje         = nuevo.mensaje,
                    estado          = IF(solicitudes_interes.estado = 'cancelada', 'pendiente', solicitudes_interes.estado)";

        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([
            ':id_estudiante' => (int)$idEstudiante,
            ':id_materia'    => (int)$idMateria,
            ':id_oferta'     => (int)$idOferta,
            ':mensaje'       => trim((string)$mensaje)
        ]);

        if (!$ok) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "SELECT id_solicitud FROM solicitudes_interes
             WHERE id_estudiante = :id_estudiante AND id_oferta = :id_oferta"
        );
        $stmt->execute([':id_estudiante' => (int)$idEstudiante, ':id_oferta' => (int)$idOferta]);

        $id = $stmt->fetchColumn();
        return $id ? (int)$id : false;
    }

    /**
     * Intereses del estudiante con el detalle de la oferta: materia,
     * carrera, turno, horarios, modalidad y si ya tiene docente.
     * @param int $idEstudiante Ficha del estudiante
     * @param bool $soloActivas Si true, omite las canceladas
     * @return array Filas ordenadas de la más reciente a la más antigua
     */
    public function obtenerPorEstudiante($idEstudiante, $soloActivas = true)
    {
        $sql = "SELECT s.id_solicitud, s.estado, s.mensaje,
                       s.fecha_creacion, s.fecha_respuesta,
                       o.id_oferta, o.estado AS estado_oferta, o.modalidad,
                       o.lugar_o_enlace, o.nivel_academico,
                       m.id_materia, m.nombre_materia,
                       c.nombre_carrera,
                       tu.nombre_turno, tu.hora_inicio, tu.hora_fin,
                       tut.nombre AS tut_nombre, tut.apellido AS tut_apellido
                FROM solicitudes_interes s
                INNER JOIN ofertas_admin o ON o.id_oferta = s.id_oferta
                INNER JOIN materias m       ON m.id_materia = s.id_materia
                LEFT JOIN carreras c        ON c.id_carrera  = m.id_carrera
                INNER JOIN turnos tu        ON tu.id_turno   = o.id_turno
                LEFT JOIN tutores tt        ON tt.id_tutor   = o.id_tutor_assigned
                LEFT JOIN usuarios tut      ON tut.id_usuario = tt.id_usuario
                WHERE s.id_estudiante = :id_estudiante";

        if ($soloActivas) {
            $sql .= " AND s.estado <> 'cancelada'";
        }

        $sql .= " ORDER BY FIELD(s.estado, 'pendiente', 'atendida', 'cancelada'), m.nombre_materia ASC";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_estudiante' => (int)$idEstudiante]);
        return $stmt->fetchAll();
    }

    /**
     * Verifica si el estudiante ya tiene un interés registrado por la
     * oferta, sea del estado que sea.
     * @param int $idEstudiante Ficha del estudiante
     * @param int $idOferta Oferta consultada
     * @return array|false Fila de solicitudes_interes o false
     */
    public function obtenerInteresDeEstudiante($idEstudiante, $idOferta)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM solicitudes_interes
             WHERE id_estudiante = :id_estudiante AND id_oferta = :id_oferta"
        );
        $stmt->execute([':id_estudiante' => (int)$idEstudiante, ':id_oferta' => (int)$idOferta]);
        return $stmt->fetch();
    }

    /**
     * Intereses de una oferta en un estado concreto, con el usuario
     * destinatario de la notificación. Se usa al aceptar una oferta
     * (los pendientes pasan a 'atendida') y al cancelar la
     * aceptación (los atendidos vuelven a 'pendiente').
     * @param int $idOferta Oferta afectada
     * @param string $estado Estado a buscar: pendiente|atendida|cancelada
     * @return array Filas {id_solicitud, id_usuario, nombre, apellido}
     */
    public function obtenerPorOferta($idOferta, $estado = 'pendiente')
    {
        $sql = "SELECT s.id_solicitud, s.mensaje, u.id_usuario,
                       u.nombre, u.apellido
                FROM solicitudes_interes s
                INNER JOIN estudiantes e ON e.id_estudiante = s.id_estudiante
                INNER JOIN usuarios u     ON u.id_usuario    = e.id_usuario
                WHERE s.id_oferta = :id_oferta
                  AND s.estado = :estado
                ORDER BY s.fecha_creacion ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id_oferta' => (int)$idOferta, ':estado' => $estado]);
        return $stmt->fetchAll();
    }

    /**
     * Pasa a 'atendida' los intereses pendientes de una oferta que ya
     * tiene docente. Devuelve cuántas filas cambiaron.
     * @param int $idOferta Oferta asignada
     * @return int Número de intereses actualizados
     */
    public function marcarAtendidasPorOferta($idOferta)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE solicitudes_interes
             SET estado = 'atendida', fecha_respuesta = CURRENT_TIMESTAMP
             WHERE id_oferta = :id_oferta AND estado = 'pendiente'"
        );
        $stmt->execute([':id_oferta' => (int)$idOferta]);
        return $stmt->rowCount();
    }

    /**
     * Revierte a 'pendiente' los intereses ya atendidos de una oferta
     * que vuelve a quedarse sin docente (el tutor canceló su aceptación).
     * El interés se conserva para que el estudiante sepa que debe volver
     * a solicitarlo, en lugar de perder el registro.
     * @param int $idOferta Oferta reabierta
     * @return int Número de intereses actualizados
     */
    public function reabrirPorOferta($idOferta)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE solicitudes_interes
             SET estado = 'pendiente', fecha_respuesta = NULL
             WHERE id_oferta = :id_oferta AND estado = 'atendida'"
        );
        $stmt->execute([':id_oferta' => (int)$idOferta]);
        return $stmt->rowCount();
    }

    /**
     * Cancela los intereses pendientes de una oferta que deja de estar
     * disponible (cerrada o eliminada por el administrador).
     * @param int $idOferta Oferta retirada
     * @return int Número de intereses cancelados
     */
    public function cancelarPendientesPorOferta($idOferta)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE solicitudes_interes
             SET estado = 'cancelada', fecha_respuesta = CURRENT_TIMESTAMP
             WHERE id_oferta = :id_oferta AND estado = 'pendiente'"
        );
        $stmt->execute([':id_oferta' => (int)$idOferta]);
        return $stmt->rowCount();
    }

    /**
     * Cancela un interés. La validación de pertenencia va en el WHERE:
     * el estudiante solo puede cancelar el suyo.
     * @param int $idSolicitud Solicitud a cancelar
     * @param int $idEstudiante Ficha del estudiante propietario
     * @return bool True si se canceló; false si la solicitud no era suya,
     *              no existía o ya estaba cancelada
     */
    public function cancelar($idSolicitud, $idEstudiante)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE solicitudes_interes
             SET estado = 'cancelada', fecha_respuesta = CURRENT_TIMESTAMP
             WHERE id_solicitud = :id_solicitud
               AND id_estudiante = :id_estudiante
               AND estado <> 'cancelada'"
        );
        $stmt->execute([
            ':id_solicitud' => (int)$idSolicitud,
            ':id_estudiante' => (int)$idEstudiante
        ]);

        // execute() devuelve true aunque no haya tocado filas, así que se
        // comprueba el recuento: de otro modo el mensaje de la interfaz
        // confirmaría una cancelación que en realidad no ocurrió.
        return $stmt->rowCount() > 0;
    }

    /**
     * Cuántos estudiantes están esperando docente en una oferta.
     * @param int $idOferta Oferta abierta
     * @return int Número de intereses pendientes
     */
    public function contarPendientesPorOferta($idOferta)
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM solicitudes_interes
             WHERE id_oferta = :id_oferta AND estado = 'pendiente'"
        );
        $stmt->execute([':id_oferta' => (int)$idOferta]);
        return (int)$stmt->fetch()['total'];
    }

    /**
     * Cuántas materias tiene el estudiante esperando docente.
     * @param int $idEstudiante Ficha del estudiante
     * @return int Número de intereses pendientes
     */
    public function contarPendientesPorEstudiante($idEstudiante)
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) FROM solicitudes_interes
             WHERE id_estudiante = :id_estudiante AND estado = 'pendiente'"
        );
        $stmt->execute([':id_estudiante' => (int)$idEstudiante]);
        return (int)$stmt->fetch()['total'];
    }
}
