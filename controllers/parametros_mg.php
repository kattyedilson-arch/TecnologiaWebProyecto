<?php
// =========================================================
// CONTROLADOR: PARÁMETROS MG (parametros_mg.php)
// ---------------------------------------------------------
// Lista los parámetros configurables del módulo MG (HU-020).
// Solo administrador y coordinador de MG pueden editarlos;
// el resto del equipo los ve en solo lectura.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ParametroModel.php';

if (!tienePermiso('gestionar_parametros_mg')) {
    requerirPermiso('ver_panel_mg');
}

$parametroModel = new ParametroModel($pdo);
$parametros = $parametroModel->obtenerTodos();

$puedeEditar = tienePermiso('gestionar_parametros_mg');

$tituloPagina = 'Parámetros de Modalidades de Grado';
require_once __DIR__ . '/../views/admin/parametros_mg.php';