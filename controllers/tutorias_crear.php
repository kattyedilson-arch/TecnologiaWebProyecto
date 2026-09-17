<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutoriaModel.php';

$model = new TutoriaModel($pdo);

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $datos = [
        'id_estudiante' => $_POST['id_estudiante'] ?? '',
        'id_tutor' => $_POST['id_tutor'] ?? '',
        'id_materia' => $_POST['id_materia'] ?? '',
        'fecha' => $_POST['fecha'] ?? '',
        'hora_inicio' => $_POST['hora_inicio'] ?? '',
        'hora_fin' => $_POST['hora_fin'] ?? '',
        'modalidad' => $_POST['modalidad'] ?? '',
        'lugar_o_enlace' => trim($_POST['lugar_o_enlace'] ?? ''),
        'observaciones' => trim($_POST['observaciones'] ?? '')
    ];

    if (empty($datos['id_estudiante'])) {
        $errores[] = "Debe seleccionar un estudiante.";
    }

    if (empty($datos['id_tutor'])) {
        $errores[] = "Debe seleccionar un tutor.";
    }

    if (empty($datos['id_materia'])) {
        $errores[] = "Debe seleccionar una materia.";
    }

    if ($datos['hora_inicio'] >= $datos['hora_fin']) {
        $errores[] = "La hora fin debe ser mayor a la hora inicio.";
    }
    if (empty($datos['fecha'])) {
        $errores[] = "Debe seleccionar una fecha.";
    }
    if (
    !empty($datos['fecha']) &&
    $datos['fecha'] < date('Y-m-d')
    ) {
        $errores[] = "No puede registrar tutorías en fechas pasadas.";
    }
    if (empty($datos['hora_inicio'])) {
        $errores[] = "Debe seleccionar la hora de inicio.";
    }
    if (empty($datos['hora_fin'])) {
    $errores[] = "Debe seleccionar la hora de finalización.";
    }
    if (
    $datos['modalidad'] !== 'presencial' &&
    $datos['modalidad'] !== 'virtual'
    ) {
        $errores[] = "Modalidad inválida.";
    }
    if (strlen($datos['lugar_o_enlace']) > 200) {
        $errores[] = "El lugar o enlace es demasiado largo.";
    }
    if (strlen($datos['observaciones']) > 1000) {
        $errores[] = "Las observaciones son demasiado largas.";
    }
    if (
    $model->existeConflictoHorario(
        $datos['id_tutor'],
        $datos['fecha'],
        $datos['hora_inicio'],
        $datos['hora_fin']
        )
    ) {
        $errores[] =
            "El tutor ya tiene una tutoría en ese horario.";
    }
    

    if (empty($errores)) {

        $model->crear($datos);

        header("Location: tutorias_listar.php");
        exit;
    }
}

$estudiantes = $model->obtenerEstudiantes();
$tutores = $model->obtenerTutores();
$materias = $model->obtenerMaterias();

require_once __DIR__ . '/../views/tutorias/crear.php';