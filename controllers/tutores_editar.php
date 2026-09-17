<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutorModel.php';

$model = new TutorModel($pdo);

$id = $_GET['id'] ?? $_POST['id_tutor'] ?? null;

    if (!$id) {
        header("Location: tutores_listar.php");
        exit;
    }

    $errores = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $datos = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'apellido' => trim($_POST['apellido'] ?? ''),
            'correo' => trim($_POST['correo'] ?? ''),
            'usuario' => trim($_POST['usuario'] ?? ''),
            'especialidad' => trim($_POST['especialidad'] ?? ''),
            'biografia' => trim($_POST['biografia'] ?? '')
        ];

        if ($model->existeUsuarioEditar(
            $datos['usuario'],
            $tutor['id_usuario']
        )) {

        $errores[] = "El usuario ya existe.";
    }

    if ($model->existeCorreoEditar(
            $datos['correo'],
            $tutor['id_usuario']
        )) {

        $errores[] = "El correo ya está registrado.";
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

    if (strlen($datos['especialidad']) < 3) {
        $errores[] = "La especialidad debe tener al menos 3 caracteres.";
    }

    if (strlen($datos['especialidad']) > 150) {
        $errores[] = "La especialidad es demasiado larga.";
    }

    if (strlen(trim($datos['biografia'])) < 10) {
        $errores[] = "La biografía debe tener al menos 10 caracteres.";
    }


    if (empty($errores)) {

        try {

            $model->actualizar($id, $datos);

            header("Location: tutores_listar.php");
            exit;

        } catch (Exception $e) {

            $errores[] = "No se pudo actualizar.";

        }
    }
}

$tutor = $model->obtenerPorId($id);

require_once __DIR__ . '/../views/tutores/editar.php';