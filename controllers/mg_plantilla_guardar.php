<?php
// =========================================================
// CONTROLADOR: GUARDAR PLANTILLA MG (mg_plantilla_guardar.php)
// ---------------------------------------------------------
// HU-027. Persiste los cambios de una plantilla de documento
// (nombre + HTML con {{variables}}). La versión se incrementa
// automáticamente cuando cambia el cuerpo. POST-only con CSRF.
// Permiso: gestionar_plantillas_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/DocumentoModel.php';

requerirPermiso('gestionar_plantillas_mg');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir('mg_plantillas.php');
}
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir('mg_plantillas.php');
}

$id = (int)($_POST['id_plantilla'] ?? 0);
$nombre = limpiarTexto($_POST['nombre'] ?? '');
$cuerpo = $_POST['cuerpo_html'] ?? '';

if ($id <= 0 || $nombre === '' || trim($cuerpo) === '') {
    setMensaje('danger', 'El nombre y el contenido de la plantilla son obligatorios.');
    redirigir('mg_plantillas.php');
}

$documentoModel = new DocumentoModel($pdo);
if ($documentoModel->guardarPlantilla($id, $nombre, $cuerpo, (int)$_SESSION['id_usuario'])) {
    setMensaje('success', 'Plantilla guardada. El cambio aplica a los próximos documentos generados.');
} else {
    setMensaje('danger', 'No se pudo guardar la plantilla.');
}
redirigir('mg_plantillas.php');