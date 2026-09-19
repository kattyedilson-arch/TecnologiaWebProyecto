<?php
// =========================================================
// CONTROLADOR: LISTAR MATERIAS (materias_listar.php)
// ---------------------------------------------------------
// Carga el catálogo completo de materias y lo envía a la vista
// de listado.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/MateriaModel.php';

$materiaModel = new MateriaModel($pdo);
$materias = $materiaModel->obtenerTodas();

require_once __DIR__ . '/../views/materias/listar.php';