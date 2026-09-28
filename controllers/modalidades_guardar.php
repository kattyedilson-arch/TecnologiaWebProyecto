<?php
// =========================================================
// CONTROLADOR: GUARDAR MODALIDAD (modalidades_guardar.php)
// ---------------------------------------------------------
// Crea o actualiza una modalidad del catálogo (HU-021).
// POST-only con CSRF. Valida código/nombre obligatorios y
// únicos, tipo dentro de la lista y estado válido. Permiso:
// registrar/editar modalidad (administrador).
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ModalidadModel.php';

if (!tienePermiso('registrar_modalidad_mg') && !tienePermiso('editar_modalidad_mg')) {
    requerirPermiso('registrar_modalidad_mg');
}

$volver = 'admin_modalidades.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}

if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$id = (int)($_POST['id'] ?? 0);
$codigo   = limpiarTexto($_POST['codigo'] ?? '');
$nombre   = limpiarTexto($_POST['nombre'] ?? '');
$descripcion = limpiarTexto($_POST['descripcion'] ?? '');
$tipo     = $_POST['tipo'] ?? 'documental';
$requisitos = limpiarTexto($_POST['requisitos'] ?? '');
$estado   = $_POST['estado'] ?? 'no_publicada';

$tiposValidos = ['documental', 'investigacion', 'practica'];

$errores = [];
if ($codigo === '' || $nombre === '') {
    $errores[] = 'El código y el nombre son obligatorios.';
}
if (!in_array($tipo, $tiposValidos, true)) {
    $errores[] = 'El tipo seleccionado no es válido.';
}
if (!in_array($estado, ['publicada', 'no_publicada'], true)) {
    $errores[] = 'El estado seleccionado no es válido.';
}

$modalidadModel = new ModalidadModel($pdo);
if (!$errores && $modalidadModel->existe($codigo, $nombre, $id > 0 ? $id : null)) {
    $errores[] = 'Ya existe una modalidad con ese código o nombre.';
}

if (!empty($errores)) {
    foreach ($errores as $error) {
        setMensaje('danger', $error);
    }
    redirigir($volver . ($id > 0 ? '?editar=' . $id : ''));
}

$datos = [
    'codigo'      => $codigo,
    'nombre'      => $nombre,
    'descripcion' => $descripcion,
    'tipo'        => $tipo,
    'requisitos'  => $requisitos,
    'estado'      => $estado,
];

try {
    if ($id > 0) {
        $modalidadModel->actualizar($id, $datos);
        setMensaje('success', 'Modalidad actualizada correctamente.');
    } else {
        $modalidadModel->crear($datos);
        setMensaje('success', 'Modalidad registrada en el catálogo.');
    }
} catch (PDOException $e) {
    setMensaje('danger', 'No se pudo guardar la modalidad.');
}

redirigir($volver);