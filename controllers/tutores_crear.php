<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutorModel.php';

$model = new TutorModel($pdo);

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $datos = [
        'nombre' => trim($_POST['nombre'] ?? ''),
        'apellido' => trim($_POST['apellido'] ?? ''),
        'correo' => trim($_POST['correo'] ?? ''),
        'usuario' => trim($_POST['usuario'] ?? ''),
        'clave' => $_POST['clave'] ?? '',
        'especialidad' => trim($_POST['especialidad'] ?? ''),
        'biografia' => trim($_POST['biografia'] ?? '')
    ];

    if (empty($datos['nombre'])) {
    $errores[] = "El nombre es obligatorio.";
}

    if (strlen($datos['nombre']) < 2) {
        $errores[] = "El nombre debe tener al menos 2 caracteres.";
    }

    if (strlen($datos['apellido']) < 2) {
        $errores[] = "El apellido debe tener al menos 2 caracteres.";
    }

    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo inválido.";
    }

    if (strlen($datos['usuario']) < 4) {
        $errores[] = "El usuario debe tener al menos 4 caracteres.";
    }

    if (strlen($datos['clave']) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }

    if (strlen($datos['especialidad']) < 3) {
        $errores[] = "La especialidad debe tener al menos 3 caracteres.";
    }

    if (strlen($datos['especialidad']) > 150) {
        $errores[] = "La especialidad es demasiado larga.";
    }

    if (strlen(trim($datos['biografia'])) < 10) {
        $errores[] = "La biografía debe tener al menos 10 caracteres.";
    }

    if ($model->existeUsuario($datos['usuario'])) {
        $errores[] = "El usuario ya existe.";
    }

    if ($model->existeCorreo($datos['correo'])) {
        $errores[] = "El correo ya está registrado.";
    }

    if (empty($errores)) {

        try {

            $model->crear($datos);

            header("Location: tutores_listar.php");
            exit;

        } catch (PDOException $e) {

            $errores[] = "No se pudo registrar el tutor.";

        }
    }
}

require_once __DIR__ . '/../views/tutores/crear.php';