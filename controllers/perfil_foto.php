<?php
// =========================================================
// CONTROLADOR: FOTO DE PERFIL (perfil_foto.php)
// ---------------------------------------------------------
// Disponible para cualquier rol autenticado. Permite subir
// (POST multipart), reemplazar o quitar (?accion=quitar) la
// fotografía de perfil del usuario. La imagen se guarda en
// assets/uploads/perfiles/ con un nombre único seguro y se
// registra su ruta web en usuarios.foto_perfil.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

$usuarioModel = new UsuarioModel($pdo);
$idUsuario = $_SESSION['id_usuario'] ?? 0;

// Carpeta física y URL pública de las fotografías
$dirPerfiles = __DIR__ . '/../assets/uploads/perfiles';
$urlBase = '/assets/uploads/perfiles/';

// ===== Quitar la foto actual =====
if (($_GET['accion'] ?? '') === 'quitar') {
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('perfil.php');
    }

    $usuario = $usuarioModel->obtenerPorId($idUsuario);
    if (!empty($usuario['foto_perfil'])) {
        // Solo se borra el archivo físico si realmente vive en nuestra carpeta
        $rutaLocal = $dirPerfiles . DIRECTORY_SEPARATOR . basename($usuario['foto_perfil']);
        if (is_file($rutaLocal)) {
            @unlink($rutaLocal);
        }
        $usuarioModel->actualizarFotoPerfil($idUsuario, null);
        $_SESSION['foto'] = null;
        setMensaje('success', 'Tu foto de perfil fue eliminada.');
    }
    redirigir('perfil.php');
}

// ===== Subir una nueva foto (POST multipart) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('perfil.php');
    }

    // Debe existir un archivo realmente subido
    if (!isset($_FILES['foto_perfil']) || $_FILES['foto_perfil']['error'] !== UPLOAD_ERR_OK) {
        setMensaje('danger', 'No se recibió la imagen. Verifica que sea JPG, PNG o WEBP.');
        redirigir('perfil.php');
    }

    $archivo = $_FILES['foto_perfil'];
    $tamanoMaximo = 2 * 1024 * 1024; // 2 MB

    // getimagesize() analiza la firma real del archivo (no confía en la extensión)
    $info = @getimagesize($archivo['tmp_name']);
    if ($info === false) {
        setMensaje('danger', 'El archivo no es una imagen válida.');
        redirigir('perfil.php');
    }

    $tiposPermitidos = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];
    if (!isset($tiposPermitidos[$info[2]])) {
        setMensaje('danger', 'Formato no permitido. Usa imágenes JPG, PNG o WEBP.');
        redirigir('perfil.php');
    }
    if ($archivo['size'] > $tamanoMaximo) {
        setMensaje('danger', 'La imagen supera el tamaño máximo de 2 MB.');
        redirigir('perfil.php');
    }

    // Nombre único: no se puede adivinar ni colisiona con otras subidas
    $nombreNuevo = 'perf_' . $idUsuario . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $tiposPermitidos[$info[2]];

    if (!is_dir($dirPerfiles)) {
        @mkdir($dirPerfiles, 0775, true);
    }
    $rutaDestino = $dirPerfiles . DIRECTORY_SEPARATOR . $nombreNuevo;

    if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
        setMensaje('danger', 'No se pudo guardar la imagen. Revisa los permisos de la carpeta de subidas.');
        redirigir('perfil.php');
    }

    // Se elimina la foto anterior (solo si pertenece a esta carpeta)
    $usuario = $usuarioModel->obtenerPorId($idUsuario);
    if (!empty($usuario['foto_perfil'])) {
        $archivoViejo = $dirPerfiles . DIRECTORY_SEPARATOR . basename($usuario['foto_perfil']);
        if (is_file($archivoViejo) && realpath($archivoViejo) !== realpath($rutaDestino)) {
            @unlink($archivoViejo);
        }
    }

    $rutaSistema = $urlBase . $nombreNuevo;
    $usuarioModel->actualizarFotoPerfil($idUsuario, $rutaSistema);
    $_SESSION['foto'] = $rutaSistema; // Refrescar el avatar visible en el navbar

    setMensaje('success', 'Tu foto de perfil fue actualizada.');
    redirigir('perfil.php');
}

// Cualquier otro acceso directo vuelve al perfil
redirigir('perfil.php');