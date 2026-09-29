<?php
// =========================================================
// CONTROLADOR: DECLARACIONES MG (mg_declaraciones.php)
// ---------------------------------------------------------
// Banda de trabajo del equipo de Modalidades de Grado. Lista
// todas las declaraciones con filtro por estado y periodo, para
// que el auxiliar las tramite y el coordinador las apruebe o
// rechace. Requiere: ver_panel_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/DeclaracionModel.php';
require_once __DIR__ . '/../models/PeriodoModel.php';

requerirPermiso('ver_panel_mg');

$declaracionModel = new DeclaracionModel($pdo);
$periodoModel = new PeriodoModel($pdo);

// Filtros recibidos por GET
$filtroEstado = $_GET['estado'] ?? '';
$estadosValidos = ['borrador', 'enviada', 'en_revision', 'aprobada', 'rechazada', 'cancelada'];
if (!in_array($filtroEstado, $estadosValidos, true)) {
    $filtroEstado = '';
}

$filtroPeriodo = (int)($_GET['periodo'] ?? 0);
$periodos = $periodoModel->obtenerTodos();

$declaraciones = $declaracionModel->obtenerLista($filtroEstado, $filtroPeriodo > 0 ? $filtroPeriodo : null);

// Conteos por estado para las pestañas de filtro (del periodo elegido)
$periodoConteo = $filtroPeriodo > 0 ? $filtroPeriodo : null;
$conteos = ['borrador' => 0, 'enviada' => 0, 'en_revision' => 0, 'aprobada' => 0, 'rechazada' => 0, 'cancelada' => 0, 'total' => 0];
if ($periodos) {
    foreach ($periodos as $periodo) {
        if ($periodoConteo !== null && (int)$periodo['id_periodo'] !== $periodoConteo) {
            continue;
        }
        $c = $declaracionModel->contarPorEstado((int)$periodo['id_periodo']);
        foreach ($c as $clave => $valor) {
            if (isset($conteos[$clave])) {
                $conteos[$clave] += $valor;
            }
        }
    }
}

$puedeAprobar = tienePermiso('aprobar_modalidad_mg');
$puedeTramitar = tienePermiso('tramitar_modalidad_mg');

$tituloPagina = 'Declaraciones de Modalidad MG';
require_once __DIR__ . '/../views/mg/declaraciones.php';