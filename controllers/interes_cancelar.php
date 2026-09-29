<?php
// =========================================================
// CONTROLADOR: CANCELAR INTERÉS EN UNA MATERIA
// (interes_cancelar.php)
// ---------------------------------------------------------
// El estudiante puede retirarse de la lista de espera de una
// materia sin docente desde su panel. La fila no se borra: pasa a
// estado 'cancelada' para conservar el historial, y puede volver a
// registrarse más adelante (el modelo la reactiva).
//
// Acepta únicamente POST con token CSRF.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/SolicitudInteresModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

// Destino de retorno: el panel del estudiante, donde vive la lista
$volver = '../views/estudiante/panel.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    setMensaje('danger', 'Operación no válida.');
    redirigir($volver);
}

$rolSesion = $_SESSION['rol'] ?? '';
$idUsuario = $_SESSION['id_usuario'] ?? 0;

if ($rolSesion !== 'estudiante') {
    setMensaje('danger', 'Solo los estudiantes pueden cancelar su solicitud.');
    redirigirAlPanel();
    exit;
}

if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$idSolicitud = (int)($_POST['id_solicitud'] ?? 0);

if (!$idSolicitud) {
    setMensaje('danger', 'Solicitud no válida.');
    redirigir($volver);
}

$estudianteModel = new EstudianteModel($pdo);
$estudiante = $estudianteModel->obtenerPorUsuario($idUsuario);

if (!$estudiante) {
    setMensaje('danger', 'No se encontró tu ficha de estudiante.');
    redirigir($volver);
}

$interesModel = new SolicitudInteresModel($pdo);

// El UPDATE filtra por id_estudiante: cancela solo la propia fila,
// así que un id_solicitud ajeno no produce ningún cambio.
$ok = $interesModel->cancelar($idSolicitud, (int)$estudiante['id_estudiante']);

if ($ok) {
    setMensaje('success', 'Cancelaste tu solicitud. Ya no estás en la lista de espera de esa materia.');
} else {
    setMensaje('danger', 'No se pudo cancelar la solicitud. Es posible que ya no estuviera activa.');
}

redirigir($volver);
