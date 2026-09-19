<?php
// =========================================================
// Funciones auxiliares reutilizables del sistema
// =========================================================

// Inicia la sesión si aún no está activa (evita headers duplicados)
function iniciarSesion()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

// Redirección segura con exit
function redirigir($url)
{
    header('Location: ' . $url);
    exit;
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