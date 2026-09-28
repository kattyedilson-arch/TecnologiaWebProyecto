<?php
// =========================================================
// MODELO: PARÁMETROS DE MODALIDADES DE GRADO (ParametroModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'parametros_mg' (HU-020). Valores centrales
// del módulo que el coordinador puede ajustar sin tocar código:
// nota de aprobación, escala máxima, antelación de citaciones,
// intervalo mínimo entre defensas, etc.
// =========================================================
class ParametroModel
{
    private $pdo;
    private static $cache = [];

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todos los parámetros disponibles, con su valor y
     * si son editables desde la interfaz.
     * @return array Parámetros ordenados por clave
     */
    public function obtenerTodos()
    {
        return $this->pdo->query(
            "SELECT * FROM parametros_mg ORDER BY id_parametro ASC"
        )->fetchAll();
    }

    /**
     * Busca un parámetro por su identificador.
     * @param int $id_parametro Identificador
     * @return array|false Fila o false
     */
    public function obtenerPorId($id_parametro)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM parametros_mg WHERE id_parametro = :id");
        $stmt->execute([':id' => $id_parametro]);
        return $stmt->fetch();
    }

    /**
     * Valor de un parámetro por clave, con fallback si no existe.
     * @param string $clave Clave del parámetro (p. ej. APROBADO_MIN)
     * @param string|null $default Valor por defecto
     * @return string|null Valor configurado o el default
     */
    public function obtener($clave, $default = null)
    {
        if (isset(self::$cache[$clave])) {
            return self::$cache[$clave];
        }
        $stmt = $this->pdo->prepare(
            "SELECT valor FROM parametros_mg WHERE clave = :clave LIMIT 1"
        );
        $stmt->execute([':clave' => $clave]);
        $valor = $stmt->fetchColumn();
        self::$cache[$clave] = $valor === false ? $default : $valor;
        return self::$cache[$clave];
    }

    /**
     * Actualiza el valor de un parámetro editable.
     * @param int $id_parametro Identificador
     * @param string $valor Nuevo valor
     * @return bool True si se actualizó
     */
    public function actualizar($id_parametro, $valor)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE parametros_mg SET valor = :valor, actualizado_en = NOW()
             WHERE id_parametro = :id AND es_editable = 1"
        );
        $ok = $stmt->execute([':valor' => trim((string)$valor), ':id' => $id_parametro]);
        if ($ok) {
            self::$cache = [];
        }
        return $ok;
    }
}