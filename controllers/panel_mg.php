<?php
// =========================================================
// CONTROLADOR: PANEL DEL EQUIPO MG (panel_mg.php)
// ---------------------------------------------------------
// Página de inicio para coordinador_mg y auxiliar_mg (HU-021).
// Muestra estadísticas del año: modalidades publicadas del
// catálogo, periodo abierto y contadores de las declaraciones
// de modalidad (proceso de grado) del periodo.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ModalidadModel.php';
require_once __DIR__ . '/../models/PeriodoModel.php';

requerirPermiso('ver_panel_mg');

$modalidadModel = new ModalidadModel($pdo);
$periodoModel = new PeriodoModel($pdo);

$periodoActivo = $periodoModel->obtenerActivo();
$modalidadesPublicadas = count($modalidadModel->obtenerPublicadas());

$resumen = ['declaraciones' => 0, 'en_revision' => 0, 'aprobadas' => 0];
if ($periodoActivo) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total,
                SUM(estado = 'en_revision') AS en_revision,
                SUM(estado = 'aprobada') AS aprobadas
         FROM declaraciones_modalidad
         WHERE id_periodo = :periodo"
    );
    $stmt->execute([':periodo' => $periodoActivo['id_periodo']]);
    $resumen = $stmt->fetch();
}

$tituloPagina = 'Panel de Modalidades de Grado';
require_once __DIR__ . '/../views/mg/panel.php';