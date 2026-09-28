<?php
// =========================================================
// CONTROLADOR: ESTADO DE NOTIFICACIONES (notificaciones_estado.php)
// ---------------------------------------------------------
// Marca avisos como leídos. Operación POST-only con CSRF:
//   accion = 'una'  -> marca la notificación 'id' del usuario
//   accion = 'todas'-> marca todas las del usuario
// Redirige de vuelta a la página desde la que se invocó
// (solo rutas internas) o, en su defecto, al listado.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    setMensaje('danger', 'Operación no válida.');
    redirigir('/controllers/notificaciones_listar.php');
}

if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir('/controllers/notificaciones_listar.php');
}

$idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
$accion = $_POST['accion'] ?? 'una';
$notificaciones = new NotificacionModel($pdo);

if ($accion === 'todas') {
    $notificaciones->marcarTodasLeidas($idUsuario);
} else {
    $idNotificacion = (int)($_POST['id'] ?? 0);
    if ($idNotificacion > 0) {
        $notificaciones->marcarLeida($idNotificacion, $idUsuario);
    }
}

// Solo se permite regresar a páginas internas del propio sistema
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$destino = (str_starts_with($referer, '/')) ? $referer : '/controllers/notificaciones_listar.php';
redirigir($destino);