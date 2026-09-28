<?php
// =========================================================
// CONTROLADOR: GUARDAR EXPEDIENTE MG (mg_expediente_guardar.php)
// ---------------------------------------------------------
// HU-024. Procesa las transiciones de etapa (previa -> mg1 ->
// mg2 -> finalizado) y los cierres con resultado terminal
// (abandono / reprobado), que exigen nota/motivo y los registra
// solo Coordinación (RN-MG-22). POST-only con CSRF.
// Permiso: gestionar_etapas_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ExpedienteMgModel.php';
require_once __DIR__ . '/../models/EtapasExpedienteModel.php';

requerirPermiso('gestionar_etapas_mg');

$volver = 'mg_expedientes.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$id = (int)($_POST['id_declaracion'] ?? 0);
$accion = $_POST['accion'] ?? '';

if ($id <= 0) {
    redirigir($volver);
}
$volverFicha = 'mg_expediente.php?id=' . $id . '&tab=etapas';

$expedienteModel = new ExpedienteMgModel($pdo);
$etapasModel = new EtapasExpedienteModel($pdo);

$expediente = $expedienteModel->obtenerPorId($id);
if (!$expediente) {
    setMensaje('danger', 'El expediente no existe.');
    redirigir($volver);
}

$esAbierto = in_array($expediente['estado'], ['borrador', 'enviada', 'en_revision', 'aprobada'], true);
if (!$esAbierto) {
    setMensaje('danger', 'El expediente está cerrado; no se permiten más transiciones.');
    redirigir($volverFicha);
}

$usuario = (int)$_SESSION['id_usuario'];

if ($accion === 'etapa') {
    $destino = $_POST['etapa_destino'] ?? '';
    $observacion = limpiarTexto($_POST['observacion'] ?? '');
    $etapasValidas = ['previa', 'mg1', 'mg2', 'finalizado'];
    if (!in_array($destino, $etapasValidas, true)) {
        setMensaje('danger', 'La etapa de destino no es válida.');
        redirigir($volverFicha);
    }
    // Solo permite avanzar, nunca retroceder
    $orden = ['previa' => 0, 'mg1' => 1, 'mg2' => 2, 'finalizado' => 3];
    if (($orden[$destino] ?? 0) <= ($orden[$expediente['etapa_actual'] ?? 'previa'] ?? 0)) {
        setMensaje('danger', 'No se puede retroceder a una etapa anterior.');
        redirigir($volverFicha);
    }

    if ($etapasModel->transicionar($id, $destino, null, $observacion, $usuario)) {
        setMensaje('success', 'Etapa actualizada a ' . strtoupper($destino) . '.');
    } else {
        setMensaje('danger', 'No se pudo registrar la transición de etapa.');
    }
    redirigir($volverFicha);
}

if ($accion === 'cerrar') {
    $tipoCierre = $_POST['tipo_cierre'] ?? '';
    $motivo = limpiarTexto($_POST['motivo'] ?? '');
    // RN-MG-22: el cierre terminal exige nota/motivo
    if ($motivo === '') {
        setMensaje('danger', 'El motivo del cierre es obligatorio (RN-MG-22).');
        redirigir($volverFicha);
    }

    if (!in_array($tipoCierre, ['abandono', 'reprobado'], true)) {
        setMensaje('danger', 'El tipo de cierre no es válido.');
        redirigir($volverFicha);
    }

    if ($etapasModel->cerrarExpediente($id, $tipoCierre, $tipoCierre, $motivo, $usuario)) {
        setMensaje('success', 'Expediente marcado como ' . $tipoCierre . '.');
    } else {
        setMensaje('danger', 'No se pudo actualizar el estado del expediente.');
    }
    redirigir($volverFicha);
}

setMensaje('danger', 'Acción no reconocida.');
redirigir($volverFicha);