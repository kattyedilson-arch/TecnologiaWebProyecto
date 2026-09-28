<?php
// =========================================================
// CONTROLADOR: DASHBOARD DEL ADMINISTRADOR (dashboard.php)
// ---------------------------------------------------------
// Panel central de resumen del sistema. Solo accesible para
// el rol 'administrador'; tutor y estudiante son redirigidos
// a sus propios paneles. Carga las métricas globales, las
// últimas tutorías, el top de tutores y las materias top.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/DashboardModel.php';
require_once __DIR__ . '/../models/ReporteModel.php';

// Solo el administrador ingresa al panel central
if (($_SESSION['rol'] ?? '') !== 'administrador') {
    redirigirAlPanel(); // Tutor -> su panel, Estudiante -> su panel
}

$dashboardModel = new DashboardModel($pdo);

// Datos para las tarjetas, tablas y rankings de la vista
$resumen = $dashboardModel->obtenerResumenGlobal();
$ultimasTutorias = $dashboardModel->obtenerUltimasTutorias(6);
$topTutores = $dashboardModel->obtenerTopTutores(5);
$materiasTop = $dashboardModel->obtenerMateriasTop(5);
$desgloseNivel = $dashboardModel->obtenerDesgloseNivelAcademico();
$resumenOfertas = $dashboardModel->obtenerResumenOfertas();
$resumenTurnos = $dashboardModel->obtenerResumenTurnos();

// Resumen del módulo de Modalidades de Grado (sin filtro de periodo)
$reporteModel = new ReporteModel($pdo);
$resumenMG = $reporteModel->resumen(null);
$topModalidadesMG = $reporteModel->topModalidades(null, 4);

// Defensa por si el promedio llega como null (no hay evaluaciones aún)
$resumen['promedio_evaluaciones'] = $resumen['promedio_evaluaciones'] ?? 0;

// Se pasa todo a la vista de presentación
require_once __DIR__ . '/../views/dashboard/index.php';