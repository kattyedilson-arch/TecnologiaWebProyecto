<?php
// =========================================================
// CONTROLADOR: ESTADO DE NOTIFICACIONES (notificaciones_estado.php)
// ---------------------------------------------------------
// Marca avisos como leídos. Operación POST-only con CSRF:
//   accion = 'una'   -> marca la notificación 'id' del usuario
//   accion = 'todas' -> marca todas las del usuario
// Si la petición es AJAX devuelve JSON con el contador de no
// leídas para refrescar la campanita sin recargar la página; en
// el resto de casos redirige a la página desde la que se invocó
// (solo rutas internas) y deja un mensaje de resultado.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

$idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
$notificaciones = new NotificacionModel($pdo);
$destino = destinoSeguro($_SERVER['HTTP_REFERER'] ?? '', '/controllers/notificaciones_listar.php');

// La campanita (dropdown del header) refresca con AJAX; los formularios
// de las páginas internas se resuelven con una redirección normal.
$esAjax = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

/**
 * Cierra la petición: JSON para AJAX (con el contador actualizado) o
 * mensaje flash + redirección de vuelta a la página de origen.
 */
function responderNotificacion($ok, $mensaje, $idUsuario, $pdo, $destino, $esAjax)
{
    if (!$esAjax) {
        setMensaje($ok ? 'success' : 'danger', $mensaje);
        redirigir($destino);
    }

    header('Content-Type: application/json; charset=utf-8');
    http_response_code($ok ? 200 : 400);
    $model = new NotificacionModel($pdo);
    echo json_encode([
        'ok'       => $ok,
        'mensaje'  => $mensaje,
        'noLeidas' => $model->contarNoLeidas($idUsuario),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    responderNotificacion(false, 'Operación no válida.', $idUsuario, $pdo, $destino, $esAjax);
}

if (!verificarTokenCsrf()) {
    responderNotificacion(false, 'La solicitud expiró. Vuelve a intentarlo.', $idUsuario, $pdo, $destino, $esAjax);
}

$accion = $_POST['accion'] ?? 'una';

if ($accion === 'todas') {
    $notificaciones->marcarTodasLeidas($idUsuario);
    $mensaje = 'Tus notificaciones se marcaron como leídas.';
} else {
    $idNotificacion = (int)($_POST['id'] ?? 0);
    if ($idNotificacion <= 0) {
        responderNotificacion(false, 'La notificación indicada no es válida.', $idUsuario, $pdo, $destino, $esAjax);
    }
    $notificaciones->marcarLeida($idNotificacion, $idUsuario);
    $mensaje = 'Notificación marcada como leída.';
}

responderNotificacion(true, $mensaje, $idUsuario, $pdo, $destino, $esAjax);
