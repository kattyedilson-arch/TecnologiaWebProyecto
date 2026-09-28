<?php
// =========================================================
// API JSON: REPORTES DEL SISTEMA (api_reportes.php)
// ---------------------------------------------------------
// Devuelve datos agregados para las gráficas del dashboard
// administrativo (Chart.js). Permiso: ver_reportes_mg
// (administrador y coordinador de MG). Siempre responde JSON.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';

header('Content-Type: application/json; charset=utf-8');

if (!tienePermiso('ver_reportes_mg')) {
    http_response_code(403);
    echo json_encode(['error' => 'No tienes permisos para ver los reportes.']);
    exit;
}

/**
 * Últimos N meses con su etiqueta "YYYY-MM" (incluye el mes actual).
 */
function ultimosMeses($n)
{
    $meses = [];
    for ($i = $n - 1; $i >= 0; $i--) {
        $meses[] = date('Y-m', strtotime("-$i months"));
    }
    return $meses;
}

// $pdo ya viene de config/conexion.php (require previo).

// Tutorías por estado (gráfica de dona)
$porEstado = $pdo->query(
    "SELECT estado, COUNT(*) AS total FROM tutorias GROUP BY estado"
)->fetchAll();

// Tutorías por mes (últimos 6 meses, gráfica de barras)
$listaMeses = ultimosMeses(6);
$porMesSql = $pdo->query(
    "SELECT DATE_FORMAT(fecha, '%Y-%m') AS mes, COUNT(*) AS total
     FROM tutorias
     WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
     GROUP BY mes"
)->fetchAll();
$porMesMap = [];
foreach ($porMesSql as $fila) {
    $porMesMap[$fila['mes']] = (int)$fila['total'];
}
$porMes = [];
foreach ($listaMeses as $mes) {
    $porMes[] = ['mes' => $mes, 'total' => $porMesMap[$mes] ?? 0];
}

// Tutores con más sesiones (top 5, barra horizontal)
$topTutoresReporte = $pdo->query(
    "SELECT u.nombre, u.apellido, COUNT(tu.id_tutoria) AS total
     FROM tutores t
     INNER JOIN usuarios u ON t.id_usuario = u.id_usuario
     INNER JOIN tutorias tu ON t.id_tutor = tu.id_tutor AND tu.estado = 'realizada'
     GROUP BY t.id_tutor, u.nombre, u.apellido
     ORDER BY total DESC
     LIMIT 5"
)->fetchAll();

// Distribución por nivel académico (pregrado/posgrado/verano/invierno)
$porNivel = $pdo->query(
    "SELECT nivel_academico, COUNT(*) AS total
     FROM tutorias
     WHERE nivel_academico IS NOT NULL AND nivel_academico <> ''
     GROUP BY nivel_academico"
)->fetchAll();

echo json_encode([
    'porEstado'         => $porEstado,
    'tutoriasPorMes'    => $porMes,
    'topTutores'        => $topTutoresReporte,
    'porNivel'          => $porNivel,
], JSON_UNESCAPED_UNICODE);