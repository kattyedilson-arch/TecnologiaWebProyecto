<?php
// =========================================================
// CONTROLADOR: GUARDAR EVENTO DE CALENDARIO (calendario_guardar.php)
// ---------------------------------------------------------
// Crea o actualiza un hito del calendario MG (HU-022).
// POST-only con CSRF. Valida título obligatorio y fecha válida.
// Permiso: gestionar_calendario_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/CalendarioMgModel.php';
require_once __DIR__ . '/../models/CohorteMgModel.php';

requerirPermiso('gestionar_calendario_mg');

$volver = 'mg_calendario.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$id = (int)($_POST['id'] ?? 0);
$titulo = limpiarTexto($_POST['titulo'] ?? '');
$tipoHito = $_POST['tipo_hito'] ?? 'entrega';
$fecha = limpiarTexto($_POST['fecha'] ?? '');
$idCohorte = (int)($_POST['id_cohorte'] ?? 0);
$descripcion = limpiarTexto($_POST['descripcion'] ?? '');

$tiposValidos = ['taller', 'informe', 'defensa', 'entrega'];

$errores = [];
if ($titulo === '') {
    $errores[] = 'El título del hito es obligatorio.';
}
if (!in_array($tipoHito, $tiposValidos, true)) {
    $errores[] = 'El tipo de hito no es válido.';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $errores[] = 'La fecha del evento es obligatoria.';
}

$calendarioModel = new CalendarioMgModel($pdo);
if (!$errores && $idCohorte > 0) {
    $cohorteModel = new CohorteMgModel($pdo);
    if (!$cohorteModel->obtenerPorId($idCohorte)) {
        $errores[] = 'La cohorte seleccionada no existe.';
    }
}

if (!empty($errores)) {
    setMensajes('danger', $errores);
    redirigir($volver . ($id > 0 ? '?editar=' . $id : ($idCohorte > 0 ? '?id_cohorte=' . $idCohorte : '')));
}

$datos = [
    'titulo'      => $titulo,
    'tipo_hito'   => $tipoHito,
    'fecha'       => $fecha,
    'id_cohorte'  => $idCohorte > 0 ? $idCohorte : null,
    'descripcion' => $descripcion,
];

try {
    if ($id > 0) {
        $calendarioModel->actualizar($id, $datos);
        setMensaje('success', 'Hito actualizado correctamente.');
    } else {
        $calendarioModel->crear($datos);
        setMensaje('success', 'Hito registrado en el calendario.');
    }
} catch (PDOException $e) {
    setMensaje('danger', 'No se pudo guardar el hito.');
}

redirigir($volver . ($idCohorte > 0 ? '?id_cohorte=' . $idCohorte : ''));