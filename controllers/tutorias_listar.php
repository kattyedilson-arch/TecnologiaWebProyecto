<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutoriaModel.php';

$model = new TutoriaModel($pdo);

$tutorias = $model->obtenerTodas();

require_once __DIR__ . '/../views/tutorias/listar.php';