<?php
// =========================================================
// CONTROLADOR: GUARDAR PARÁMETRO MG (parametros_guardar.php)
// ---------------------------------------------------------
// Actualiza el valor de un parámetro configurable (HU-020).
// POST-only con CSRF. Solo administrador y coordinador de MG.
// Los parámetros no editables (NOTA_MAX) se ignoran.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ParametroModel.php';

requerirPermiso('gestionar_parametros_mg');

$volver = 'parametros_mg.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$parametroModel = new ParametroModel($pdo);
$actualizados = 0;

foreach (($_POST['valor'] ?? []) as $idParametro => $valor) {
    $idParametro = (int)$idParametro;
    if ($idParametro <= 0) {
        continue;
    }
    $parametro = $parametroModel->obtenerPorId($idParametro);
    if (!$parametro || !(int)$parametro['es_editable']) {
        continue;
    }
    $valor = trim((string)$valor);
    if ((string)$valor === '' || !preg_match('/^\d+(\.\d+)?$/', $valor)) {
        setMensaje('danger', 'El parámetro ' . htmlspecialchars($parametro['clave']) . ' debe ser un número mayor a cero.');
        redirigir($volver);
    }
    if ($parametroModel->actualizar($idParametro, $valor)) {
        $actualizados++;
    }
}

if ($actualizados > 0) {
    setMensaje('success', 'Parámetros actualizados correctamente.');
} else {
    setMensaje('info', 'No se modificó ningún parámetro.');
}
redirigir($volver);