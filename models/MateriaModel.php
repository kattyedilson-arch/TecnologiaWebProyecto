<?php

class MateriaModel
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function obtenerTodas()
    {
        $sql = "SELECT
                    m.id_materia,
                    m.nombre_materia,
                    c.nombre_carrera
                FROM materias m
                INNER JOIN carreras c
                ON m.id_carrera = c.id_carrera
                ORDER BY m.id_materia DESC";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function obtenerPorId($id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT * FROM materias WHERE id_materia = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch();
    }

    public function obtenerCarreras()
    {
        return $this->pdo->query(
            "SELECT * FROM carreras ORDER BY nombre_carrera"
        )->fetchAll();
    }

    public function crear($datos)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO materias
            (
                nombre_materia,
                id_carrera
            )
            VALUES
            (
                :nombre_materia,
                :id_carrera
            )"
        );

        return $stmt->execute([
            ':nombre_materia' => $datos['nombre_materia'],
            ':id_carrera' => $datos['id_carrera']
        ]);
    }

    public function actualizar($id, $datos)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE materias
             SET nombre_materia=:nombre,
                 id_carrera=:carrera
             WHERE id_materia=:id"
        );

        return $stmt->execute([
            ':nombre' => $datos['nombre_materia'],
            ':carrera' => $datos['id_carrera'],
            ':id' => $id
        ]);
    }

    public function eliminar($id)
    {
        $stmt = $this->pdo->prepare(
            "DELETE FROM materias
             WHERE id_materia=:id"
        );

        return $stmt->execute([
            ':id' => $id
        ]);
    }
        public function existeMateria($nombre)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_materia
            FROM materias
            WHERE nombre_materia = :nombre"
        );

        $stmt->execute([
            ':nombre' => $nombre
        ]);

        return $stmt->fetch();
    }
        public function existeMateriaEditar($nombre, $id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id_materia
            FROM materias
            WHERE nombre_materia = :nombre
            AND id_materia <> :id"
        );

        $stmt->execute([
            ':nombre' => $nombre,
            ':id' => $id
        ]);

        return $stmt->fetch();
    }

}