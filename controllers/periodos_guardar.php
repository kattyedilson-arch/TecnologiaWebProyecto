<?php
// =========================================================
// CONTROLADOR: GUARDAR PERIODO (periodos_guardar.php)
// ---------------------------------------------------------
// Crea o actualiza un periodo académico (HU-044). Operación
// POST-only con CSRF. Valida nombre obligatorio, fechas
// coherentes (inicio <= fin), estado válido y nombre único.
// Redirige a admin_periodos.php con mensaje flash.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/PeriodoModel.php';

requerirPermiso('gestionar_periodos');

$volver = 'admin_periodos.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}

if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$id = (int)($_POST['id'] ?? 0);
// El nombre es un código (AAAA-N): se normaliza para que un guion tipográfico
// o un espacio invisible pegado desde Word no invalide el formato. Se guarda
// el valor crudo aparte, solo para poder mostrarlo si aun así falla.
$nombreCrudo = limpiarTexto($_POST['nombre'] ?? '');
$nombre      = normalizarCodigo($nombreCrudo);
$inicio    = limpiarTexto($_POST['fecha_inicio'] ?? '');
$fin       = limpiarTexto($_POST['fecha_fin'] ?? '');
$estado    = $_POST['estado'] ?? 'abierto';
$descripcion = limpiarTexto($_POST['descripcion'] ?? '');

// Validaciones simples de negocio
$errores = [];
if ($nombre === '') {
    $errores[] = 'El nombre del periodo es obligatorio.';
}
if ($nombre !== '' && !preg_match('/^\d{4}-\d{1,2}$/', $nombre)) {
    $errores[] = 'El nombre debe seguir el formato "AAAA-N" (p. ej. 2026-1). '
        . 'Valor recibido: ' . valorVisible($nombreCrudo);
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
    $errores[] = 'Las fechas de inicio y fin son obligatorias.';
} elseif ($inicio > $fin) {
    $errores[] = 'La fecha de fin no puede ser anterior al inicio.';
}
if (!in_array($estado, ['abierto', 'cerrado'], true)) {
    $errores[] = 'El estado seleccionado no es válido.';
}

$periodoModel = new PeriodoModel($pdo);
if (!$errores && $periodoModel->existeNombre($nombre, $id > 0 ? $id : null)) {
    $errores[] = 'Ya existe un periodo con ese nombre.';
}

if (!empty($errores)) {
    setMensajes('danger', $errores);
    redirigir($volver . ($id > 0 ? '?editar=' . $id : ''));
}

$datos = [
    'nombre'       => $nombre,
    'fecha_inicio' => $inicio,
    'fecha_fin'    => $fin,
    'estado'       => $estado,
    'descripcion'  => $descripcion,
];

try {
    if ($id > 0) {
        $periodoModel->actualizar($id, $datos);
        setMensaje('success', 'Periodo actualizado correctamente.');
    } else {
        $nuevoId = $periodoModel->crear($datos);
        setMensaje('success', 'Periodo creado correctamente' . ($nuevoId ? '.' : ' (verifica los datos).'));
    }
} catch (PDOException $e) {
    setMensaje('danger', 'No se pudo guardar el periodo.');
}

redirigir($volver);