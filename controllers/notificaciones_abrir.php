<?php
// =========================================================
// CONTROLADOR: ABRIR NOTIFICACIÓN (notificaciones_abrir.php)
// ---------------------------------------------------------
// Entrada desde la campanita del header: marca la notificación
// como leída (solo si pertenece al usuario de la sesión) y
// redirige al enlace asociado para que el aviso no vuelva a
// mostrarse como pendiente.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

$id = (int)($_GET['id'] ?? 0);
$usuario = (int)$_SESSION['id_usuario'];

$model = new NotificacionModel($pdo);
$destino = $id > 0 ? $model->obtenerPorId($id, $usuario) : false;

if ($destino) {
    // Marca leída y apunta al enlace original del aviso
    $model->marcarLeida($id, $usuario);
    redirigir($destino['enlace'] ?: 'notificaciones_listar.php');
}

redirigir('notificaciones_listar.php');