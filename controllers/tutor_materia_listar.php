<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutorMateriaModel.php';

$model = new TutorMateriaModel($pdo);

$relaciones = $model->obtenerTodas();

require_once __DIR__ . '/../views/tutor_materia/listar.php';