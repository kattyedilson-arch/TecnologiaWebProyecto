<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/MateriaModel.php';

$model = new MateriaModel($pdo);

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $datos = [
        'nombre_materia' => trim($_POST['nombre_materia'] ?? ''),
        'id_carrera' => $_POST['id_carrera'] ?? ''
    ];

    if (empty($datos['nombre_materia'])) {
        $errores[] = "El nombre de la materia es obligatorio.";
    }

    if (strlen($datos['nombre_materia']) < 3) {
        $errores[] = "La materia debe tener al menos 3 caracteres.";
    }

    if (strlen($datos['nombre_materia']) > 150) {
        $errores[] = "La materia es demasiado larga.";
    }

    if (empty($datos['id_carrera'])) {
        $errores[] = "Debe seleccionar una carrera.";
    }

    if ($model->existeMateria($datos['nombre_materia'])) {
        $errores[] = "La materia ya existe.";
    }

    if (empty($errores)) {

        try {

            $model->crear($datos);

            header("Location: materias_listar.php");
            exit;

        } catch (PDOException $e) {

            $errores[] = "No se pudo registrar la materia.";

        }
    }
}

$carreras = $model->obtenerCarreras();

require_once __DIR__ . '/../views/materias/crear.php';
