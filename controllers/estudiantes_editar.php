<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

$model = new EstudianteModel($pdo);

$id = $_GET['id'] ?? $_POST['id_estudiante'] ?? null;

if (!$id) {
    header("Location: estudiantes_listar.php");
    exit;
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $datos = [
        'nombre'     => trim($_POST['nombre'] ?? ''),
        'apellido'   => trim($_POST['apellido'] ?? ''),
        'correo'     => trim($_POST['correo'] ?? ''),
        'usuario'    => trim($_POST['usuario'] ?? ''),
        'id_carrera' => $_POST['id_carrera'] ?? '',
        'semestre'   => $_POST['semestre'] ?? ''
    ];

    if (
        empty($datos['nombre']) ||
        empty($datos['apellido']) ||
        empty($datos['correo']) ||
        empty($datos['usuario'])
    ) {
        $errores[] = "Todos los campos son obligatorios.";
    }

    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo inválido.";
    }

    if ($datos['semestre'] < 1 || $datos['semestre'] > 12) {
        $errores[] = "Semestre inválido.";
    }

    if (empty($errores)) {

        try {

            $model->actualizar($id, $datos);

            header("Location: estudiantes_listar.php");
            exit;

        } catch (Exception $e) {

            $errores[] = "No se pudo actualizar.";

        }
    }
}

$estudiante = $model->obtenerPorId($id);

if (!$estudiante) {
    header("Location: estudiantes_listar.php");
    exit;
}

$carreras = $model->obtenerCarreras();

require_once __DIR__ . '/../views/estudiantes/editar.php';