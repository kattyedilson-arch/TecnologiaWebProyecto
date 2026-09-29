<?php
// =========================================================
// RUNNER DE MIGRACIONES (scripts/migrar.php)
// ---------------------------------------------------------
// Aplica en orden los archivos database/migrations/*.sql que
// aún no estén registrados en la tabla schema_migrations.
// Idempotente: cada archivo se aplica UNA sola vez.
//
// NOTA: NO se usa una transacción por archivo porque las
// sentencias DDL (CREATE TABLE, ALTER...) ejecutan un commit
// implícito en MySQL y romperían la transacción. Cada sentencia
// corre en autocommit; al primer error se detiene todo.
//
// Uso (dentro del contenedor web, desde la raíz del proyecto):
//     php scripts/migrar.php
// =========================================================

require_once __DIR__ . '/../includes/cargar_env.php';
require_once __DIR__ . '/../includes/Db.php';

function linea($texto = '')
{
    if (PHP_SAPI === 'cli') {
        echo $texto . PHP_EOL;
    } else {
        echo nl2br(htmlspecialchars((string)$texto) . '<br>');
    }
}

$pdo = Db::conexion();

// Tabla de control: registra qué migraciones ya se aplicaron
$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(80) PRIMARY KEY,
    applied_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$aplicadas = $pdo->query("SELECT version FROM schema_migrations")->fetchAll(PDO::FETCH_COLUMN);
$aplicadas = array_flip($aplicadas);

$archivos = glob(__DIR__ . '/../database/migrations/*.sql');
sort($archivos);

if (!$archivos) {
    linea('No hay migraciones en database/migrations.');
    exit(0);
}

$pendientes = 0;

foreach ($archivos as $archivo) {
    $version = basename($archivo);

    if (isset($aplicadas[$version])) {
        linea("[ok] $version (ya aplicada)");
        continue;
    }

    $sql = (string)file_get_contents($archivo);
    // Separa sentencias: cada una termina con ';' al final de línea
    $partes = preg_split('/;\s*\R/', $sql);

    try {
        foreach ($partes as $parte) {
            $parte = preg_replace('/^\s*--.*$/m', '', $parte); // quita comentarios
            $parte = trim($parte);
            if ($parte === '') {
                continue;
            }
            $pdo->exec($parte);
        }
        $stmt = $pdo->prepare("INSERT INTO schema_migrations (version) VALUES (?)");
        $stmt->execute([$version]);
        $pendientes++;
        linea("[+] $version aplicada");
    } catch (Throwable $e) {
        linea("[x] ERROR en $version: " . $e->getMessage());
        linea('    Sentencia fallida: ' . (isset($parte) ? substr($parte, 0, 220) : '(desconocida)'));
        linea('    Corrige la migración e inténtalo de nuevo.');
        exit(1);
    }
}

linea($pendientes > 0
    ? "Migraciones aplicadas: $pendientes."
    : 'Base de datos al día: no hay migraciones pendientes.');
exit(0);