<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

$model = new EstudianteModel($pdo);

$estudiantes = $model->obtenerTodos();

require_once __DIR__ . '/../views/estudiantes/listar.php';