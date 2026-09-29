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

// Resuelve la ruta interna a la que se vuelve tras una acción POST.
// El Referer del navegador llega como URL absoluta ("http://host/ruta"),
// así que se toma solo la ruta interna; cualquier destino externo o
// malformado cae en la ruta por defecto (evita open redirect).
function destinoSeguro($referer, $porDefecto = '/controllers/notificaciones_listar.php')
{
    $porDefecto = '/' . ltrim((string)$porDefecto, '/');
    $referer = trim((string)$referer);
    if ($referer === '') {
        return $porDefecto;
    }

    // Ya viene como ruta interna desde la raíz ("/controllers/x.php")
    if (str_starts_with($referer, '/') && !str_starts_with($referer, '//') && !str_starts_with($referer, '/\\')) {
        return $referer;
    }

    // URL absoluta: se conserva únicamente la ruta y su query string
    $ruta = parse_url($referer, PHP_URL_PATH);
    if (empty($ruta) || !str_starts_with($ruta, '/') || str_starts_with($ruta, '//')) {
        return $porDefecto;
    }
    $query = parse_url($referer, PHP_URL_QUERY);
    return $ruta . ($query ? '?' . $query : '');
}

// Establece un mensaje flash (se muestra una sola vez)
function setMensaje($tipo, $texto)
{
    iniciarSesion();
    $_SESSION['flash'] = ['tipo' => $tipo, 'texto' => $texto];
}

// Establece varios mensajes de error en un solo flash (se muestran todos).
// Antes se llamaba a setMensaje() en bucle y cada iteración sobrescribía la
// anterior, por lo que el usuario solo veía el último error.
function setMensajes($tipo, array $textos)
{
    iniciarSesion();
    $textos = array_values(array_filter(array_map('strval', $textos), function ($t) {
        return trim($t) !== '';
    }));
    if (empty($textos)) {
        return;
    }
    $_SESSION['flash'] = [
        'tipo'   => $tipo,
        'texto'  => implode(' ', $textos),
        'textos' => $textos,
    ];
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

// Normaliza un código corto (AAAA-N, MOD-PG, C-2026-1) para que la validación
// por expresión regular no falle por caracteres invisibles o guiones
// tipográficos que el usuario pega desde Word/Docs: "2026–1" (en dash) o
// "2026 - 1" se convierten en "2026-1". Para nombres con espacios se debe
// seguir usando limpiarTexto().
function normalizarCodigo($valor)
{
    $texto = strtr((string)($valor ?? ''), [
        // Guiones y rayas tipográficos -> guion ASCII
        "\u{2010}" => '-', "\u{2011}" => '-', "\u{2012}" => '-',
        "\u{2013}" => '-', "\u{2014}" => '-', "\u{2015}" => '-',
        "\u{2212}" => '-', "\u{FE58}" => '-', "\u{FF0D}" => '-',
        // Espacios no separables -> espacio
        "\u{00A0}" => ' ', "\u{202F}" => ' ', "\u{2007}" => ' ', "\u{2009}" => ' ',
        // Zero-width y soft hyphen (habitual al copiar desde PDF): se eliminan
        "\u{200B}" => '', "\u{200C}" => '', "\u{200D}" => '', "\u{FEFF}" => '',
        "\u{00AD}" => '',
    ]);
    // Quita el resto de espacios y caracteres de control (bytes ASCII).
    $texto = preg_replace('/[\x00-\x20\x7F]/', '', $texto);
    // Colapsa guiones repetidos ("C--2026-1" -> "C-2026-1").
    $texto = preg_replace('/-{2,}/', '-', $texto);
    return trim((string)$texto);
}

// Devuelve el valor recibido marcando los caracteres invisibles como
// [nbsp], [guion], [zws]... para que en un mensaje de error se vea qué
// carácter concreto provocó el rechazo.
function valorVisible($valor)
{
    $texto = strtr((string)($valor ?? ''), [
        "\u{00A0}" => '[nbsp]', "\u{202F}" => '[nnbsp]', "\u{2007}" => '[figsp]',
        "\u{2009}" => '[thin]', "\u{200B}" => '[zws]',  "\u{200C}" => '[zwnj]',
        "\u{200D}" => '[zwj]',  "\u{FEFF}" => '[bom]',
        "\u{2010}" => '[guion]', "\u{2011}" => '[guion]', "\u{2012}" => '[guion]',
        "\u{2013}" => '[guion]', "\u{2014}" => '[guion]', "\u{2015}" => '[guion]',
        "\u{2212}" => '[menos]', "\u{FE58}" => '[guion]', "\u{FF0D}" => '[guion]',
    ]);
    $texto = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '[ctrl]', $texto);
    return mb_strimwidth($texto, 0, 40, '…', 'UTF-8');
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