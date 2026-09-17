<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutorMateriaModel.php';

$model = new TutorMateriaModel($pdo);

$idTutor = $_GET['id_tutor'] ?? null;
$idMateria = $_GET['id_materia'] ?? null;

if ($idTutor && $idMateria) {

    $model->eliminar(
        $idTutor,
        $idMateria
    );
}

header("Location: tutor_materia_listar.php");
exit;