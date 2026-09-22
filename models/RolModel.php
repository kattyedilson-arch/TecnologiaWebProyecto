<?php
// =========================================================
// MODELO: ROLES (RolModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'roles' de la base de datos.
// Los roles del sistema son: administrador, tutor y estudiante.
// =========================================================
class RolModel
{
    private $pdo; // Conexión PDO compartida (se inyecta desde el controller)

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Devuelve la lista de todos los roles existentes.
     * @return array Lista de roles [id_rol, nombre_rol]
     */
    public function obtenerTodos()
    {
        return $this->pdo->query("SELECT id_rol, nombre_rol FROM roles")->fetchAll();
    }
}