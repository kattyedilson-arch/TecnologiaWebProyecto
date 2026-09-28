<?php
// =========================================================
// CONTROLADOR: ACTA DE CALIFICACIÓN (mg_acta.php)
// ---------------------------------------------------------
// Detalle de una declaración aprobada para evaluar la defensa:
// abre el acta si no existe, lista el jurado con su cuadro de
// notas, muestra la nota final/resultado y permite firmarla.
// GET + vista. Edición y firma viven en acta_guardar.php.
// Permiso: tramitar_modalidad_mg (admin, coord, auxiliar MG).
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';

requerirPermiso('tramitar_modalidad_mg');

$id = (int)($_GET['id'] ?? 0);

require_once __DIR__ . '/../models/DeclaracionModel.php';
require_once __DIR__ . '/../models/JuradoModel.php';
require_once __DIR__ . '/../models/ActaModel.php';

$declaracionModel = new DeclaracionModel($pdo);
$declaracion = $id > 0 ? $declaracionModel->obtenerPorId($id) : null;

if (!$declaracion) {
    setMensaje('danger', 'La declaración no existe.');
    redirigir('mg_jurados.php');
}
if ($declaracion['estado'] !== 'aprobada') {
    setMensaje('warning', 'Solo las declaraciones aprobadas tienen acta de calificación.');
    redirigir('mg_tribunal.php?id=' . $id);
}

$actaModel = new ActaModel($pdo);
$actaModel->abrirSiVacio($id);
$acta = $actaModel->obtenerPorDeclaracion($id);

$notaMax = $actaModel->notaMax();
$aprobadoMin = $actaModel->aprobadoMin();

$juradoModel = new JuradoModel($pdo);
$jurado = $juradoModel->obtenerPorDeclaracion($id);

$rolSesion = $_SESSION['rol'] ?? '';
$puedeGuardar = in_array($rolSesion, ['administrador', 'coordinador_mg', 'auxiliar_mg'], true);
$puedeFirmar  = in_array($rolSesion, ['administrador', 'coordinador_mg'], true);

$tieneNotas = $acta && count($acta['notas']) > 0;

require_once __DIR__ . '/../views/mg/acta.php';