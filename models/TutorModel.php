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

}