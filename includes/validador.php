<?php
// =========================================================
// VALIDADOR COMÚN (includes/validador.php)
// ---------------------------------------------------------
// Reglas de validación de servidor reutilizables por todos los
// controllers. Este archivo se incluye desde includes/funciones.php
// para que los validadores existan en todos los flujos.
//
// Reglas básicas (migradas desde funciones.php):
//   validarLetras, validarEtiqueta, validarNombreLogico, validarUrl
//
// API unificada para validar un formulario:
//   $errores = validarFormulario($_POST, [
//       'nombre' => ['requerido' => true, 'etiqueta' => true, 'min' => 5, 'max' => 150],
//       'correo' => ['requerido' => true, 'correo' => true],
//       'edad'   => ['entero' => true, 'min' => 1, 'max' => 120],
//   ]);
// $errores es un array clave => mensaje (vacío si todo pasó).
// =========================================================

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

// Valida una URL básica (requerida para modalidad virtual)
function validarUrl($url)
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// Valida un correo electrónico en formato estándar
function validarCorreo($correo)
{
    return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
}

// Valida que un valor sea un entero (acepta string numérico)
function validarEntero($valor)
{
    return is_numeric($valor) && (float)$valor === (float)(int)$valor;
}

// Valida una fecha en formato AAAA-MM-DD y que sea real (p. ej. rechaza 2026-02-31)
function validarFecha($fecha)
{
    if (!is_string($fecha) || preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
        return false;
    }
    [$anio, $mes, $dia] = array_map('intval', explode('-', $fecha));
    return checkdate($mes, $dia, $anio);
}

// Valida una hora HH:MM (formato 24 h)
function validarHora($hora)
{
    if (!is_string($hora) || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $hora) !== 1) {
        return false;
    }
    [$h, $m] = array_map('intval', explode(':', $hora));
    return $h >= 0 && $h <= 23 && $m >= 0 && $m <= 59;
}

// ------------------------------------------------------------------
// API unificada: valida un formulario contra un esquema de reglas.
// Reglas soportadas por campo:
//   requerido  => el valor no puede estar vacío ni ser solo espacios
//   etiqueta   => validarEtiqueta()
//   letras     => validarLetras()
//   logico     => validarNombreLogico()
//   correo     => validarCorreo()
//   url        => validarUrl()
//   entero     => validarEntero()
//   min/max    => longitud (string) o valor (numérico) mínimo/máximo
//   fecha      => validarFecha()
//   hora       => validarHora()
//   igual      => el valor debe coincidir con otra clave del formulario
// ------------------------------------------------------------------
function validarFormulario(array $datos, array $esquema)
{
    $errores = [];
    foreach ($esquema as $campo => $reglas) {
        $valor = $datos[$campo] ?? null;
        $valorStr = trim((string)($valor ?? ''));

        if (!empty($reglas['requerido']) && $valorStr === '') {
            $errores[$campo] = reglaMensaje($campo, 'requerido');
            continue;
        }
        if ($valorStr === '') {
            continue; // opcional y vacío: pasa
        }

        foreach ($reglas as $regla => $opt) {
            switch ($regla) {
                case 'etiqueta':
                    if (!validarEtiqueta($valor)) {
                        $errores[$campo] = reglaMensaje($campo, 'etiqueta');
                    }
                    break;
                case 'letras':
                    if (!validarLetras($valor)) {
                        $errores[$campo] = reglaMensaje($campo, 'letras');
                    }
                    break;
                case 'logico':
                    if (!validarNombreLogico($valor)) {
                        $errores[$campo] = reglaMensaje($campo, 'logico');
                    }
                    break;
                case 'correo':
                    if (!validarCorreo($valor)) {
                        $errores[$campo] = reglaMensaje($campo, 'correo');
                    }
                    break;
                case 'url':
                    if (!validarUrl($valor)) {
                        $errores[$campo] = reglaMensaje($campo, 'url');
                    }
                    break;
                case 'entero':
                    if (!validarEntero($valor)) {
                        $errores[$campo] = reglaMensaje($campo, 'entero');
                    }
                    break;
                case 'fecha':
                    if (!validarFecha($valorStr)) {
                        $errores[$campo] = reglaMensaje($campo, 'fecha');
                    }
                    break;
                case 'hora':
                    if (!validarHora($valorStr)) {
                        $errores[$campo] = reglaMensaje($campo, 'hora');
                    }
                    break;
                case 'min':
                    if (is_numeric($valor) && (float)$valor < (float)$opt) {
                        $errores[$campo] = reglaMensaje($campo, 'min', $opt);
                    } elseif (mb_strlen($valorStr) < (int)$opt) {
                        $errores[$campo] = reglaMensaje($campo, 'min', $opt);
                    }
                    break;
                case 'max':
                    if (is_numeric($valor) && (float)$valor > (float)$opt) {
                        $errores[$campo] = reglaMensaje($campo, 'max', $opt);
                    } elseif (mb_strlen($valorStr) > (int)$opt) {
                        $errores[$campo] = reglaMensaje($campo, 'max', $opt);
                    }
                    break;
                case 'igual':
                    if ($valorStr !== trim((string)($datos[$opt] ?? ''))) {
                        $errores[$campo] = reglaMensaje($campo, 'igual', $opt);
                    }
                    break;
            }
            if (isset($errores[$campo])) {
                break; // un solo error por campo
            }
        }
    }
    return $errores;
}

// Etiqueta legible de un campo para los mensajes de error
function etiquetaCampo($campo)
{
    return ucfirst(str_replace(['_', '-'], ' ', $campo));
}

// Construye el mensaje de error en español para una regla
function reglaMensaje($campo, $regla, $opt = null)
{
    $etiqueta = etiquetaCampo($campo);
    switch ($regla) {
        case 'requerido': return "El campo $etiqueta es obligatorio.";
        case 'etiqueta':  return "El campo $etiqueta contiene caracteres no permitidos.";
        case 'letras':    return "El campo $etiqueta solo debe contener letras.";
        case 'logico':    return "El campo $etiqueta no parece un valor real.";
        case 'correo':    return "El campo $etiqueta no es un correo válido.";
        case 'url':       return "El campo $etiqueta no es una URL válida.";
        case 'entero':    return "El campo $etiqueta debe ser un número entero.";
        case 'fecha':     return "El campo $etiqueta no es una fecha válida.";
        case 'hora':      return "El campo $etiqueta no es una hora válida.";
        case 'min':       return "El campo $etiqueta debe tener al menos $opt.";
        case 'max':       return "El campo $etiqueta no puede superar $opt.";
        case 'igual':     return "El campo $etiqueta no coincide.";
        default:          return "El campo $etiqueta es inválido.";
    }
}