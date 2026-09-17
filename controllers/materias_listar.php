<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/MateriaModel.php';

$model = new MateriaModel($pdo);

$materias = $model->obtenerTodas();

require_once __DIR__ . '/../views/materias/listar.php';