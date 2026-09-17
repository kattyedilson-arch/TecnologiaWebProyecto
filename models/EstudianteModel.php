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
}