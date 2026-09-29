<?php
// =========================================================
// CONTROLADOR: LISTADO DE NOTIFICACIONES (notificaciones_listar.php)
// ---------------------------------------------------------
// Muestra todas las notificaciones del usuario en sesión,
// con opciones para marcar individualmente (POST) o marcar
// todas como leídas. Permiso: ver_notificaciones (todos los
// roles de portal).
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

requerirPermiso('ver_notificaciones');

$idUsuario = (int)$_SESSION['id_usuario'];
$notifModel = new NotificacionModel($pdo);

// Todas las notificaciones (lista completa, recientes primero)
$notificaciones = $notifModel->obtenerPorUsuario($idUsuario, 500);
$noLeidas = $notifModel->contarNoLeidas($idUsuario);

$tituloPagina = 'Mis Notificaciones';
require_once __DIR__ . '/../views/notificaciones/listar.php';