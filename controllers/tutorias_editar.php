<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutoriaModel.php';

$model = new TutoriaModel($pdo);

$id = $_GET['id'] ?? $_POST['id_tutoria'] ?? null;

if (!$id) {
    header("Location: tutorias_listar.php");
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $datos = [

        'fecha' => $_POST['fecha'] ?? '',
        'hora_inicio' => $_POST['hora_inicio'] ?? '',
        'hora_fin' => $_POST['hora_fin'] ?? '',
        'modalidad' => $_POST['modalidad'] ?? '',
        'lugar_o_enlace' => trim($_POST['lugar_o_enlace'] ?? ''),
        'observaciones' => trim($_POST['observaciones'] ?? '')
    ];

    if ($datos['fecha'] < date('Y-m-d')) {
        $errores[] =
            "No puede registrar fechas pasadas.";
    }

    if (
        $datos['hora_inicio'] >=
        $datos['hora_fin']
    ) {
        $errores[] =
            "La hora final debe ser mayor.";
    }

    if (empty($errores)) {

        $model->actualizar($id, $datos);

        header("Location: tutorias_listar.php");
        exit;
    }
}

$tutoria = $model->obtenerPorId($id);

require_once __DIR__ . '/../views/tutorias/editar.php';