<?php
// =========================================================
// CONTROLADOR: REPORTES MG (reportes_mg.php)
// ---------------------------------------------------------
// Panel de estadísticas del módulo de modalidades de grado:
// conteos por estado, avance de tribunales/avales, notas de
// actas y top de modalidades. Filtrable por periodo. Permiso:
// ver_panel_mg (admin, coordinador MG, auxiliar MG).
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';

requerirPermiso('ver_panel_mg');

require_once __DIR__ . '/../models/ReporteModel.php';
require_once __DIR__ . '/../models/PeriodoModel.php';

$periodos = (new PeriodoModel($pdo))->obtenerTodos();
$idPeriodo = (int)($_GET['periodo'] ?? 0);
$periodoElegido = null;
foreach ($periodos as $periodo) {
    if ((int)$periodo['id_periodo'] === $idPeriodo) {
        $periodoElegido = $periodo;
        break;
    }
}
if (!$periodoElegido) {
    $idPeriodo = 0;
}

$reporte = new ReporteModel($pdo);
$resumen = $reporte->resumen($idPeriodo > 0 ? $idPeriodo : null);
$topModalidades = $reporte->topModalidades($idPeriodo > 0 ? $idPeriodo : null);
$sinJurado = $reporte->aprobadasSinJuradoLista($idPeriodo > 0 ? $idPeriodo : null);
$recientes = $reporte->actividadReciente($idPeriodo > 0 ? $idPeriodo : null);

$estadoBadges = [
    'borrador'   => ['bg-secondary bg-opacity-10 text-secondary border', 'bi-file-earmark'],
    'enviada'    => ['bg-primary bg-opacity-10 text-primary border border-primary-subtle', 'bi-send'],
    'en_revision'=> ['bg-info bg-opacity-10 text-info border', 'bi-search'],
    'aprobada'   => ['bg-success bg-opacity-10 text-success border border-success-subtle', 'bi-check2-circle'],
    'rechazada'  => ['bg-danger bg-opacity-10 text-danger border border-danger-subtle', 'bi-x-circle'],
    'cancelada'  => ['bg-warning bg-opacity-10 text-warning border', 'bi-slash-circle'],
];

require_once __DIR__ . '/../views/mg/reportes.php';