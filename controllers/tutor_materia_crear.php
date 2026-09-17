<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutorMateriaModel.php';

$model = new TutorMateriaModel($pdo);

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $idTutor = $_POST['id_tutor'] ?? '';
    $idMateria = $_POST['id_materia'] ?? '';

    if (empty($idTutor)) {
        $errores[] = "Debe seleccionar un tutor.";
    }

    if (empty($idMateria)) {
        $errores[] = "Debe seleccionar una materia.";
    }

    if (
        empty($errores) &&
        $model->existeRelacion($idTutor, $idMateria)
    ) {
        $errores[] =
            "La relación ya existe.";
    }

    if (empty($errores)) {

        $model->crear(
            $idTutor,
            $idMateria
        );

        header(
            "Location: tutor_materia_listar.php"
        );
        exit;
    }
}

$tutores = $model->obtenerTutores();
$materias = $model->obtenerMaterias();

require_once __DIR__ .
    '/../views/tutor_materia/crear.php';