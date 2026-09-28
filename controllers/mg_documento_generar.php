<?php
// =========================================================
// CONTROLADOR: GENERAR DOCUMENTO MG (mg_documento_generar.php)
// ---------------------------------------------------------
// HU-027. Emite la carta de asignación de tutor a partir de la
// plantilla editable. Obtiene el correlativo transaccional
// (tipo+año), renderiza las {{variables}} escapadas y guarda el
// snapshot en documentos_generados para reimpresión fiel.
// POST-only con CSRF. Permiso: gestionar_documentos_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ExpedienteMgModel.php';
require_once __DIR__ . '/../models/AsignacionTutorModel.php';
require_once __DIR__ . '/../models/DocumentoModel.php';

requerirPermiso('gestionar_documentos_mg');

$volver = 'mg_expedientes.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$id = (int)($_POST['id_declaracion'] ?? 0);
$tipo = $_POST['tipo'] ?? 'CARTA_ASIGNACION_TUTOR';
$tiposPermitidos = ['CARTA_ASIGNACION_TUTOR'];
if ($id <= 0 || !in_array($tipo, $tiposPermitidos, true)) {
    redirigir($volver);
}
$volverFicha = 'mg_expediente.php?id=' . $id . '&tab=documentos';

$expedienteModel = new ExpedienteMgModel($pdo);
$asignacionModel = new AsignacionTutorModel($pdo);
$documentoModel = new DocumentoModel($pdo);

$expediente = $expedienteModel->obtenerPorId($id);
if (!$expediente) {
    setMensaje('danger', 'El expediente no existe.');
    redirigir($volver);
}
$asignacion = $asignacionModel->obtenerVigente($id);
if (!$asignacion || !$asignacion['tutor_nombre']) {
    setMensaje('danger', 'Para generar la carta el expediente debe tener un tutor vigente.');
    redirigir($volverFicha);
}

try {
    $plantilla = $documentoModel->obtenerPlantillaPorCodigo($tipo);
    if (!$plantilla) {
        setMensaje('danger', 'La plantilla del documento no está disponible.');
        redirigir($volverFicha);
    }

    $variables = [
        'fecha_larga'           => fechaLarga(),
        'estudiante_nombre'     => trim($expediente['estudiante_nombre'] . ' ' . $expediente['estudiante_apellido']),
        'registro_universitario'=> $expediente['registro_universitario'],
        'carrera'               => $expediente['nombre_carrera'] ?: '—',
        'referencia_decanatura' => $asignacion['referencia_decanatura'] ?: '(s/n)',
        'tutor_nombre'          => trim($asignacion['tutor_nombre'] . ' ' . $asignacion['tutor_apellido']),
        'modalidad'             => $expediente['modalidad_nombre'],
        'cohorte'               => $expediente['cohorte_codigo'] ?: '—',
        'tema'                  => $expediente['titulo_proyecto'] ?: '(a definir)',
        'responsable_nombre'    => trim(($_SESSION['nombre'] ?? 'Coordinación') . ' ' . ($_SESSION['apellido'] ?? '')),
    ];

    $correlativo = $documentoModel->siguienteCorrelativo($tipo, (int)date('Y'));
    if ($correlativo === '') {
        setMensaje('danger', 'No se pudo obtener un correlativo para el documento.');
        redirigir($volverFicha);
    }
    $variables['numero_carta'] = $correlativo;

    $contenido = $documentoModel->render($plantilla['cuerpo_html'], $variables);

    $idDocumento = $documentoModel->generar([
        'id_plantilla'       => (int)$plantilla['id_plantilla'],
        'tipo'               => $tipo,
        'id_declaracion'     => $id,
        'destinatario'       => $variables['estudiante_nombre'],
        'numero_correlativo' => $correlativo,
        'contenido_snapshot' => $contenido,
        'generado_por'       => (int)$_SESSION['id_usuario'],
    ]);
    if (!$idDocumento) {
        setMensaje('danger', 'No se pudo guardar el documento generado.');
        redirigir($volverFicha);
    }

    // Vincula el número de la carta a la asignación vigente
    $asignacionModel->registrarCarta((int)$asignacion['id_asignacion'], $correlativo);

    setMensaje('success', 'Documento generado: ' . $correlativo . '.');
    redirigir('mg_documento_ver.php?id=' . $idDocumento);
} catch (PDOException $e) {
    setMensaje('danger', 'No se pudo generar el documento.');
    redirigir($volverFicha);
}