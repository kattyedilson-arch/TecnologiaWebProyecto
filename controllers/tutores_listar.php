<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutorModel.php';

$model = new TutorModel($pdo);

$tutores = $model->obtenerTodos();

require_once __DIR__ . '/../views/tutores/listar.php';