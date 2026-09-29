<?php
// =========================================================
// CONTROLADOR: GUARDAR COHORTE MG (cohortes_guardar.php)
// ---------------------------------------------------------
// Crea o actualiza una cohorte de Modalidades de Grado
// (HU-022). POST-only con CSRF. Valida código/nombre
// obligatorios y únicos, fechas coherentes y estado válido.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/CohorteMgModel.php';

requerirPermiso('gestionar_cohortes_mg');

$volver = 'admin_cohortes.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$id = (int)($_POST['id'] ?? 0);
// El código es corto (C-2026-1): se normaliza para tolerar guiones
// tipográficos o espacios invisibles pegados desde Word.
$codigoCrudo = limpiarTexto($_POST['codigo'] ?? '');
$codigo = strtoupper(normalizarCodigo($codigoCrudo));
$nombre = limpiarTexto($_POST['nombre'] ?? '');
$idPeriodo = (int)($_POST['id_periodo'] ?? 0);
$inicio = limpiarTexto($_POST['fecha_inicio'] ?? '');
$fin = limpiarTexto($_POST['fecha_fin'] ?? '');
$estado = $_POST['estado'] ?? 'vigente';

$errores = [];
if ($codigo === '' || $nombre === '') {
    $errores[] = 'El código y el nombre de la cohorte son obligatorios.';
}
if ($codigo !== '' && !preg_match('/^[A-Z0-9-]+$/', $codigo)) {
    $errores[] = 'El código solo admite letras, números y guiones (p. ej. C-2026-1). '
        . 'Valor recibido: ' . valorVisible($codigoCrudo);
}
if (mb_strlen($codigo, 'UTF-8') > 30) {
    $errores[] = 'El código no puede superar los 30 caracteres.';
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $inicio) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fin)) {
    $errores[] = 'Las fechas de inicio y fin son obligatorias.';
} elseif ($inicio > $fin) {
    $errores[] = 'La fecha de fin no puede ser anterior al inicio.';
}
if (!in_array($estado, ['vigente', 'cerrada'], true)) {
    $errores[] = 'El estado seleccionado no es válido.';
}

$cohorteModel = new CohorteMgModel($pdo);
if (!$errores && $cohorteModel->existe($codigo, $nombre, $id > 0 ? $id : null)) {
    $errores[] = 'Ya existe una cohorte con ese código o nombre.';
}

if (!empty($errores)) {
    setMensajes('danger', $errores);
    redirigir($volver . ($id > 0 ? '?editar=' . $id : ''));
}

$datos = [
    'codigo'       => $codigo,
    'nombre'       => $nombre,
    'id_periodo'   => $idPeriodo > 0 ? $idPeriodo : null,
    'fecha_inicio' => $inicio,
    'fecha_fin'    => $fin,
    'estado'       => $estado,
];

try {
    if ($id > 0) {
        $cohorteModel->actualizar($id, $datos);
        setMensaje('success', 'Cohorte actualizada correctamente.');
    } else {
        $cohorteModel->crear($datos);
        setMensaje('success', 'Cohorte registrada correctamente.');
    }
} catch (PDOException $e) {
    setMensaje('danger', 'No se pudo guardar la cohorte.');
}

redirigir($volver);