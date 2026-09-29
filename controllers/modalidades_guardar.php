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
// El código es corto (MOD-PG): se normaliza para tolerar guiones
// tipográficos o espacios invisibles pegados desde Word.
$codigoCrudo = limpiarTexto($_POST['codigo'] ?? '');
$codigo   = strtoupper(normalizarCodigo($codigoCrudo));
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
if ($codigo !== '' && !preg_match('/^[A-Z0-9-]+$/', $codigo)) {
    $errores[] = 'El código solo admite letras, números y guiones (p. ej. MOD-PG). '
        . 'Valor recibido: ' . valorVisible($codigoCrudo);
}
if (mb_strlen($codigo, 'UTF-8') > 20) {
    $errores[] = 'El código no puede superar los 20 caracteres.';
}
if (!in_array($tipo, $tiposValidos, true)) {
    $errores[] = 'El tipo seleccionado no es válido.';
}
if (!in_array($estado, ['publicada', 'no_publicada'], true)) {
    $errores[] = 'El estado seleccionado no es válido.';
}

$modalidadModel = new ModalidadModel($pdo);
if (!$errores && $modalidadModel->existe($codigo, '', $id > 0 ? $id : null)) {
    $errores[] = 'Ya existe una modalidad con ese código.';
}

if (!empty($errores)) {
    setMensajes('danger', $errores);
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
        // Verificar que la modalidad existe antes de intentar actualizar
        $existe = $modalidadModel->obtenerPorId($id);
        if (!$existe) {
            throw new PDOException("No existe modalidad con id = $id");
        }
        $modalidadModel->actualizar($id, $datos);
        setMensaje('success', 'Modalidad actualizada correctamente.');
    } else {
        $modalidadModel->crear($datos);
        setMensaje('success', 'Modalidad registrada en el catálogo.');
    }
} catch (PDOException $e) {
    setMensaje('danger', 'No se pudo guardar la modalidad: ' . $e->getMessage());
}

redirigir($volver);