<?php
// =========================================================
// CONTROLADOR: LISTAR TUTORÍAS (tutorias_listar.php)
// ---------------------------------------------------------
// Vista de gestión de tutorías del ADMINISTRADOR. Aplica un
// filtro por estado (pendiente/confirmada/realizada/cancelada)
// leído desde GET y calcula las métricas globales por estado.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutoriaModel.php';

requerirRol('administrador');

$tutoriaModel = new TutoriaModel($pdo);

// Filtro por estado desde GET (?estado=pendiente, etc.)
$filtroEstado = $_GET['estado'] ?? null;
if (!in_array($filtroEstado, ['pendiente', 'confirmada', 'en_proceso', 'realizada', 'cancelada'])) {
    $filtroEstado = null; // Cualquier otro valor se ignora (se muestran todas)
}

$tutorias = $tutoriaModel->obtenerTodas($filtroEstado);
$metricas = $tutoriaModel->obtenerMetricasGlobales();

require_once __DIR__ . '/../views/tutorias/listar.php';