<?php
// =========================================================
// SCRIPT DE REPARACIÓN DE CODIFICACIÓN (acentos mojibake)
// ---------------------------------------------------------
// Detecta y corrige textos doble-codificados (UTF-8 leído como
// Latin-1 en algún momento), por ejemplo:
//   "TecnologÃ­a Web"  ->  "Tecnología Web"
// también convierte las tablas al charset utf8mb4 si no lo están.
// Adicionalmente cubre columnas ENUM/SET: si un valor corregido
// no está aún en la definición de la columna, se AMPLÍA la
// definición (un ALTER) antes de actualizar la fila. No se
// compacta el ENUM automáticamente para evitar pérdida de datos;
// revisa con SHOW COLUMNS y compacta manualmente si lo deseas.
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

// 1) Descubrir tablas y sus columnas de texto + ENUM/SET
$filasColumnas = $pdo->query(
    "SELECT t.TABLE_NAME, c.COLUMN_NAME, c.DATA_TYPE, c.COLUMN_TYPE,
            c.IS_NULLABLE, c.COLUMN_DEFAULT
     FROM information_schema.TABLES t
     INNER JOIN information_schema.COLUMNS c
        ON t.TABLE_NAME = c.TABLE_NAME AND t.TABLE_SCHEMA = c.TABLE_SCHEMA
     WHERE t.TABLE_SCHEMA = '" . addslashes($nombreBase) . "'
       AND t.TABLE_TYPE = 'BASE TABLE'
       AND c.DATA_TYPE IN ('char','varchar','tinytext','text','mediumtext','longtext','enum','set')
     ORDER BY t.TABLE_NAME, c.ORDINAL_POSITION")->fetchAll();

// Columnas agrupadas por tabla
$columnasPorTabla = [];
foreach ($filasColumnas as $col) {
    $columnasPorTabla[$col['TABLE_NAME']][] = $col;
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

// Extrae los valores permitidos de una definición ENUM/SET,
// p. ej. "enum('Lunes','Martes')" -> ['Lunes','Martes']
function valoresPermitidos($columnType)
{
    if (!is_string($columnType) || !preg_match('/^(enum|set)\(/i', $columnType)) {
        return null;
    }
    if (!preg_match_all("/'((?:[^']|'')*)'/", $columnType, $m)) {
        return null;
    }
    return array_map(fn($v) => str_replace("''", "'", $v), $m[1]);
}

// Construye la definición de columna para un ALTER (enum|set) sin
// perder NULL/DEFAULT originales.
function definirEnumSet($tipoBase, array $valores, $nullable, $default)
{
    $lista = implode(', ', array_map(fn($v) => "'" . str_replace("'", "''", $v) . "'", $valores));
    $sql = "$tipoBase($lista)";
    $sql .= ($nullable !== 'NO') ? ' NULL' : ' NOT NULL';
    if ($default !== null) {
        $sql .= " DEFAULT '" . str_replace("'", "''", $default) . "'";
    }
    return $sql;
}

// Cache por tabla.columna: valores permitidos actuales (se actualiza al ampliar)
$cachePermitidos = [];

foreach ($columnasPorTabla as $tabla => $cols) {
    foreach ($cols as $col) {
        if (in_array($col['DATA_TYPE'], ['enum', 'set'], true)) {
            $cachePermitidos[$tabla][$col['COLUMN_NAME']] = valoresPermitidos($col['COLUMN_TYPE'] ?? '');
        }
    }
}

$totalCorregidos = 0;
$totalAmpliaciones = 0;

foreach ($columnasPorTabla as $tabla => $cols) {
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

    $listaColumnas = implode(', ', array_map(fn($c) => '`' . $c['COLUMN_NAME'] . '`', $cols));
    $filas = $pdo->query("SELECT " . implode(', ', array_map(fn($c) => '`' . $c . '`', $pks)) . ", $listaColumnas FROM `$tabla`")->fetchAll();

    foreach ($filas as $fila) {
        $condicionPk = implode(' AND ', array_map(fn($pk) => '`' . $pk . '` = ?', $pks));
        $ubiPk = array_map(fn($pk) => $fila[$pk], $pks);

        foreach ($cols as $col) {
            $nombreCol = $col['COLUMN_NAME'];
            $valorActual = $fila[$nombreCol] ?? '';
            if (!estaDobleCodificado($valorActual)) {
                continue;
            }
            $nuevoValor = corregirValor($valorActual);
            if ($nuevoValor === $valorActual) {
                continue;
            }

            // ENUM/SET: si el valor corregido no está permitido, ampliar la definición
            if (in_array($col['DATA_TYPE'], ['enum', 'set'], true)) {
                $permitidos = &$cachePermitidos[$tabla][$nombreCol];
                if ($permitidos !== null && !in_array($nuevoValor, $permitidos, true)) {
                    $permitidos[] = $nuevoValor;
                    $definicion = definirEnumSet(
                        $col['DATA_TYPE'],
                        $permitidos,
                        $col['IS_NULLABLE'] ?? 'NO',
                        $col['COLUMN_DEFAULT'] ?? null
                    );
                    $pdo->exec("ALTER TABLE `$tabla` MODIFY `$nombreCol` $definicion");
                    $totalAmpliaciones++;
                    echo "  [enum] $tabla.$nombreCol: definición ampliada con \"$nuevoValor\"\n";
                }
            }

            $ubi = array_merge([$nuevoValor], $ubiPk);
            $stmt = $pdo->prepare("UPDATE `$tabla` SET `$nombreCol` = ? WHERE $condicionPk");
            $stmt->execute($ubi);

            $totalCorregidos++;
            $corto = mb_strlen($valorActual) > 45 ? mb_substr($valorActual, 0, 44) . '...' : $valorActual;
            echo "  [ok] $tabla.$nombreCol: \"$corto\" -> \"$nuevoValor\"\n";
        }
    }
}

echo "\n=== Datos corregidos: $totalCorregidos valor(es) ===\n";
echo "=== Definiciones ENUM/SET ampliadas: $totalAmpliaciones ===\n\n";

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