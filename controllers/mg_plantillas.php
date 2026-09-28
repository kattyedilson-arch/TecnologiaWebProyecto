<?php
// =========================================================
// CONTROLADOR: PLANTILLAS DE DOCUMENTOS MG (mg_plantillas.php)
// ---------------------------------------------------------
// HU-027. Lista las plantillas de documentos del módulo y
// permite editar su contenido HTML (con {{variables}}). Cambiar
// la plantilla NO requiere tocar código. Permiso:
// gestionar_plantillas_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/DocumentoModel.php';

requerirPermiso('gestionar_plantillas_mg');

$documentoModel = new DocumentoModel($pdo);

$plantillas = $documentoModel->listarPlantillas();
$plantillaEditar = null;
$editarId = (int)($_GET['editar'] ?? 0);
if ($editarId > 0) {
    foreach ($plantillas as $p) {
        if ((int)$p['id_plantilla'] === $editarId) {
            $plantillaEditar = $p;
            break;
        }
    }
}

$tituloPagina = 'Plantillas de documentos MG';
require_once __DIR__ . '/../views/mg/plantillas.php';