<?php
// =========================================================
// CONTROLADOR: FICHA DE EXPEDIENTE MG (mg_expediente.php)
// ---------------------------------------------------------
// HU-024. Vista de un expediente con su información, etapa
// actual, historial de etapas y del tutor, documentos emitidos.
// También sirve de aterrizaje para las acciones de asignación
// de tutor (HU-025), cambio/renuncia (HU-026) y carta (HU-027),
// que viven en controladores POST separados.
// Permiso: ver_expediente_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ExpedienteMgModel.php';
require_once __DIR__ . '/../models/EtapasExpedienteModel.php';
require_once __DIR__ . '/../models/AsignacionTutorModel.php';
require_once __DIR__ . '/../models/DocumentoModel.php';
require_once __DIR__ . '/../models/ParametroModel.php';

requerirPermiso('ver_expediente_mg');

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    redirigir('mg_expedientes.php');
}

$expedienteModel = new ExpedienteMgModel($pdo);
$etapasModel = new EtapasExpedienteModel($pdo);
$asignacionModel = new AsignacionTutorModel($pdo);
$documentoModel = new DocumentoModel($pdo);

$expediente = $expedienteModel->obtenerPorId($id);
if (!$expediente) {
    setMensaje('danger', 'El expediente seleccionado no existe.');
    redirigir('mg_expedientes.php');
}

$historialEtapas = $etapasModel->obtenerHistorial($id);
$historialTutor = $asignacionModel->obtenerHistorial($id);
$asignacionVigente = $asignacionModel->obtenerVigente($id);
$documentos = $documentoModel->obtenerPorDeclaracion($id);

$puedeGestionar = tienePermiso('gestionar_expedientes_mg');
$puedeEtapas = tienePermiso('gestionar_etapas_mg');
$puedeAsignar = tienePermiso('asignar_tutor_mg');
$puedeCambiarTutor = tienePermiso('cambiar_tutor_mg');
$puedeDocumentos = tienePermiso('gestionar_documentos_mg');

$tutoresDisponibles = $asignacionModel->listarTutoresConCarga();
$parametroModel = new ParametroModel($pdo);
$tutorCargaRecomendada = (int)$parametroModel->obtener('tutor_carga_recomendada', 3);

$tituloPagina = 'Expediente #' . $id;
require_once __DIR__ . '/../views/mg/expediente.php';