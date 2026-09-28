<?php
// =========================================================
// CONTROLADOR: GESTIÓN DE PERIODOS ACADÉMICOS (admin_periodos.php)
// ---------------------------------------------------------
// Lista los periodos académicos (HU-044) y muestra el
// formulario para crear o editar uno. Permiso requerido:
// gestionar_periodos (administrador y coordinador de MG).
// El parámetro ?editar=ID precarga el formulario para editar.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/PeriodoModel.php';

requerirPermiso('gestionar_periodos');

$periodoModel = new PeriodoModel($pdo);
$periodos = $periodoModel->obtenerTodos();

// Precarga para editar un periodo (solo datos del propio sistema)
$periodoEditarCompleto = null;
if (!empty($_GET['editar'])) {
    $periodoEditarCompleto = $periodoModel->obtenerPorId((int)$_GET['editar']);
}

$tituloPagina = 'Periodos Académicos';
require_once __DIR__ . '/../views/admin/periodos.php';