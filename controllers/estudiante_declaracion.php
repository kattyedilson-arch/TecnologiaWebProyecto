<?php
// =========================================================
// CONTROLADOR: DECLARACIÓN DE MODALIDAD (estudiante_declaracion.php)
// ---------------------------------------------------------
// Página del estudiante para declarar su modalidad de grado.
// Muestra su declaración del periodo (si existe) o el
// formulario de nueva declaración (solo con periodo abierto
// y modalidades publicadas). El historial de periodos
// anteriores se muestra más abajo como referencia.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/EstudianteModel.php';
require_once __DIR__ . '/../models/DeclaracionModel.php';
require_once __DIR__ . '/../models/ModalidadModel.php';
require_once __DIR__ . '/../models/PeriodoModel.php';

requerirRol('estudiante');

$idUsuario = (int)$_SESSION['id_usuario'];
$estudianteModel = new EstudianteModel($pdo);
$estudiante = $estudianteModel->obtenerPorUsuario($idUsuario);

if (!$estudiante) {
    setMensaje('danger', 'Debes completar tu ficha de estudiante antes de declarar tu modalidad.');
    redirigir('/controllers/perfil.php');
}

$declaracionModel = new DeclaracionModel($pdo);
$modalidadModel = new ModalidadModel($pdo);
$periodoModel = new PeriodoModel($pdo);

$periodoActivo = $periodoModel->obtenerActivo();
$declaraciones = $declaracionModel->obtenerPorEstudiante((int)$estudiante['id_estudiante']);
$modalidadesPublicadas = $modalidadModel->obtenerPublicadas();

// La declaración "actual" es la del periodo activo (o null)
$declaracionActual = null;
if ($periodoActivo) {
    foreach ($declaraciones as $d) {
        if ((int)$d['id_periodo'] === (int)$periodoActivo['id_periodo']) {
            $declaracionActual = $d;
            break;
        }
    }
}

// Estados que permiten al estudiante seguir editando (borrador, rechazada o cancelada)
$editable = $declaracionActual && in_array($declaracionActual['estado'], ['borrador', 'rechazada', 'cancelada'], true);

// Para declaraciones aprobadas: jurado asignado y checklist de avales visible
$miJurado = [];
$misAvales = [];
$miActa = null;
if ($declaracionActual && $declaracionActual['estado'] === 'aprobada') {
    require_once __DIR__ . '/../models/JuradoModel.php';
    require_once __DIR__ . '/../models/AvalModel.php';
    require_once __DIR__ . '/../models/ActaModel.php';
    $juradoModel = new JuradoModel($pdo);
    $avalModel = new AvalModel($pdo);
    $miJurado = $juradoModel->obtenerPorDeclaracion((int)$declaracionActual['id_declaracion']);
    $misAvales = $avalModel->obtenerPorDeclaracion((int)$declaracionActual['id_declaracion']);
    $miActa = (new ActaModel($pdo))->obtenerPorDeclaracion((int)$declaracionActual['id_declaracion']);
}

// Tutor MG asignado al expediente actual (si lo hay)
$miTutorMg = null;
if ($declaracionActual) {
    require_once __DIR__ . '/../models/AsignacionTutorModel.php';
    $miTutorMg = (new AsignacionTutorModel($pdo))->obtenerVigente((int)$declaracionActual['id_declaracion']);
}

$tituloPagina = 'Modalidad de Grado';
require_once __DIR__ . '/../views/estudiante/declaracion.php';