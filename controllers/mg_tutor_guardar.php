<?php
// =========================================================
// CONTROLADOR: GUARDAR TUTOR MG (mg_tutor_guardar.php)
// ---------------------------------------------------------
// HU-025 / HU-026. Procesa la asignación (primera vez) y el
// cambio/renuncia de tutor de un expediente. Ambos cierran
// cualquier asignación vigente previa en el historial y crean
// la nueva como 'vigente'. POST-only con CSRF.
// Permiso: asignar_tutor_mg (asignar) / cambiar_tutor_mg (cambiar).
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ExpedienteMgModel.php';
require_once __DIR__ . '/../models/AsignacionTutorModel.php';
require_once __DIR__ . '/../models/EtapasExpedienteModel.php';
require_once __DIR__ . '/../models/NotificacionModel.php';
require_once __DIR__ . '/../models/ParametroModel.php';

$volver = 'mg_expedientes.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}

$id = (int)($_POST['id_declaracion'] ?? 0);
$accion = $_POST['accion'] ?? '';
if ($id <= 0) {
    redirigir($volver);
}
$volverFicha = 'mg_expediente.php?id=' . $id . '&tab=tutor';

if ($accion === 'asignar') {
    requerirPermiso('asignar_tutor_mg');
} elseif ($accion === 'cambiar') {
    requerirPermiso('cambiar_tutor_mg');
} else {
    setMensaje('danger', 'Acción no reconocida.');
    redirigir($volver);
}

if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volverFicha);
}

$expedienteModel = new ExpedienteMgModel($pdo);
$asignacionModel = new AsignacionTutorModel($pdo);
$expediente = $expedienteModel->obtenerPorId($id);
if (!$expediente) {
    setMensaje('danger', 'El expediente no existe.');
    redirigir($volver);
}

$esAbierto = in_array($expediente['estado'], ['borrador', 'enviada', 'en_revision', 'aprobada'], true);
if (!$esAbierto) {
    setMensaje('danger', 'El expediente está cerrado; no se puede modificar la asignación de tutor.');
    redirigir($volverFicha);
}

$idTutor = (int)($_POST['id_tutor'] ?? 0);
if ($idTutor <= 0) {
    setMensaje('danger', 'Debes seleccionar un docente tutor.');
    redirigir($volverFicha);
}

$datos = [
    'motivo_fin'                => limpiarTexto($_POST['motivo_fin'] ?? ''),
    'referencia_decanatura'     => limpiarTexto($_POST['referencia_decanatura'] ?? ''),
    'disponibilidad_consultada' => !empty($_POST['disponibilidad_consultada']) ? 1 : 0,
    'observaciones'             => limpiarTexto($_POST['observaciones'] ?? ''),
];
$usuario = (int)$_SESSION['id_usuario'];

try {
    // Resuelve el id_usuario del docente tutor para notificarle la asignación
    $stmtTutor = $pdo->prepare("SELECT id_usuario FROM tutores WHERE id_tutor = :id");
    $stmtTutor->execute([':id' => $idTutor]);
    $idUsuarioTutor = (int)$stmtTutor->fetchColumn();
    $nombreEstudiante = trim(($expediente['estudiante_nombre'] ?? '') . ' ' . ($expediente['estudiante_apellido'] ?? ''));

    if ($accion === 'cambiar') {
        if (trim($datos['motivo_fin']) === '') {
            setMensaje('danger', 'El motivo de la renuncia/cambio es obligatorio.');
            redirigir($volverFicha);
        }
        $idAsignacion = $asignacionModel->reemplazar($id, $idTutor, $datos, $usuario);
        if ($idAsignacion === false) {
            setMensaje('danger', 'No se pudo registrar el cambio porque no hay una asignación vigente previa.');
            redirigir($volverFicha);
        }
        if ($idUsuarioTutor > 0) {
            (new NotificacionModel($pdo))->crear(
                $idUsuarioTutor,
                'modalidad',
                'Cambio de tutor en expediente',
                'Has sido asignado como nuevo tutor de ' . $nombreEstudiante . ' (' . ($expediente['modalidad_nombre'] ?? 'Modalidad de grado') . ').',
                '/views/tutor/panel.php'
            );
        }
        setMensaje('success', 'Cambio de tutor registrado. Genera la nueva carta de asignación en Documentos.');
        redirigir($volverFicha);
    }

    $idAsignacion = $asignacionModel->asignar($id, $idTutor, $datos, $usuario);
    if ($idAsignacion !== false && $idAsignacion > 0) {
        if ($idUsuarioTutor > 0) {
            (new NotificacionModel($pdo))->crear(
                $idUsuarioTutor,
                'modalidad',
                'Tutor asignado a expediente',
                'Has sido asignado como tutor de ' . $nombreEstudiante . ' (' . ($expediente['modalidad_nombre'] ?? 'Modalidad de grado') . ').',
                '/views/tutor/panel.php'
            );
        }
        setMensaje('success', 'Tutor asignado correctamente. Recuerda emitir la carta de asignación (pestaña Documentos).');
    } else {
        setMensaje('danger', $asignacionModel->obtenerVigente($id)
            ? 'Este expediente ya tiene un tutor vigente. Usa el cambio de tutor.'
            : 'No se pudo registrar la asignación de tutor.');
    }
} catch (PDOException $e) {
    setMensaje('danger', 'No se pudo registrar la asignación de tutor.');
}

redirigir($volverFicha);