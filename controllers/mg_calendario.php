<?php
// =========================================================
// CONTROLADOR: CALENDARIO DE HITOS MG (mg_calendario.php)
// ---------------------------------------------------------
// Lista los hitos del proceso de Modalidades de Grado (HU-022):
// talleres, informes, defensas y entregas. Admite el filtro
// ?id_cohorte=ID para ver el calendario de una cohorte en
// particular y mostrar el formulario de alta/edición.
// Permiso: gestionar_calendario_mg (admin y coordinador).
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/CalendarioMgModel.php';
require_once __DIR__ . '/../models/CohorteMgModel.php';

requerirPermiso('gestionar_calendario_mg');

$calendarioModel = new CalendarioMgModel($pdo);
$cohorteModel = new CohorteMgModel($pdo);

$idCohorte = (int)($_GET['id_cohorte'] ?? 0);
$idEditar = (int)($_GET['editar'] ?? 0);

if ($idCohorte > 0) {
    $eventos = $calendarioModel->obtenerPorCohorte($idCohorte);
    $cohorteActual = $cohorteModel->obtenerPorId($idCohorte);
} else {
    $eventos = $calendarioModel->obtenerTodos();
    $cohorteActual = null;
}

$cohortes = $cohorteModel->obtenerVigentes();

$eventoEditar = null;
if ($idEditar > 0) {
    $eventoEditar = $calendarioModel->obtenerPorId($idEditar);
}

$tituloPagina = 'Calendario de Modalidades de Grado';
require_once __DIR__ . '/../views/mg/calendario.php';