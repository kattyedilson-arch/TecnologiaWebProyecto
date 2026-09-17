<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/MateriaModel.php';

$model = new MateriaModel($pdo);

$id = $_GET['id'] ?? $_POST['id_materia'] ?? null;

if (!$id) {
    header("Location: materias_listar.php");
    exit;
}

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

    if ($model->existeMateriaEditar(
            $datos['nombre_materia'],
            $id
        )) {

        $errores[] = "Ya existe una materia con ese nombre.";
    }

    if (empty($errores)) {

        try {

            $model->actualizar($id, $datos);

            header("Location: materias_listar.php");
            exit;

        } catch (Exception $e) {

            $errores[] = "No se pudo actualizar.";

        }
    }
}

$materia = $model->obtenerPorId($id);

$carreras = $model->obtenerCarreras();

require_once __DIR__ . '/../views/materias/editar.php';