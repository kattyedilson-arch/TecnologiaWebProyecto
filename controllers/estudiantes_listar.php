<?php
// =========================================================
// CONTROLADOR: LISTAR ESTUDIANTES (estudiantes_listar.php)
// ---------------------------------------------------------
// Carga el listado de estudiantes con su ficha académica
// (carrera, semestre, RU) y sus tutorías solicitadas, y lo
// envía a la vista.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

$estudianteModel = new EstudianteModel($pdo);
$estudiantes = $estudianteModel->obtenerTodos();

require_once __DIR__ . '/../views/estudiantes/listar.php';