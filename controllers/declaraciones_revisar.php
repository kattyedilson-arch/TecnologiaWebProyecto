<?php
// =========================================================
// CONTROLADOR: REVISAR DECLARACIÓN (declaraciones_revisar.php)
// ---------------------------------------------------------
// El equipo MG mueve una declaración por su flujo:
//   enviada -> en_revision  (auxiliar/coordinador: tramitar_modalidad_mg)
//   en_revision -> aprobada | rechazada (coordinador: aprobar_modalidad_mg)
// POST-only con CSRF. Cada cambio notifica al estudiante.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/DeclaracionModel.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

if (!tienePermiso('tramitar_modalidad_mg') && !tienePermiso('aprobar_modalidad_mg')) {
    requerirPermiso('tramitar_modalidad_mg');
}

$volver = 'mg_declaraciones.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$id = (int)($_POST['id'] ?? 0);
$accion = $_POST['accion'] ?? '';
$observacion = limpiarTexto($_POST['observacion'] ?? '');

$declaracionModel = new DeclaracionModel($pdo);
$declaracion = $id > 0 ? $declaracionModel->obtenerPorId($id) : null;

if (!$declaracion) {
    setMensaje('danger', 'La declaración no existe.');
    redirigir($volver);
}

// Transición: enviada -> en_revision (auxiliar o coordinador)
if ($accion === 'en_revision') {
    if (!tienePermiso('tramitar_modalidad_mg')) {
        setMensaje('danger', 'No tienes permisos para tramitar declaraciones.');
        redirigir($volver);
    }
    if ($declaracion['estado'] !== 'enviada') {
        setMensaje('danger', 'Solo se puede pasar a revisión una declaración recién enviada.');
        redirigir($volver . '?estado=enviada');
    }
    $declaracionModel->marcarEnRevision($id);
    setMensaje('success', 'Declaración marcada como "en revisión".');
}

// Transición: (enviada | en_revision) -> aprobada | rechazada (solo coordinador/admin)
elseif (in_array($accion, ['aprobar', 'rechazar'], true)) {
    if (!tienePermiso('aprobar_modalidad_mg')) {
        setMensaje('danger', 'Solo el coordinador de MG puede aprobar o rechazar declaraciones.');
        redirigir($volver . '?estado=revision');
    }
    if (!in_array($declaracion['estado'], ['enviada', 'en_revision'], true)) {
        setMensaje('danger', 'La declaración ya fue resuelta.');
        redirigir($volver . '?estado=revision');
    }
    if ($accion === 'rechazar' && trim($observacion) === '') {
        setMensaje('danger', 'Para rechazar una declaración debes indicar el motivo.');
        redirigir($volver . '?estado=en_revision');
    }

    $declaracionModel->resolver($id, $accion === 'aprobar' ? 'aprobada' : 'rechazada', $observacion);

    $notificador = new NotificacionModel($pdo);
    $nombreEstudiante = trim($declaracion['nombre'] . ' ' . $declaracion['apellido']);
    if ($accion === 'aprobar') {
        // Checklist estándar de avales para la nueva declaración aprobada
        require_once __DIR__ . '/../models/AvalModel.php';
        $avalModel = new AvalModel($pdo);
        $avalModel->crearDefectoSiVacio($id);

        $notificador->crear(
            (int)$declaracion['id_usuario'],
            'modalidad',
            'Declaración aprobada',
            '¡Felicidades, ' . $nombreEstudiante . '! Tu modalidad ' . $declaracion['modalidad_nombre'] . ' fue aprobada.',
            '/controllers/estudiante_declaracion.php'
        );
        setMensaje('success', 'Declaración de ' . $nombreEstudiante . ' aprobada.');
    } else {
        $notificador->crear(
            (int)$declaracion['id_usuario'],
            'modalidad',
            'Declaración rechazada',
            'Tu declaración de ' . $declaracion['modalidad_nombre'] . ' fue rechazada. Motivo: ' . $observacion,
            '/controllers/estudiante_declaracion.php'
        );
        setMensaje('success', 'Declaración de ' . $nombreEstudiante . ' rechazada.');
    }
    redirigir($volver . '?estado=aprobada');
} else {
    setMensaje('danger', 'Acción no válida.');
}

redirigir($volver);