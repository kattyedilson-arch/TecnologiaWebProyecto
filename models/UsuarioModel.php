<?php
// =========================================================
// MODELO: USUARIOS (UsuarioModel.php)
// ---------------------------------------------------------
// Acceso a la tabla 'usuarios', la base del login del sistema.
// Un usuario tiene un rol (administrador, tutor o estudiante)
// y su contraseña se guarda con password_hash() (bcrypt), nunca
// en texto plano.
// =========================================================
class UsuarioModel
{
    private $pdo; // Conexión PDO compartida

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Lista todos los usuarios con su rol, para la gestión de usuarios.
     * @return array Usuarios ordenados del más reciente al más antiguo
     */
    public function obtenerTodos()
    {
        $sql = "SELECT u.id_usuario, u.nombre, u.apellido, u.correo, u.usuario,
                       r.nombre_rol, u.estado, u.fecha_registro
                FROM usuarios u
                INNER JOIN roles r ON u.id_rol = r.id_rol
                ORDER BY u.id_usuario DESC";
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Busca un usuario por su identificador.
     * @param int $id Identificador del usuario
     * @return array|false Fila completa del usuario o false
     */
    public function obtenerPorId($id)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Crea un nuevo usuario.
     * @param array $datos id_rol, nombre, apellido, correo, usuario, clave, telefono
     * @return bool True si la inserción fue exitosa (PDOException si correo/usuario duplicados)
     */
    public function crear($datos)
    {
        // password_hash genera un hash seguro (bcrypt) — NUNCA guardar la contraseña en texto plano
        $hash = password_hash($datos['clave'], PASSWORD_DEFAULT);

        $sql = "INSERT INTO usuarios (id_rol, nombre, apellido, correo, usuario, contrasena_hash, telefono)
                VALUES (:id_rol, :nombre, :apellido, :correo, :usuario, :hash, :telefono)";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id_rol'   => $datos['id_rol'],
            ':nombre'   => $datos['nombre'],
            ':apellido' => $datos['apellido'],
            ':correo'   => $datos['correo'],
            ':usuario'  => $datos['usuario'],
            ':hash'     => $hash,
            ':telefono' => !empty($datos['telefono']) ? $datos['telefono'] : null,
        ]);
    }

    /**
     * Actualiza un usuario (roles, datos personales y estado).
     * Si se envía 'clave_nueva' se regenera el hash de la contraseña.
     * @param int $id Identificador del usuario
     * @param array $datos Datos enviados desde el formulario de edición
     * @return bool True si la actualización fue exitosa
     */
    public function actualizar($id, $datos)
    {
        // Si se proporciona una nueva contraseña, se regenera el hash bcrypt
        $claveNueva = $datos['clave_nueva'] ?? '';

        if (!empty($claveNueva)) {
            $hash = password_hash($claveNueva, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios
                    SET id_rol = :id_rol, nombre = :nombre, apellido = :apellido,
                        correo = :correo, usuario = :usuario, estado = :estado,
                        telefono = :telefono, contrasena_hash = :hash
                    WHERE id_usuario = :id";
            $params = [
                ':id_rol'   => $datos['id_rol'],
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':correo'   => $datos['correo'],
                ':usuario'  => $datos['usuario'],
                ':estado'   => $datos['estado'],
                ':telefono' => !empty($datos['telefono']) ? $datos['telefono'] : null,
                ':hash'     => $hash,
                ':id'       => $id,
            ];
        } else {
            // Sin cambio de contraseña: se actualizan los demás campos
            $sql = "UPDATE usuarios
                    SET id_rol = :id_rol, nombre = :nombre, apellido = :apellido,
                        correo = :correo, usuario = :usuario, estado = :estado,
                        telefono = :telefono
                    WHERE id_usuario = :id";
            $params = [
                ':id_rol'   => $datos['id_rol'],
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':correo'   => $datos['correo'],
                ':usuario'  => $datos['usuario'],
                ':estado'   => $datos['estado'],
                ':telefono' => !empty($datos['telefono']) ? $datos['telefono'] : null,
                ':id'       => $id,
            ];
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Actualización de datos personales del usuario AUTENTICADO (pantalla "Mi Perfil").
     * No modifica el rol ni el estado; solo nombre/apellido/correo/teléfono
     * y, opcionalmente, la contraseña.
     * @param int $id Identificador del usuario en sesión
     * @param array $datos nombre, apellido, correo, telefono, clave_nueva, clave_conf
     * @return bool True si la actualización fue exitosa
     */
    public function actualizarPerfilPropio($id, $datos)
    {
        $claveNueva = $datos['clave_nueva'] ?? '';

        if (!empty($claveNueva)) {
            $hash = password_hash($claveNueva, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios
                    SET nombre = :nombre, apellido = :apellido, correo = :correo,
                        telefono = :telefono, contrasena_hash = :hash
                    WHERE id_usuario = :id";
            $params = [
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':correo'   => $datos['correo'],
                ':telefono' => !empty($datos['telefono']) ? $datos['telefono'] : null,
                ':hash'     => $hash,
                ':id'       => $id,
            ];
        } else {
            $sql = "UPDATE usuarios
                    SET nombre = :nombre, apellido = :apellido, correo = :correo,
                        telefono = :telefono
                    WHERE id_usuario = :id";
            $params = [
                ':nombre'   => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':correo'   => $datos['correo'],
                ':telefono' => !empty($datos['telefono']) ? $datos['telefono'] : null,
                ':id'       => $id,
            ];
        }

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Elimina un usuario del sistema.
     * Si el usuario tiene perfil asociado (estudiante/tutor) la FK lanza excepción.
     * @param int $id Identificador del usuario
     * @return bool True si la eliminación fue exitosa
     */
    public function eliminar($id)
    {
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id_usuario = :id");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Busca un usuario para el LOGIN por su nombre de usuario O por su correo.
     * Une con la tabla roles para conocer su rol (administrador/tutor/estudiante).
     * @param string $usuario Usuario o correo digitado en el login
     * @return array|false Fila del usuario con su rol, o false si no existe
     */
    public function obtenerPorUsuario($usuario) {
    $sql = "SELECT u.*, r.nombre_rol 
            FROM usuarios u
            JOIN roles r ON u.id_rol = r.id_rol
            WHERE u.usuario = :usuario1 OR u.correo = :usuario2
            LIMIT 1";
    $stmt = $this->pdo->prepare($sql);
    $stmt->execute([
        'usuario1' => $usuario,
        'usuario2' => $usuario
    ]);
    return $stmt->fetch();
}
}