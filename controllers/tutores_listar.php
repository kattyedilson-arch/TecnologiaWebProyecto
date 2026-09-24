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

// Reseñas de todos los tutores en una sola consulta (evita N+1),
// agrupadas en PHP para mantener el contrato de la vista.
$resenasPorTutor = [];
foreach ($tutorModel->obtenerResenasDeTodos() as $resena) {
    $resenasPorTutor[$resena['id_tutor']][] = $resena;
}

require_once __DIR__ . '/../views/tutores/listar.php';