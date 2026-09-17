<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/MateriaModel.php';

$model = new MateriaModel($pdo);

$id = $_GET['id'] ?? null;

if ($id) {
    $model->eliminar($id);
}

header("Location: materias_listar.php");
exit;