<?php
// =========================================================
// CONTROLADOR: VER/IMPRIMIR DOCUMENTO MG (mg_documento_ver.php)
// ---------------------------------------------------------
// HU-027. Muestra un documento generado (snapshot) en formato
// A4 profesional listo para imprimir. Nunca re-renderiza: usa
// el contenido guardado en el momento de la emisión.
// Permiso: gestionar_documentos_mg (o ver_expediente_mg).
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/DocumentoModel.php';

if (!tienePermiso('gestionar_documentos_mg') && !tienePermiso('ver_expediente_mg')) {
    requerirPermiso('gestionar_documentos_mg');
}

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirigir('mg_expedientes.php');
}

$documentoModel = new DocumentoModel($pdo);
$documento = $documentoModel->obtenerPorId($id);
if (!$documento) {
    setMensaje('danger', 'El documento no existe.');
    redirigir('mg_expedientes.php');
}

$tituloPagina = 'Documento ' . ($documento['numero_correlativo'] ?? $documento['tipo']);
require_once __DIR__ . '/../views/mg/documento_ver.php';