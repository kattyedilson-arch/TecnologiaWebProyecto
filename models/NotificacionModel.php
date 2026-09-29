<?php
// =========================================================
// MODELO: NOTIFICACIONES (NotificacionModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'notificaciones' (HU-043). Cada aviso
// pertenece a un usuario destinatario y puede estar asociado
// a un evento del sistema (tutorías, modalidades de grado,
// actas, mensajes administrativos). La campanita del header
// consulta este modelo para el contador y las últimas 5.
// =========================================================
class NotificacionModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Registra una nueva notificación para un usuario.
     * @param int $id_usuario Destinatario
     * @param string $tipo Clasificación: tutoria|sistema|modalidad|acta
     * @param string $titulo Título corto del aviso
     * @param string $mensaje Cuerpo del aviso
     * @param string|null $enlace Ruta interna a la que lleva el clic
     * @return bool True si la inserción fue exitosa
     */
    public function crear($id_usuario, $tipo, $titulo, $mensaje, $enlace = null)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO notificaciones (id_usuario, tipo, titulo, mensaje, enlace)
             VALUES (:id_usuario, :tipo, :titulo, :mensaje, :enlace)"
        );
        return $stmt->execute([
            ':id_usuario' => $id_usuario,
            ':tipo'       => $tipo,
            ':titulo'     => $titulo,
            ':mensaje'    => $mensaje,
            ':enlace'     => $enlace
        ]);
    }

    /**
     * Notificaciones de un usuario, de la más reciente a la más antigua.
     * @param int $id_usuario Destinatario
     * @param int $limite Máximo de filas devueltas (header usa 5)
     * @return array Fila de notificaciones
     */
    public function obtenerPorUsuario($id_usuario, $limite = 5)
    {
        $sql = "SELECT id_notificacion, tipo, titulo, mensaje, enlace, leida, fecha_creacion
                FROM notificaciones
                WHERE id_usuario = :id_usuario
                ORDER BY fecha_creacion DESC, id_notificacion DESC
                LIMIT :limite";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':id_usuario', $id_usuario, PDO::PARAM_INT);
        $stmt->bindValue(':limite', (int)$limite, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Una notificación de un usuario, validando la propiedad.
     * @param int $id_notificacion Identificador del aviso
     * @param int $id_usuario Usuario propietario
     * @return array|false Fila (incluye 'enlace') o false si no existe / no es suya
     */
    public function obtenerPorId($id_notificacion, $id_usuario)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_notificacion, tipo, titulo, mensaje, enlace, leida, fecha_creacion
             FROM notificaciones
             WHERE id_notificacion = :id AND id_usuario = :id_usuario"
        );
        $stmt->execute([':id' => $id_notificacion, ':id_usuario' => $id_usuario]);
        return $stmt->fetch();
    }

    /**
     * Cantidad de avisos sin leer de un usuario (badge de la campanita).
     * @param int $id_usuario Destinatario
     * @return int Total de no leídas
     */
    public function contarNoLeidas($id_usuario)
    {
        $stmt = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM notificaciones WHERE id_usuario = :id_usuario AND leida = 0"
        );
        $stmt->execute([':id_usuario' => $id_usuario]);
        return (int)$stmt->fetch()['total'];
    }

    /**
     * Marca una notificación como leída, validando que pertenezca al usuario
     * (evita que alguien marque avisos ajenos).
     * @param int $id_notificacion Identificador del aviso
     * @param int $id_usuario Usuario propietario
     * @return bool True si se actualizó
     */
    public function marcarLeida($id_notificacion, $id_usuario)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE notificaciones SET leida = 1 WHERE id_notificacion = :id AND id_usuario = :id_usuario"
        );
        return $stmt->execute([
            ':id'          => $id_notificacion,
            ':id_usuario'  => $id_usuario
        ]);
    }

    /**
     * Marca TODAS las notificaciones del usuario como leídas (acción masiva).
     * @param int $id_usuario Destinatario
     * @return bool True si la actualización fue exitosa
     */
    public function marcarTodasLeidas($id_usuario)
    {
        $stmt = $this->pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE id_usuario = :id_usuario");
        return $stmt->execute([':id_usuario' => $id_usuario]);
    }
}