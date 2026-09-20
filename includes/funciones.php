<?php
// =========================================================
// Funciones auxiliares reutilizables del sistema
// =========================================================

// Inicia la sesión si aún no está activa (evita headers duplicados)
// y endurece la cookie de sesión: solo HTTP (no accesible por JS),
// SameSite=Lax (mitiga CSRF) y marca Secure cuando hay HTTPS.
function iniciarSesion()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// Redirección segura con exit
function redirigir($url)
{
    header('Location: ' . $url);
    exit;
}

// URL base del sistema ("" si la app está en la raíz del servidor,
// o "/subcarpeta" si se despliega en un subdirectorio). Se calcula
// comparando la carpeta raíz del proyecto con el DOCUMENT_ROOT.
function base_url()
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $docRoot = isset($_SERVER['DOCUMENT_ROOT'])
        ? rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']), '/')
        : '';
    $dir = str_replace('\\', '/', dirname(__DIR__)); // padre de includes/ = raíz del proyecto
    if ($docRoot !== '' && strpos($dir, $docRoot) === 0) {
        $base = substr($dir, strlen($docRoot));
    } else {
        $base = '';
    }
    return $base;
}

// Establece un mensaje flash (se muestra una sola vez)
function setMensaje($tipo, $texto)
{
    iniciarSesion();
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

// Obtiene (y elimina) el mensaje flash actual, si existe
function getMensaje()
{
    iniciarSesion();
    if (isset($_SESSION['flash'])) {
        $mensaje = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $mensaje;
    }
    return null;
}

// Normaliza texto simple de formulario (quita etiquetas)
function limpiarTexto($valor)
{
    return trim(strip_tags((string)($valor ?? '')));
}

// Pone en mayúscula la inicial de la primera palabra y de las palabras
// significativas, dejando en minúscula los conectores del medio (en, de, la...).
// Ejemplos: "redes" -> "Redes", "ingeneria en calculo" -> "Ingeneria en Calculo",
// "base de datos" -> "Base de Datos". Respeta tildes.
function capitalizarNombre($texto)
{
    $texto = trim((string)($texto ?? ''));
    if ($texto === '') {
        return $texto;
    }
    // Conectores/preposiciones/artículos que no llevan inicial mayúscula en medio del nombre
    $enlaces = [
        'a', 'al', 'de', 'del', 'en',
        'y', 'e', 'o', 'u',
        'la', 'las', 'el', 'los',
        'un', 'una', 'unos', 'unas',
        'para', 'por', 'con', 'sin', 'sobre',
    ];
    $palabras = preg_split('/\s+/u', $texto);
    foreach ($palabras as $i => $palabra) {
        $esEnlace = in_array(mb_strtolower($palabra, 'UTF-8'), $enlaces, true);
        if ($i === 0 || !$esEnlace) {
            $palabras[$i] = mb_strtoupper(mb_substr($palabra, 0, 1), 'UTF-8') . mb_substr($palabra, 1);
        }
    }
    return implode(' ', $palabras);
}

// Pone en mayúscula solo la PRIMERA letra, dejando el resto tal cual
// (ej: "redes de datos" -> "Redes de datos"). Respeta tildes.
function capitalizarInicial($texto)
{
    $texto = trim((string)($texto ?? ''));
    if ($texto === '') {
        return $texto;
    }
    return mb_strtoupper(mb_substr($texto, 0, 1), 'UTF-8') . mb_substr($texto, 1);
}

// Valida texto que solo debe contener letras (permite espacios, tildes, guiones y apóstrofes)
function validarLetras($texto)
{
    return preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s\'\-]+$/', $texto) === 1;
}

// Valida etiquetas/nombres alfanuméricos (permite signos comunes de agrupación)
function validarEtiqueta($texto)
{
    return preg_match('/^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s.,+:&()\/#\-_]+$/u', $texto) === 1;
}

// Valida una URL básica (requerida para modalidad virtual)
function validarUrl($url)
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// Iniciales para avatar circular (ej: "Juan Pérez" -> "JP")
function iniciales($nombre, $apellido)
{
    $iniNombre = mb_substr(trim($nombre), 0, 1);
    $iniApellido = mb_substr(trim($apellido), 0, 1);
    return strtoupper($iniNombre . $iniApellido);
}

// Convierte "14:30:00" a "14:30" de forma segura
function horaCorta($hora)
{
    if (empty($hora)) {
        return '';
    }
    return substr($hora, 0, 5);
}

// ---------------------------------------------------------
// PROTECCIÓN CSRF (Cross-Site Request Forgery)
// ---------------------------------------------------------
// Genera (y reutiliza) el token de seguridad de la sesión.
function generarTokenCsrf()
{
    iniciarSesion();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Devuelve el token listo para usar en una URL con &token=...
function tokenCsrfUrl()
{
    return urlencode(generarTokenCsrf());
}

// Devuelve un <input hidden> para incluir dentro de los formularios
function campoCsrf()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generarTokenCsrf(), ENT_QUOTES) . '">';
}

// Verifica que el token recibido (POST csrf_token o GET token) coincida
function verificarTokenCsrf()
{
    iniciarSesion();
    $token = $_POST['csrf_token'] ?? ($_REQUEST['token'] ?? '');
    return !empty($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

// ---------------------------------------------------------
// CONTROL DE ACCESO POR ROL
// ---------------------------------------------------------
// Verifica que el usuario en sesión tenga uno de los roles
// permitidos. Si no lo cumple, redirige según su rol real
// (o al login si no hay sesión) y detiene la ejecución.
function verificarRol($rolesPermitidos)
{
    iniciarSesion();
    $rol = $_SESSION['rol'] ?? '';
    if (isset($_SESSION['id_usuario']) && in_array($rol, (array)$rolesPermitidos, true)) {
        return true;
    }
    if (!isset($_SESSION['id_usuario']) || $rol === '') {
        header('Location: ' . base_url() . '/views/login/login.php');
        exit;
    }
    if ($rol === 'tutor') {
        header('Location: ' . base_url() . '/views/tutor/panel.php');
        exit;
    }
    header('Location: ' . base_url() . '/views/estudiante/panel.php');
    exit;
}