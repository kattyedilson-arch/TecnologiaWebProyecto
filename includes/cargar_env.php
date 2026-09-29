<?php
// =========================================================
// CARGA DE VARIABLES DE ENTORNO (.env) (cargar_env.php)
// ---------------------------------------------------------
// Si existe un archivo .env junto al proyecto, lo lee y carga
// las variables en el entorno del proceso SOLO cuando no están
// ya definidas (así las variables reales del sistema/contenedor
// tienen prioridad: docker-compose, hosting, etc.).
// Uso: require_once __DIR__ . '/cargar_env.php';
// =========================================================

function cargarVariablesEntorno($archivo = null)
{
    $archivo = $archivo ?? __DIR__ . '/../.env';
    if (!is_file($archivo)) {
        return;
    }
    $lineas = file($archivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lineas as $linea) {
        $linea = trim($linea);
        if ($linea === '' || str_starts_with($linea, '#')) {
            continue;
        }
        $pos = strpos($linea, '=');
        if ($pos === false) {
            continue;
        }
        $clave = trim(substr($linea, 0, $pos));
        $valor = trim(substr($linea, $pos + 1));
        $valor = trim($valor, " \t\n\r\0\x0B\"'");
        if (getenv($clave) === false) {
            putenv($clave . '=' . $valor);
            $_ENV[$clave] = $valor;
        }
    }
}

cargarVariablesEntorno();