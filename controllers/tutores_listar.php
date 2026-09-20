<?php
// =========================================================
// CONTROLADOR: LISTAR TUTORES (tutores_listar.php)
// ---------------------------------------------------------
// Carga el listado de docentes tutores con sus estadísticas
// (materias y horarios asignados) y lo envía a la vista.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutorModel.php';

requerirRol('administrador');

$tutorModel = new TutorModel($pdo);
$tutores = $tutorModel->obtenerTodos();

require_once __DIR__ . '/../views/tutores/listar.php';