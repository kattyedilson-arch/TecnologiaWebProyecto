<?php
// =========================================================
// CONTROLADOR: GESTIÓN DE TRIBUNAL (mg_tribunal.php)
// ---------------------------------------------------------
// Detalle de una declaración aprobada: asigna/edita el jurado
// de docentes y administra el checklist de avales. Requiere:
// ver_panel_mg (tramitar_modalidad_mg para guardar).
// Recibe ?id=ID_DECLARACION.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/DeclaracionModel.php';
require_once __DIR__ . '/../models/JuradoModel.php';
require_once __DIR__ . '/../models/AvalModel.php';

requerirPermiso('ver_panel_mg');

$id = (int)($_GET['id'] ?? 0);
$declaracionModel = new DeclaracionModel($pdo);
$declaracion = $id > 0 ? $declaracionModel->obtenerPorId($id) : null;

if (!$declaracion || $declaracion['estado'] !== 'aprobada') {
    setMensaje('danger', 'La declaración no existe o aún no está aprobada.');
    redirigir('mg_jurados.php');
}

$juradoModel = new JuradoModel($pdo);
$avalModel = new AvalModel($pdo);

$avales = $avalModel->obtenerPorDeclaracion($id);
$conteoAvales = $avalModel->contarPorDeclaracion($id);
$jurado = $juradoModel->obtenerPorDeclaracion($id);
$docentes = $juradoModel->obtenerDocentes();

$puedeGuardar = tienePermiso('tramitar_modalidad_mg');

$tituloPagina = 'Tribunal y Avales';
require_once __DIR__ . '/../views/mg/tribunal.php';