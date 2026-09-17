<?php

class TutorMateriaModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function obtenerTodas()
    {
        $sql = "
            SELECT
                tm.id_tutor,
                tm.id_materia,
                u.nombre,
                u.apellido,
                m.nombre_materia
            FROM tutor_materia tm

            INNER JOIN tutores t
                ON tm.id_tutor = t.id_tutor

            INNER JOIN usuarios u
                ON t.id_usuario = u.id_usuario

            INNER JOIN materias m
                ON tm.id_materia = m.id_materia

            ORDER BY u.nombre
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function obtenerTutores()
    {
        $sql = "
            SELECT
                t.id_tutor,
                u.nombre,
                u.apellido
            FROM tutores t
            INNER JOIN usuarios u
                ON t.id_usuario = u.id_usuario
            ORDER BY u.nombre
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function obtenerMaterias()
    {
        return $this->pdo->query(
            "SELECT * FROM materias ORDER BY nombre_materia"
        )->fetchAll();
    }

    public function crear($idTutor, $idMateria)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tutor_materia
            (
                id_tutor,
                id_materia
            )
            VALUES
            (
                :id_tutor,
                :id_materia
            )"
        );

        return $stmt->execute([
            ':id_tutor' => $idTutor,
            ':id_materia' => $idMateria
        ]);
    }

    public function eliminar($idTutor, $idMateria)
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM tutor_materia
            WHERE id_tutor = :id_tutor
            AND id_materia = :id_materia"
        );

        return $stmt->execute([
            ':id_tutor' => $idTutor,
            ':id_materia' => $idMateria
        ]);
    }
    public function existeRelacion($idTutor, $idMateria)
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
            FROM tutor_materia
            WHERE id_tutor = :id_tutor
            AND id_materia = :id_materia"
        );

        $stmt->execute([
            ':id_tutor' => $idTutor,
            ':id_materia' => $idMateria
        ]);

        return $stmt->fetch();
    }
}