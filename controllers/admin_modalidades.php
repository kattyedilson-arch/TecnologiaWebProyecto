<?php
// =========================================================
// CONTROLADOR: CATÁLOGO DE MODALIDADES (admin_modalidades.php)
// ---------------------------------------------------------
// Lista y formula las modalidades de grado (HU-021). Permiso:
// registrar_modalidad_mg/editar_modalidad_mg/publicar_modalidad_mg
// (puede gestionarlo el administrador; el coordinador de MG
// consulta el catálogo). Parámetro ?editar=ID precarga el
// formulario.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ModalidadModel.php';

if (!tienePermiso('ver_panel_mg') && !tienePermiso('registrar_modalidad_mg')) {
    requerirPermiso('registrar_modalidad_mg');
}

$modalidadModel = new ModalidadModel($pdo);
$modalidades = $modalidadModel->obtenerTodas();

$modalidadEditar = null;
if (!empty($_GET['editar'])) {
    $modalidadEditar = $modalidadModel->obtenerPorId((int)$_GET['editar']);
}

$tituloPagina = 'Catálogo de Modalidades de Grado';
require_once __DIR__ . '/../views/admin/modalidades.php';