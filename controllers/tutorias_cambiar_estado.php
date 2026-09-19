<?php
// =========================================================
// CONTROLADOR: CAMBIAR ESTADO DE TUTORÍA (tutorias_cambiar_estado.php)
// ---------------------------------------------------------
// Cambia el estado de una tutoría (confirmada/realizada/
// cancelada). Valida el estado, que la tutoría exista y aplica
// CONTROL DE PERMISOS por rol:
//   - administrador: puede hacer cualquier transición
//   - tutor: solo sobre SUS tutorías (aceptar, marcar realizada, cancelar)
//   - estudiante: solo cancelar las SUYAS (pendiente o confirmada)
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutoriaModel.php';
require_once __DIR__ . '/../models/TutorModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

// Datos recibidos por GET (enlaces) 
$idTutoria = $_REQUEST['id'] ?? null;
$nuevoEstado = $_REQUEST['estado'] ?? null;
$observaciones = $_REQUEST['observaciones'] ?? null;

$estadosValidos = ['pendiente', 'confirmada', 'realizada', 'cancelada'];
$rol = $_SESSION['rol'] ?? '';
$idUsuario = $_SESSION['id_usuario'] ?? 0;

// Destino de retorno según el rol (para redirigir al panel correcto)
$volver = 'tutorias_listar.php';
if ($rol === 'estudiante') $volver = '../views/estudiante/panel.php';
if ($rol === 'tutor') $volver = '../views/tutor/panel.php';

// Validaciones básicas de los parámetros
if (!$idTutoria || !in_array($nuevoEstado, $estadosValidos, true)) {
    setMensaje('danger', 'Datos inválidos para cambiar el estado.');
    redirigir($volver);
}

// Protección CSRF: las URLs de acción deben incluir el token de la sesión
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$tutoriaModel = new TutoriaModel($pdo);
$tutorModel = new TutorModel($pdo);
$estudianteModel = new EstudianteModel($pdo);

// La tutoría debe existir
$tutoria = $tutoriaModel->obtenerPorId($idTutoria);

if (!$tutoria) {
    setMensaje('danger', 'La tutoría solicitada no existe.');
    redirigir($volver);
}

// ===== Control de permisos por rol =====
$permitido = false;

if ($rol === 'administrador') {
    $permitido = true; // El admin gestiona todo
} elseif ($rol === 'tutor') {
    // El tutor solo puede gestionar SUS propias tutorías
    $tutorPerfil = $tutorModel->obtenerPorUsuario($idUsuario);
    if ($tutorPerfil && (int)$tutorPerfil['id_tutor'] === (int)$tutoria['id_tutor']) {
        $permitido = true;
    }
} elseif ($rol === 'estudiante') {
    // El estudiante solo puede cancelar SUS propias tutorías
    $estPerfil = $estudianteModel->obtenerPorUsuario($idUsuario);
    if ($estPerfil && (int)$estPerfil['id_estudiante'] === (int)$tutoria['id_estudiante'] && $nuevoEstado === 'cancelada') {
        $permitido = true;
    }
}

if (!$permitido) {
    setMensaje('danger', 'No tienes permisos para realizar esta acción.');
    redirigir($volver);
}

// ===== Restricciones de transición (reglas de negocio) =====
if ($rol !== 'administrador') {
    // El estudiante solo puede cancelar desde 'pendiente' o 'confirmada'
    if ($rol === 'estudiante' && $nuevoEstado === 'cancelada' && !in_array($tutoria['estado'], ['pendiente', 'confirmada'], true)) {
        setMensaje('danger', 'Solo puedes cancelar tutorías pendientes o confirmadas.');
        redirigir($volver);
    }
    // El tutor no puede cancelar una sesión ya realizada
    if ($rol === 'tutor' && $nuevoEstado === 'cancelada' && $tutoria['estado'] === 'realizada') {
        setMensaje('danger', 'No puedes cancelar una sesión ya realizada.');
        redirigir($volver);
    }
}

// ===== Aplicar el cambio de estado =====
try {
    $tutoriaModel->actualizarEstado($idTutoria, $nuevoEstado, $observaciones);
    setMensaje('success', 'Estado actualizado correctamente.');
} catch (PDOException $e) {
    setMensaje('danger', 'No se pudo actualizar el estado.');
}

// Redirección final según el rol
redirigir($volver);