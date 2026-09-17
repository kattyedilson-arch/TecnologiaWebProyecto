<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

$model = new EstudianteModel($pdo);

$id = $_GET['id'] ?? null;

if ($id) {
    $model->eliminar($id);
}

header("Location: estudiantes_listar.php");
exit;