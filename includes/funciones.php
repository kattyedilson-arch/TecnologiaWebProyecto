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

// Escapa una cadena para salida segura en HTML (alias corto de htmlspecialchars).
// Uso: e($variable) en TODAS las salidas dinámicas.
function e($valor)
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

// Reglas de validación de servidor (validarLetras, validarEtiqueta,
// validarNombreLogico, validarUrl, validarFormulario, ...)
require_once __DIR__ . '/validador.php';

// Formatea un nombre con formato de título: primera letra de cada palabra
// en mayúscula y conectores en minúscula ("Ingeniería de Sistemas").
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

// Iniciales para avatar circular (ej: "Juan Pérez" -> "JP")
function iniciales($nombre, $apellido)
{
    $iniNombre = mb_substr(trim($nombre), 0, 1);
    $iniApellido = mb_substr(trim($apellido), 0, 1);
    return strtoupper($iniNombre . $iniApellido);
}

// Fecha en formato "Cochabamba, 28 de septiembre de 2026"
function fechaLarga($fecha = null)
{
    $ts = $fecha ? strtotime($fecha) : time();
    $dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
    $meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio',
              'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
    return $dias[(int)date('w', $ts)] . ', ' . (int)date('j', $ts) . ' de '
         . $meses[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
}

// Renderiza un avatar circular: <img> si hay foto de perfil o, como
// respaldo, una imagen por defecto (y las iniciales como alt). $clase
// puede ser "avatar-md", "avatar-lg", etc. $extra permite estilos
// inline (p. ej. degradado).
function avatarHTML($foto, $inicialesTexto, $clase = 'avatar-md', $extra = '')
{
    $estilo = $extra !== '' ? ' style="' . htmlspecialchars($extra, ENT_QUOTES) . '"' : '';
    if (!empty($foto)) {
        return '<span class="' . trim($clase) . ' avatar-foto"' . $estilo . '><img src="' . htmlspecialchars($foto, ENT_QUOTES) . '" alt="Foto de perfil"></span>';
    }
    return '<span class="' . trim($clase) . ' avatar-foto"' . $estilo . '><img src="/assets/img/avatar-default.svg" alt="' . htmlspecialchars($inicialesTexto) . '"></span>';
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