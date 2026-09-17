<?php

class TutorModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function obtenerTodos()
    {
        $sql = "
            SELECT
                t.id_tutor,
                u.nombre,
                u.apellido,
                u.correo,
                u.usuario,
                t.especialidad,
                t.biografia
            FROM tutores t
            INNER JOIN usuarios u
                ON t.id_usuario = u.id_usuario
            ORDER BY t.id_tutor DESC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }
    public function crear($datos)
    {
        $this->pdo->beginTransaction();

        try {

            $hash = password_hash(
                $datos['clave'],
                PASSWORD_DEFAULT
            );

            $stmt = $this->pdo->prepare(
                "INSERT INTO usuarios
                (
                    id_rol,
                    nombre,
                    apellido,
                    correo,
                    usuario,
                    contrasena_hash
                )
                VALUES
                (
                    2,
                    :nombre,
                    :apellido,
                    :correo,
                    :usuario,
                    :hash
                )"
            );

            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':correo' => $datos['correo'],
                ':usuario' => $datos['usuario'],
                ':hash' => $hash
            ]);

            $idUsuario = $this->pdo->lastInsertId();

            $stmt = $this->pdo->prepare(
                "INSERT INTO tutores
                (
                    id_usuario,
                    especialidad,
                    biografia
                )
                VALUES
                (
                    :id_usuario,
                    :especialidad,
                    :biografia
                )"
            );

            $stmt->execute([
                ':id_usuario' => $idUsuario,
                ':especialidad' => $datos['especialidad'],
                ':biografia' => $datos['biografia']
            ]);

            $this->pdo->commit();

            return true;

        } catch (Exception $e) {

            $this->pdo->rollBack();

            throw $e;
        }
    }
    public function obtenerPorId($id)
    {
        $sql = "
            SELECT
                t.*,
                u.nombre,
                u.apellido,
                u.correo,
                u.usuario
            FROM tutores t
            INNER JOIN usuarios u
                ON t.id_usuario = u.id_usuario
            WHERE t.id_tutor = :id
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch();
    }
    public function actualizar($id, $datos)
    {
        $this->pdo->beginTransaction();

        try {

            $tutor = $this->obtenerPorId($id);

            $stmt = $this->pdo->prepare(
                "UPDATE usuarios
                SET
                    nombre = :nombre,
                    apellido = :apellido,
                    correo = :correo,
                    usuario = :usuario
                WHERE id_usuario = :id_usuario"
            );

            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':correo' => $datos['correo'],
                ':usuario' => $datos['usuario'],
                ':id_usuario' => $tutor['id_usuario']
            ]);

            $stmt = $this->pdo->prepare(
                "UPDATE tutores
                SET
                    especialidad = :especialidad,
                    biografia = :biografia
                WHERE id_tutor = :id"
            );

            $stmt->execute([
                ':especialidad' => $datos['especialidad'],
                ':biografia' => $datos['biografia'],
                ':id' => $id
            ]);

            $this->pdo->commit();

            return true;

        } catch (Exception $e) {

            $this->pdo->rollBack();

            throw $e;
        }
    }
    public function eliminar($id)
    {
        $tutor = $this->obtenerPorId($id);

        if (!$tutor) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "DELETE FROM usuarios
            WHERE id_usuario = :id_usuario"
        );

        return $stmt->execute([
            ':id_usuario' => $tutor['id_usuario']
        ]);
    }
    public function existeUsuario($usuario)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_usuario
            FROM usuarios
            WHERE usuario = :usuario"
        );

        $stmt->execute([
            ':usuario' => $usuario
        ]);

        return $stmt->fetch();
    }
    public function existeCorreo($correo)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_usuario
            FROM usuarios
            WHERE correo = :correo"
        );

        $stmt->execute([
            ':correo' => $correo
        ]);

        return $stmt->fetch();
    }
    public function existeUsuarioEditar($usuario, $idUsuario)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_usuario
            FROM usuarios
            WHERE usuario = :usuario
            AND id_usuario <> :id"
        );

        $stmt->execute([
            ':usuario' => $usuario,
            ':id' => $idUsuario
        ]);

        return $stmt->fetch();
    }
    public function existeCorreoEditar($correo, $idUsuario)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_usuario
            FROM usuarios
            WHERE correo = :correo
            AND id_usuario <> :id"
        );

        $stmt->execute([
            ':correo' => $correo,
            ':id' => $idUsuario
        ]);

        return $stmt->fetch();
    }
    


}