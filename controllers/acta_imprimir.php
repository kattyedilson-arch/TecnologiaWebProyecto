<?php
// =========================================================
// CONTROLADOR: IMPRIMIR / VER ACTA (acta_imprimir.php)
// ---------------------------------------------------------
// Renderiza el acta oficial de calificación como documento
// imprimible (HTML plano con CSS de impresión). Si el acta aún
// no fue firmada muestra una marca de BORRADOR. Permiso:
// tramitar_modalidad_mg (equipo MG).
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

if (!$declaracion || $declaracion['estado'] !== 'aprobada') {
    setMensaje('danger', 'La declaración no existe o no está aprobada.');
    redirigir('mg_jurados.php');
}

$actaModel = new ActaModel($pdo);
$actaModel->abrirSiVacio($id);
$acta = $actaModel->obtenerPorDeclaracion($id);
$jurado = (new JuradoModel($pdo))->obtenerPorDeclaracion($id);
$notaMax = $actaModel->notaMax();

if (!$acta || !$acta['notas']) {
    setMensaje('warning', 'El acta aún no tiene notas registradas.');
    redirigir('mg_acta.php?id=' . $id);
}

// Un solo miembro del jurado ingresa al acta final
$notas = $acta['notas'];
$notasProc = array_map(function ($n) {
    return array_merge($n, ['nombre_completo' => trim($n['nombre'] . ' ' . $n['apellido'])]);
}, $notas);

require_once __DIR__ . '/../views/mg/acta_imprimir.php';