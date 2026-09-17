<?php

class EstudianteModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function obtenerTodos()
    {
        $sql = "SELECT
                    e.id_estudiante,
                    u.nombre,
                    u.apellido,
                    u.usuario,
                    c.nombre_carrera,
                    e.semestre,
                    e.registro_universitario
                FROM estudiantes e
                INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                INNER JOIN carreras c ON e.id_carrera = c.id_carrera
                ORDER BY e.id_estudiante DESC";

        return $this->pdo->query($sql)->fetchAll();
    }
        public function obtenerCarreras()
    {
        return $this->pdo->query(
            "SELECT * FROM carreras ORDER BY nombre_carrera"
        )->fetchAll();
    }

    public function generarRU()
    {
        $sql = "SELECT COUNT(*) + 1 AS siguiente FROM estudiantes";
        $fila = $this->pdo->query($sql)->fetch();

        $anio = date('Y');

        return 'RU-' . $anio . '-' . str_pad(
            $fila['siguiente'],
            5,
            '0',
            STR_PAD_LEFT
        );
    }

    public function obtenerUsuariosEstudiantes()
    {
        $sql = "SELECT id_usuario, nombre, apellido
                FROM usuarios
                WHERE id_rol = 3
                AND id_usuario NOT IN (
                    SELECT id_usuario FROM estudiantes
                )";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function crear($datos)
    {
        $sql = "INSERT INTO estudiantes
                (id_usuario, id_carrera, semestre, registro_universitario)
                VALUES
                (:id_usuario, :id_carrera, :semestre, :registro_universitario)";

        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute([
            ':id_usuario' => $datos['id_usuario'],
            ':id_carrera' => $datos['id_carrera'],
            ':semestre' => $datos['semestre'],
            ':registro_universitario' => $datos['registro_universitario']
        ]);
    }
        public function obtenerPorId($id)
    {
        $sql = "SELECT
                    e.*,
                    u.nombre,
                    u.apellido,
                    u.correo,
                    u.usuario
                FROM estudiantes e
                INNER JOIN usuarios u
                    ON e.id_usuario = u.id_usuario
                WHERE e.id_estudiante = :id";

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

            $estudiante = $this->obtenerPorId($id);

            $sqlUsuario = "
                UPDATE usuarios
                SET
                    nombre = :nombre,
                    apellido = :apellido,
                    correo = :correo,
                    usuario = :usuario
                WHERE id_usuario = :id_usuario
            ";

            $stmt = $this->pdo->prepare($sqlUsuario);

            $stmt->execute([
                ':nombre' => $datos['nombre'],
                ':apellido' => $datos['apellido'],
                ':correo' => $datos['correo'],
                ':usuario' => $datos['usuario'],
                ':id_usuario' => $estudiante['id_usuario']
            ]);

            $sqlEstudiante = "
                UPDATE estudiantes
                SET
                    id_carrera = :id_carrera,
                    semestre = :semestre
                WHERE id_estudiante = :id
            ";

            $stmt = $this->pdo->prepare($sqlEstudiante);

            $stmt->execute([
                ':id_carrera' => $datos['id_carrera'],
                ':semestre' => $datos['semestre'],
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
        $estudiante = $this->obtenerPorId($id);

        if (!$estudiante) {
            return false;
        }

        $stmt = $this->pdo->prepare(
            "DELETE FROM usuarios
            WHERE id_usuario = :id_usuario"
        );

        return $stmt->execute([
            ':id_usuario' => $estudiante['id_usuario']
        ]);
    }

}