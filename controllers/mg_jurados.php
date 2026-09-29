<?php
// =========================================================
// CONTROLADOR: TRIBUNALES Y AVALES (mg_jurados.php)
// ---------------------------------------------------------
// Lista las declaraciones aprobadas y su grado de avance:
// jurado asignado + avales entregados. Enlaza a la gestión
// detallada (mg_tribunal.php). Requiere: ver_panel_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/JuradoModel.php';

requerirPermiso('ver_panel_mg');

$juradoModel = new JuradoModel($pdo);
$aprobadas = $juradoModel->obtenerResumenAprobadas();

$tituloPagina = 'Tribunales y Avales';
require_once __DIR__ . '/../views/mg/jurados.php';