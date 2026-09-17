<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutoriaModel.php';

$model = new TutoriaModel($pdo);

$id = $_GET['id'] ?? null;
$estado = $_GET['estado'] ?? null;

$estadosPermitidos = [
    'pendiente',
    'confirmada',
    'realizada',
    'cancelada'
];

if (
    $id &&
    in_array($estado, $estadosPermitidos, true)
) {
    $model->cambiarEstado($id, $estado);
}

header("Location: tutorias_listar.php");
exit;