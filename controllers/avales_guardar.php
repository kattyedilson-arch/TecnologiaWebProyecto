<?php
// =========================================================
// CONTROLADOR: GUARDAR AVALES (avales_guardar.php)
// ---------------------------------------------------------
// Acciones del checklist: agregar, eliminar o cambiar estado
// (entregado/observado/pendiente) de un aval, siempre de una
// declaración aprobada. POST-only con CSRF. Requiere permiso
// de tramitación MG.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/DeclaracionModel.php';
require_once __DIR__ . '/../models/AvalModel.php';

const DIR_AVALES = __DIR__ . '/../assets/uploads/avales';
const URL_AVALES = '/assets/uploads/avales/';

requerirPermiso('tramitar_modalidad_mg');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir('mg_jurados.php');
}
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir('mg_jurados.php');
}

$accion = $_POST['accion'] ?? '';
$idDeclaracion = (int)($_POST['id_declaracion'] ?? 0);
$volver = 'mg_tribunal.php?id=' . $idDeclaracion;

$declaracionModel = new DeclaracionModel($pdo);
$declaracion = $idDeclaracion > 0 ? $declaracionModel->obtenerPorId($idDeclaracion) : null;
if (!$declaracion || $declaracion['estado'] !== 'aprobada') {
    setMensaje('danger', 'La declaración no existe o no está aprobada.');
    redirigir('mg_jurados.php');
}

$avalModel = new AvalModel($pdo);

try {
    if ($accion === 'agregar') {
        $nombre = limpiarTexto($_POST['nombre'] ?? '');
        $descripcion = limpiarTexto($_POST['descripcion'] ?? '');
        if ($nombre === '') {
            setMensaje('danger', 'El nombre del aval es obligatorio.');
            redirigir($volver);
        }
        $avalModel->agregarItem($idDeclaracion, $nombre, $descripcion);
        setMensaje('success', 'Aval agregado al checklist.');
    } elseif ($accion === 'eliminar') {
        $av = (int)($_POST['id_aval'] ?? 0);
        $avalModel->eliminarItem($av, $idDeclaracion);
        setMensaje('success', 'Aval retirado del checklist.');
    } elseif ($accion === 'estado') {
        $av = (int)($_POST['id_aval'] ?? 0);
        $estado = $_POST['estado'] ?? '';
        if (!in_array($estado, ['pendiente', 'entregado', 'observado'], true)) {
            setMensaje('danger', 'Estado de aval no válido.');
            redirigir($volver);
        }
        $observacion = limpiarTexto($_POST['observacion'] ?? '');
        $avalModel->actualizarEstado($av, $idDeclaracion, $estado, $observacion);

        // Del entregado a observado sin motivo no aplica; pero la observación se guarda
        if ($estado === 'entregado') {
            setMensaje('success', 'Aval marcado como entregado.');
        } elseif ($estado === 'observado') {
            setMensaje('warning', 'Aval observado: se devolvió al estudiante.');
        } else {
            setMensaje('success', 'El aval vuelve a estar pendiente.');
        }
    } elseif ($accion === 'adjuntar') {
        $av = (int)($_POST['id_aval'] ?? 0);

        if (!isset($_FILES['archivo_aval']) || $_FILES['archivo_aval']['error'] !== UPLOAD_ERR_OK) {
            setMensaje('danger', 'No se recibió el documento. Verifica que sea PDF o imagen.');
            redirigir($volver);
        }

        $archivoSubido = $_FILES['archivo_aval'];
        if ($archivoSubido['size'] > 5 * 1024 * 1024) {
            setMensaje('danger', 'El documento supera el tamaño máximo de 5 MB.');
            redirigir($volver);
        }

        // Validación por contenido real (no confía en la extensión)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($archivoSubido['tmp_name']);
        $extensiones = [
            'application/pdf'     => 'pdf',
            'image/jpeg'          => 'jpg',
            'image/png'           => 'png',
            'image/webp'          => 'webp',
        ];
        if (!isset($extensiones[$mime])) {
            setMensaje('danger', 'Formato no permitido. Usa PDF, JPG, PNG o WEBP.');
            redirigir($volver);
        }

        // Nombre único: seguro y sin colisiones
        $nombreNuevo = 'aval_' . $av . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extensiones[$mime];

        if (!is_dir(DIR_AVALES)) {
            @mkdir(DIR_AVALES, 0775, true);
        }
        if (!move_uploaded_file($archivoSubido['tmp_name'], DIR_AVALES . DIRECTORY_SEPARATOR . $nombreNuevo)) {
            setMensaje('danger', 'No se pudo guardar el documento. Revisa los permisos de la carpeta de subidas.');
            redirigir($volver);
        }

        // Se reemplaza un archivo previo, si existía en nuestra carpeta
        $avalActual = $avalModel->obtenerPorDeclaracion($idDeclaracion);
        foreach ($avalActual as $fila) {
            if ((int)$fila['id_aval'] === $av && !empty($fila['archivo'])) {
                $viejo = DIR_AVALES . DIRECTORY_SEPARATOR . basename($fila['archivo']);
                if (is_file($viejo) && realpath($viejo) !== realpath(DIR_AVALES . DIRECTORY_SEPARATOR . $nombreNuevo)) {
                    @unlink($viejo);
                }
            }
        }

        $nombreDescarga = trim($archivoSubido['name']) !== '' ? basename($archivoSubido['name']) : $nombreNuevo;
        $avalModel->adjuntarArchivo($av, $idDeclaracion, URL_AVALES . $nombreNuevo, $nombreDescarga);
        setMensaje('success', 'Documento adjuntado al aval.');
    } elseif ($accion === 'quitar_archivo') {
        $av = (int)($_POST['id_aval'] ?? 0);
        $avalActual = $avalModel->obtenerPorDeclaracion($idDeclaracion);
        foreach ($avalActual as $fila) {
            if ((int)$fila['id_aval'] === $av && !empty($fila['archivo'])) {
                $viejo = DIR_AVALES . DIRECTORY_SEPARATOR . basename($fila['archivo']);
                if (is_file($viejo)) {
                    @unlink($viejo);
                }
            }
        }
        $avalModel->quitarArchivo($av, $idDeclaracion);
        setMensaje('success', 'Documento retirado del aval.');
    } else {
        setMensaje('danger', 'Acción no válida.');
    }
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        setMensaje('danger', 'No se pudo guardar: ya existe un aval con ese nombre para esta declaración.');
    } else {
        setMensaje('danger', 'No se pudo actualizar el registro de avales.');
    }
}

redirigir($volver);