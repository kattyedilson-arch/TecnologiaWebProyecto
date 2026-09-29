<?php
// =========================================================
// CONTROLADOR: IMPORTAR PADRÓN MG (mg_importar.php)
// ---------------------------------------------------------
// HU-023. Subida de CSV del padrón de Modalidades de Grado.
// Flujo en dos pasos:
//   1) POST con archivo -> se lee en memoria, se previsualizan
//      las filas (ok/advertencia/error/pendiente_cuenta/omitida)
//      y el resultado se conserva en sesión.
//   2) GET ?confirmar -> el coordinador confirma y solo entonces
//      se crean los expedientes (transacción en el modelo).
// Permiso: importar_padron_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ImportacionMgModel.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

requerirPermiso('importar_padron_mg');

$importacionModel = new ImportacionMgModel($pdo);

// ---------- PASO 2: confirmar la importación previsualizada ----------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'confirmar') {
        if (!verificarTokenCsrf()) {
            setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
            redirigir('mg_importar.php');
        }
        if (empty($_SESSION['importacion_preview']) || !isset($_SESSION['importacion_archivo'])) {
            setMensaje('danger', 'No hay una previsualización pendiente de confirmar.');
            redirigir('mg_importar.php');
        }

        $filas = $_SESSION['importacion_preview'];
        $nombreArchivo = $_SESSION['importacion_archivo'];
        unset($_SESSION['importacion_preview'], $_SESSION['importacion_archivo']);

        $resultado = $importacionModel->confirmar($nombreArchivo, $filas, (int)$_SESSION['id_usuario']);
        if ($resultado['ok']) {
            setMensaje('success', $resultado['mensaje']);
        } else {
            setMensaje('danger', $resultado['mensaje']);
        }
        redirigir('mg_importar.php');
    }

    if ($_POST['accion'] === 'cancelar') {
        unset($_SESSION['importacion_preview'], $_SESSION['importacion_archivo']);
        setMensaje('info', 'Previsualización descartada.');
        redirigir('mg_importar.php');
    }
}

// ---------- PASO 1: subir y previsualizar ----------
$previewFilas = null;
$previewArchivo = null;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && !isset($_POST['accion'])) {
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('mg_importar.php');
    }
    $parse = $importacionModel->parsear($_FILES['archivo'] ?? []);
    if ($parse['error']) {
        setMensaje('danger', $parse['error']);
        redirigir('mg_importar.php');
    }
    $filasCrudas = $parse['datos']['filas'];
    if (empty($filasCrudas)) {
        setMensaje('danger', 'El CSV no contiene filas de datos (solo la cabecera o vacío).');
        redirigir('mg_importar.php');
    }
    $previewFilas = $importacionModel->previsualizar($filasCrudas);
    $_SESSION['importacion_preview'] = $previewFilas;
    $_SESSION['importacion_archivo'] = $_FILES['archivo']['name'];
    $previewArchivo = $_FILES['archivo']['name'];
}

$importaciones = $importacionModel->obtenerImportaciones();

// Detalle de una importación ya confirmada
$detalleImportacion = null;
$detalleFilas = [];
$verImportacion = (int)($_GET['ver'] ?? 0);
if ($verImportacion > 0) {
    $detalleImportacion = null;
    foreach ($importaciones as $imp) {
        if ((int)$imp['id_importacion'] === $verImportacion) {
            $detalleImportacion = $imp;
            break;
        }
    }
    if ($detalleImportacion) {
        $detalleFilas = $importacionModel->obtenerDetalle($verImportacion);
    }
}

$tituloPagina = 'Importar padrón MG';

if (isset($_GET['confirmar']) && !empty($_SESSION['importacion_preview'])) {
    $previewFilas = $_SESSION['importacion_preview'];
    $previewArchivo = $_SESSION['importacion_archivo'] ?? '';
}

require_once __DIR__ . '/../views/mg/importar.php';