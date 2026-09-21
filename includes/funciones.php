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

// Valida que un nombre (carrera/materia) parezca real y no sea texto aleatorio:
// debe incluir al menos una vocal, tener por lo menos 3 letras distintas
// y no repetir la misma letra 4+ veces seguidas (p. ej. "hhhh", "aaaaaa").
function validarNombreLogico($texto)
{
    if (preg_match('/[aeiouáéíóúü]/i', $texto) !== 1) {
        return false;
    }
    if (preg_match('/(.)\1{3}/u', $texto) === 1) {
        return false;
    }
    $letras = array_unique(preg_split('//u', $texto, -1, PREG_SPLIT_NO_EMPTY));
    return count($letras) >= 3;
}

// Formatea un nombre con formato de título: primera letra de cada palabra
// en mayúscula y conectores en minúscula ("Ingenieria de Sistemas").
function formatearNombre($texto)
{
    $texto = mb_strtolower(trim((string)$texto), 'UTF-8');
    $conectores = ['de', 'la', 'el', 'los', 'las', 'del', 'en', 'y', 'a', 'e', 'o', 'u', 'con', 'al', 'para', 'por'];
    $palabras = preg_split('/\s+/u', $texto);
    foreach ($palabras as $i => $palabra) {
        $baja = mb_strtolower($palabra, 'UTF-8');
        if ($i > 0 && in_array($baja, $conectores, true)) {
            continue;
        }
        $palabras[$i] = mb_strtoupper(mb_substr($palabra, 0, 1), 'UTF-8') . mb_substr($palabra, 1);
    }
    return implode(' ', $palabras);
}

// Corrige y formatea un nombre de carrera/materia: repara typos
// frecuentes ("shistemas" -> "sistemas"), corrige palabras casi-idénticas
// a términos académicos (distancia <= 1) y aplica formato de título.
function corregirNombre($texto)
{
    $texto = trim((string)$texto);
    if ($texto === '') {
        return '';
    }

    $mapa = [
        'shistemas'   => 'sistemas',
        'shitemas'    => 'sistemas',
        'sitemas'     => 'sistemas',
        'sistmas'     => 'sistemas',
        'istemas'     => 'sistemas',
        'ingeneria'   => 'ingenieria',
        'ingeniria'   => 'ingenieria',
        'telecomunicacion' => 'telecomunicaciones',
    ];

    $diccionario = [
        'sistemas', 'informatica', 'ingenieria', 'telecomunicaciones', 'electronica',
        'mecanica', 'electricas', 'redes', 'psicologia', 'comercial', 'industrial',
        'civil', 'programacion', 'tecnologia', 'software', 'datos', 'contaduria',
        'derecho', 'medicina', 'enfermeria', 'educacion', 'matematicas', 'fisica',
        'biologia', 'arquitectura', 'administracion', 'marketing', 'recursos',
        'humanos', 'ambiental', 'gestion', 'bases', 'web',
    ];

    $palabras = preg_split('/\s+/u', $texto);
    foreach ($palabras as $i => $palabra) {
        $baja = mb_strtolower($palabra, 'UTF-8');
        if (isset($mapa[$baja])) {
            $palabras[$i] = $mapa[$baja];
            continue;
        }
        if (mb_strlen($baja) < 4) {
            continue;
        }
        $mejor = null;
        $distancia = null;
        foreach ($diccionario as $candidata) {
            $d = levenshtein($baja, $candidata);
            if ($d <= 1 && ($distancia === null || $d < $distancia)) {
                $mejor = $candidata;
                $distancia = $d;
            } elseif ($d <= 1 && $d === $distancia && $mejor !== $candidata) {
                $mejor = null; // empate entre candidatas: no adivinar
            }
        }
        if ($mejor !== null) {
            $palabras[$i] = $mejor;
        }
    }
    return formatearNombre(implode(' ', $palabras));
}

// Devuelve la lista global de carreras reales (sugerencias y validación)
function obtenerCarrerasGlobales()
{
    static $lista = null;
    if ($lista === null) {
        $lista = require __DIR__ . '/carreras_globales.php';
    }
    return $lista;
}

// Normaliza un texto para comparaciones (minúsculas, sin tildes, un solo espacio)
function normalizarComparacion($texto)
{
    $texto = mb_strtolower(trim((string)$texto), 'UTF-8');
    $texto = strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
        'ü' => 'u', 'ñ' => 'n',
    ]);
    return trim((string)preg_replace('/\s+/u', ' ', $texto));
}

// Devuelve el nombre canónico (tal como está en la lista global) de una
// carrera, o null si el nombre no pertenece a la lista. La comparación
// ignora mayúsculas, tildes y espacios extra.
function buscarCarreraGlobalExacta($nombre)
{
    $buscar = normalizarComparacion($nombre);
    foreach (obtenerCarrerasGlobales() as $carrera) {
        if (normalizarComparacion($carrera) === $buscar) {
            return $carrera;
        }
    }
    return null;
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