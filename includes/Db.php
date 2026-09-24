<?php
// =========================================================
// GESTOR DE CONEXIÓN A MYSQL (Db.php)
// ---------------------------------------------------------
// Encapsula el objeto PDO que comparten todos los Model.
// Mantiene una única conexión por request y, si la persistencia
// está activa (DB_PERSISTENT), las conexiones se reutilizan
// entre requests servidos por el mismo worker Apache/FPM.
// Ante una sesión cerrada por MySQL se reconecta solo.
// =========================================================
class Db
{
    private static $pdo = null;
    private static $verificando = false;

    /**
     * Conexión PDO compartida (singleton por request).
     * @return PDO
     */
    public static function conexion()
    {
        if (self::$pdo instanceof PDO) {
            self::verificarConexion();
            return self::$pdo;
        }
        self::$pdo = self::crear();
        return self::$pdo;
    }

    /**
     * Libera la conexión del request (pasa a null). La solicitud
     * siguiente creará una nueva.
     */
    public static function cerrar()
    {
        self::$pdo = null;
    }

    /**
     * Construye el objeto PDO con las opciones de la aplicación.
     * @return PDO
     */
    private static function crear()
    {
        $host = getenv('DB_HOST') ?: 'localhost';
        $db   = getenv('DB_NAME') ?: 'tutorias_db';
        $user = getenv('DB_USER') ?: 'tutorias_user';
        $pass = getenv('DB_PASS') ?: '12345';
        $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        $opciones = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        if (self::persistenciaActiva()) {
            $opciones[PDO::ATTR_PERSISTENT] = true;
        }

        try {
            $pdo = new PDO($dsn, $user, $pass, $opciones);
        } catch (PDOException $e) {
            error_log("Error de conexión a la base de datos: " . $e->getMessage());
            die("No se pudo conectar a la base de datos. Inténtalo de nuevo más tarde.");
        }

        // Defensa ante conexiones persistentes reutilizadas: si el worker
        // devolvió un enlace con una transacción a medias, se descarta.
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        return $pdo;
    }

    /**
     * Comprueba que la conexión siga viva y la regenera si MySQL
     * cerró la sesión (wait_timeout, reinicio del servidor...).
     */
    private static function verificarConexion()
    {
        if (self::$verificando) {
            return;
        }
        if (getenv('DB_PING') === '0') {
            return;
        }
        self::$verificando = true;
        try {
            self::$pdo->query('SELECT 1')->fetch();
        } catch (PDOException $e) {
            if (self::esConexionCaida($e)) {
                error_log("Db: reconexión automática (" . $e->getMessage() . ")");
                self::$pdo = self::crear();
            }
        } finally {
            self::$verificando = false;
        }
    }

    /**
     * True si el error de PDO corresponde a una sesión MySQL cortada.
     * @param PDOException $e Excepción capturada
     * @return bool
     */
    private static function esConexionCaida(PDOException $e)
    {
        $mensaje = strtolower($e->getMessage());
        return str_contains($mensaje, 'server has gone away')
            || str_contains($mensaje, 'lost connection')
            || str_contains($mensaje, 'error reading communication packet');
    }

    /**
     * Persistencia activable con la variable de entorno DB_PERSISTENT.
     * Está activada por defecto (recomendado bajo Apache mod_php/mysqlnd).
     * @return bool
     */
    private static function persistenciaActiva()
    {
        $persistente = getenv('DB_PERSISTENT');
        if ($persistente === false) {
            return true;
        }
        return $persistente === '1';
    }
}