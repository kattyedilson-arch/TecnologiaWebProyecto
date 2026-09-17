<?php

class TutoriaModel
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
                t.id_tutoria,
                ue.nombre AS estudiante_nombre,
                ue.apellido AS estudiante_apellido,
                ut.nombre AS tutor_nombre,
                ut.apellido AS tutor_apellido,
                m.nombre_materia,
                t.fecha,
                t.hora_inicio,
                t.hora_fin,
                t.modalidad,
                t.estado
            FROM tutorias t
            INNER JOIN estudiantes e
                ON t.id_estudiante = e.id_estudiante
            INNER JOIN usuarios ue
                ON e.id_usuario = ue.id_usuario
            INNER JOIN tutores tu
                ON t.id_tutor = tu.id_tutor
            INNER JOIN usuarios ut
                ON tu.id_usuario = ut.id_usuario
            INNER JOIN materias m
                ON t.id_materia = m.id_materia
            ORDER BY t.id_tutoria DESC
        ";

        return $this->pdo->query($sql)->fetchAll();
    }

    public function obtenerEstudiantes()
    {
        $sql = "
            SELECT
                e.id_estudiante,
                u.nombre,
                u.apellido
            FROM estudiantes e
            INNER JOIN usuarios u
                ON e.id_usuario = u.id_usuario
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

    public function crear($datos)
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO tutorias
            (
                id_estudiante,
                id_tutor,
                id_materia,
                fecha,
                hora_inicio,
                hora_fin,
                modalidad,
                lugar_o_enlace,
                observaciones
            )
            VALUES
            (
                :id_estudiante,
                :id_tutor,
                :id_materia,
                :fecha,
                :hora_inicio,
                :hora_fin,
                :modalidad,
                :lugar_o_enlace,
                :observaciones
            )"
        );

        return $stmt->execute([
            ':id_estudiante' => $datos['id_estudiante'],
            ':id_tutor' => $datos['id_tutor'],
            ':id_materia' => $datos['id_materia'],
            ':fecha' => $datos['fecha'],
            ':hora_inicio' => $datos['hora_inicio'],
            ':hora_fin' => $datos['hora_fin'],
            ':modalidad' => $datos['modalidad'],
            ':lugar_o_enlace' => $datos['lugar_o_enlace'],
            ':observaciones' => $datos['observaciones']
        ]);
    }
    public function existeConflictoHorario(
        $idTutor,
        $fecha,
        $horaInicio,
        $horaFin
    )
    {
        $sql = "
            SELECT id_tutoria
            FROM tutorias
            WHERE id_tutor = :id_tutor
            AND fecha = :fecha
            AND estado <> 'cancelada'
            AND (
                :hora_inicio < hora_fin
                AND :hora_fin > hora_inicio
            )
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':id_tutor' => $idTutor,
            ':fecha' => $fecha,
            ':hora_inicio' => $horaInicio,
            ':hora_fin' => $horaFin
        ]);

        return $stmt->fetch();
    }
    public function cambiarEstado($id, $estado)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tutorias
            SET estado = :estado
            WHERE id_tutoria = :id"
        );

        return $stmt->execute([
            ':estado' => $estado,
            ':id' => $id
        ]);
    }
    public function obtenerPorId($id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT *
            FROM tutorias
            WHERE id_tutoria = :id"
        );

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch();
    }
     public function actualizar($id, $datos)
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tutorias
            SET
                fecha = :fecha,
                hora_inicio = :hora_inicio,
                hora_fin = :hora_fin,
                modalidad = :modalidad,
                lugar_o_enlace = :lugar,
                observaciones = :obs
            WHERE id_tutoria = :id"
        );

        return $stmt->execute([
            ':fecha' => $datos['fecha'],
            ':hora_inicio' => $datos['hora_inicio'],
            ':hora_fin' => $datos['hora_fin'],
            ':modalidad' => $datos['modalidad'],
            ':lugar' => $datos['lugar_o_enlace'],
            ':obs' => $datos['observaciones'],
            ':id' => $id
        ]);
    }

}