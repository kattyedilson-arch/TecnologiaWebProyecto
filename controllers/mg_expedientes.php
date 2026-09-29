<?php
// =========================================================
// CONTROLADOR: LISTADO DE EXPEDIENTES MG (mg_expedientes.php)
// ---------------------------------------------------------
// HU-024. Listado paginado de expedientes (declaraciones_modalidad)
// con búsqueda por estudiante/registro y filtros por cohorte,
// modalidad, etapa (previa/mg1/mg2) y estado. Acceso de lectura:
// ver_expediente_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ExpedienteMgModel.php';
require_once __DIR__ . '/../models/CohorteMgModel.php';

requerirPermiso('ver_expediente_mg');

$expedienteModel = new ExpedienteMgModel($pdo);
$cohorteModel = new CohorteMgModel($pdo);

$filtros = [
    'id_cohorte'  => (int)($_GET['id_cohorte'] ?? 0),
    'id_modalidad'=> (int)($_GET['id_modalidad'] ?? 0),
    'etapa_actual'=> trim((string)($_GET['etapa_actual'] ?? '')),
    'estado'      => trim((string)($_GET['estado'] ?? '')),
];

$resultado = $expedienteModel->listar($filtros);
$resumen = $expedienteModel->contarResumen();
$cohortes = $cohorteModel->obtenerVigentes();
$modalidades = $expedienteModel->listarModalidades();

$tituloPagina = 'Expedientes MG';
require_once __DIR__ . '/../views/mg/expedientes.php';