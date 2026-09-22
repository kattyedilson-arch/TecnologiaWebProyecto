<?php
// =========================================================
// SCRIPT DE REPARACIÓN DE CODIFICACIÓN (acentos mojibake)
// ---------------------------------------------------------
// Detecta y corrige textos doble-codificados (UTF-8 leído como
// Latin-1 en algún momento), por ejemplo:
//   "TecnologÃ­a Web"  ->  "Tecnología Web"
// también convierte las tablas al charset utf8mb4 si no lo están.
//
// CÓMO EJECUTARLO (con el contenedor levantado):
//   docker exec tutorias_web_v2 php /var/www/html/scripts_bd/reparar_acentos.php
//
// DESPUÉS DE USARLO: elimina este archivo del servidor web
// (/scripts_bd/reparar_acentos.php) por seguridad.
// =========================================================

require_once __DIR__ . '/../config/conexion.php';

// SOLO CONSOLA: si alguien lo abre desde el navegador, se deniega.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Acceso denegado. Ejecuta este script por consola: php scripts_bd/reparar_acentos.php');
}

$nombreBase = $pdo->query('SELECT DATABASE()')->fetchColumn();

echo "=== Reparación de codificación UTF-8 ===\n";
echo "Base de datos: $nombreBase\n\n";

// 1) Descubrir tablas y sus columnas de texto
$columnas = $pdo->query(
    "SELECT t.TABLE_NAME, c.COLUMN_NAME, c.DATA_TYPE
     FROM information_schema.TABLES t
     INNER JOIN information_schema.COLUMNS c
        ON t.TABLE_NAME = c.TABLE_NAME AND t.TABLE_SCHEMA = c.TABLE_SCHEMA
     WHERE t.TABLE_SCHEMA = '" . addslashes($nombreBase) . "'
       AND t.TABLE_TYPE = 'BASE TABLE'
       AND c.DATA_TYPE IN ('char','varchar','tinytext','text','mediumtext','longtext')
     ORDER BY t.TABLE_NAME, c.ORDINAL_POSITION")->fetchAll();

// Columnas de texto agrupadas por tabla
$columnasPorTabla = [];
foreach ($columnas as $col) {
    $columnasPorTabla[$col['TABLE_NAME']][] = $col['COLUMN_NAME'];
}

// 2) Detectar texto doble-codificado (contiene "Ã" o "Â" en UTF-8)
function estaDobleCodificado($valor)
{
    return is_string($valor)
        && (strpos($valor, "\xC3\x83") !== false || strpos($valor, "\xC3\x82") !== false);
}

// 3) Corregir un valor doble-codificado
function corregirValor($valor)
{
    $nuevo = $valor;
    $pasos = 0;
    while (estaDobleCodificado($nuevo) && $pasos < 3) {
        $nuevo = mb_convert_encoding($nuevo, 'ISO-8859-1', 'UTF-8');
        $pasos++;
    }
    if (mb_check_encoding($nuevo, 'UTF-8')) {
        return $nuevo;
    }
    return $valor;
}

$totalCorregidos = 0;

foreach ($columnasPorTabla as $tabla => $colsTexto) {
    // Descubrir claves primarias de la tabla
    $sqlPk = $pdo->prepare(
        "SELECT COLUMN_NAME
         FROM information_schema.KEY_COLUMN_USAGE
         WHERE TABLE_SCHEMA = '" . addslashes($nombreBase) . "'
           AND TABLE_NAME = ?
           AND CONSTRAINT_NAME = 'PRIMARY'
         ORDER BY ORDINAL_POSITION");
    $sqlPk->execute([$tabla]);
    $pks = $sqlPk->fetchAll(PDO::FETCH_COLUMN);

    if (empty($pks)) {
        echo "  [omitida] $tabla: sin clave primaria\n";
        continue;
    }

    $listaColumnas = implode(', ', array_map(fn($c) => '`' . $c . '`', $colsTexto));
    $filas = $pdo->query("SELECT " . implode(', ', array_map(fn($c) => '`' . $c . '`', $pks)) . ", $listaColumnas FROM `$tabla`")->fetchAll();

    foreach ($filas as $fila) {
        $condicionPk = implode(' AND ', array_map(fn($pk) => '`' . $pk . '` = ?', $pks));
        $ubiPk = array_map(fn($pk) => $fila[$pk], $pks);

        foreach ($colsTexto as $col) {
            $valorActual = $fila[$col] ?? '';
            if (!estaDobleCodificado($valorActual)) {
                continue;
            }
            $nuevoValor = corregirValor($valorActual);
            if ($nuevoValor === $valorActual) {
                continue;
            }

            $ubi = array_merge([$nuevoValor], $ubiPk);
            $stmt = $pdo->prepare("UPDATE `$tabla` SET `$col` = ? WHERE $condicionPk");
            $stmt->execute($ubi);

            $totalCorregidos++;
            $corto = mb_strlen($valorActual) > 45 ? mb_substr($valorActual, 0, 44) . '...' : $valorActual;
            echo "  [ok] $tabla.$col: \"$corto\" -> \"$nuevoValor\"\n";
        }
    }
}

echo "\n=== Datos corregidos: $totalCorregidos valor(es) ===\n\n";

// 4) Normalizar el charset de las tablas a utf8mb4
$tablasCharset = $pdo->query(
    "SELECT TABLE_NAME, TABLE_COLLATION
     FROM information_schema.TABLES
     WHERE TABLE_SCHEMA = '" . addslashes($nombreBase) . "'
       AND TABLE_TYPE = 'BASE TABLE'")->fetchAll();

$conversiones = 0;
foreach ($tablasCharset as $t) {
    if (!str_starts_with(strtolower($t['TABLE_COLLATION']), 'utf8mb4_')) {
        $pdo->exec("ALTER TABLE `{$t['TABLE_NAME']}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "  [charset] {$t['TABLE_NAME']} convertida a utf8mb4\n";
        $conversiones++;
    }
}

echo "\nTablas convertidas a utf8mb4: $conversiones\n";
echo "=== Proceso finalizado ===\n";