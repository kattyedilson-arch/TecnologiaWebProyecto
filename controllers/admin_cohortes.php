<?php
// =========================================================
// CONTROLADOR: GESTIÓN DE COHORTES MG (admin_cohortes.php)
// ---------------------------------------------------------
// Lista las cohortes de Modalidades de Grado (HU-022) y muestra
// el formulario para crear o editar una. Permiso:
// gestionar_cohortes_mg (administrador y coordinador de MG).
// El parámetro ?editar=ID precarga el formulario.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/CohorteMgModel.php';
require_once __DIR__ . '/../models/PeriodoModel.php';

requerirPermiso('gestionar_cohortes_mg');

$cohorteModel = new CohorteMgModel($pdo);
$periodoModel = new PeriodoModel($pdo);

$cohortes = $cohorteModel->obtenerTodas();
$periodos = $periodoModel->obtenerTodos();

$cohorteEditar = null;
if (!empty($_GET['editar'])) {
    $cohorteEditar = $cohorteModel->obtenerPorId((int)$_GET['editar']);
}

$tituloPagina = 'Cohortes de Modalidades de Grado';
require_once __DIR__ . '/../views/admin/cohortes.php';