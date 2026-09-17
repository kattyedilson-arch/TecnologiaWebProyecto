<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutorModel.php';

$model = new TutorModel($pdo);

$id = $_GET['id'] ?? null;

if ($id) {
    $model->eliminar($id);
}

header("Location: tutores_listar.php");
exit;