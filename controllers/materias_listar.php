<?php
// =========================================================
// CONTROLADOR: LISTAR MATERIAS (materias_listar.php)
// ---------------------------------------------------------
// Carga el catálogo completo de materias y lo envía a la vista
// de listado.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/MateriaModel.php';

// Solo el administrador gestiona las materias
verificarRol('administrador');

$materiaModel = new MateriaModel($pdo);
$materias = $materiaModel->obtenerTodas();

require_once __DIR__ . '/../views/materias/listar.php';