<?php
// =========================================================
// CONFIGURACIÓN DE LA CONEXIÓN A MYSQL (conexion.php)
// ---------------------------------------------------------
// Crea el objeto PDO global $pdo que usan todas las clases
// Model. Los valores se leen primero de variables de entorno
// (definidas en docker-compose.yml) y si no existen se usan
// valores por defecto para desarrollo local.
// =========================================================

$host = getenv('DB_HOST') ?: 'localhost';   // Host de MySQL (en Docker = "db")
$db   = getenv('DB_NAME') ?: 'tutorias_db'; // Nombre de la base de datos
$user = getenv('DB_USER') ?: 'tutorias_user'; // Usuario de la BD
$pass = getenv('DB_PASS') ?: '12345';       // Contraseña del usuario
$charset = 'utf8mb4';                       // Soporte completo de acentos/ñ/emojis

// DSN de PDO: incluye el charset utf8mb4 para leer/escribir con UTF-8
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

// Opciones de comportamiento del PDO:
$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // Lanza excepciones ante errores SQL
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // Devuelve arrays asociativos (nombre_columna => valor)
    PDO::ATTR_EMULATE_PREPARES   => false,                   // Usa consultas preparadas REALES de MySQL (más seguro)
];

try {
    // Se crea la conexión. Si falla (servidor caído, credenciales malas) se detiene el sistema.
    $pdo = new PDO($dsn, $user, $pass, $opciones);
} catch (PDOException $e) {
    // El detalle real se guarda en el log del servidor (nunca se muestra al usuario)
    error_log("Error de conexión a la base de datos: " . $e->getMessage());
    die("No se pudo conectar a la base de datos. Inténtalo de nuevo más tarde.");
}

