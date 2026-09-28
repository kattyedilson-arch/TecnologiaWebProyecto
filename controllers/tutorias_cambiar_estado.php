<?php
// =========================================================
// CONTROLADOR: CAMBIAR ESTADO DE TUTORÍA (tutorias_cambiar_estado.php)
// ---------------------------------------------------------
// Cambia el estado de una tutoría (confirmada/en_proceso/
// realizada/cancelada). Valida el estado, que la tutoría exista,
// aplica CONTROL DE PERMISOS por rol y RESTRICCIONES de
// transición:
//   Ciclo: pendiente -> confirmada -> en_proceso -> realizada (o cancelada)
//   - administrador: puede hacer cualquier transición
//   - tutor: solo sobre SUS tutorías (aceptar, iniciar, marcar
//     realizada, cancelar mientras no esté realizada)
//   - estudiante: solo cancelar las SUYAS (pendiente o confirmada)
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutoriaModel.php';
require_once __DIR__ . '/../models/TutorModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

// Solo se aceptan envíos por POST (evita cambios de estado destructivos por GET)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    setMensaje('danger', 'Operación no válida.');
    redirigir($_SESSION['rol'] === 'administrador' ? 'tutorias_listar.php' : '../index.php');
}

// Datos recibidos por POST (formularios con token CSRF)
$idTutoria = $_POST['id'] ?? null;
$nuevoEstado = $_POST['estado'] ?? null;
$observaciones = $_POST['observaciones'] ?? null;

$estadosValidos = ['pendiente', 'confirmada', 'en_proceso', 'realizada', 'cancelada'];

// Transiciones permitidas para no-admin:
//   confirmada  <- pendiente   (aceptar)
//   en_proceso  <- confirmada  (iniciar sesión)
//   realizada   <- en_proceso  (finalizar)
//   cancelada   <- pendiente/confirmada/en_proceso
$transicionesPermitidas = [
    'confirmada' => ['pendiente'],
    'en_proceso' => ['confirmada'],
    'realizada'  => ['en_proceso'],
    'cancelada'  => ['pendiente', 'confirmada', 'en_proceso']
];

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
if ($rol !== 'administrador' && $nuevoEstado !== $tutoria['estado']) {
    $permitidosDesde = $transicionesPermitidas[$nuevoEstado] ?? [];
    if (!in_array($tutoria['estado'], $permitidosDesde, true)) {
        setMensaje('danger', 'No puedes cambiar de "' . $tutoria['estado'] . '" a "' . $nuevoEstado . '".');
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

// ===== Notificaciones: avisa a los implicados según el nuevo estado =====
// (solo cuando el estado efectivamente cambió)
if ($nuevoEstado !== $tutoria['estado']) {
    require_once __DIR__ . '/../models/NotificacionModel.php';
    $notificador = new NotificacionModel($pdo);

    // Resuelve el id_usuario del tutor y del estudiante de la tutoría
    $auxEst = $pdo->prepare("SELECT id_usuario FROM estudiantes WHERE id_estudiante = :id");
    $auxEst->execute([':id' => $tutoria['id_estudiante']]);
    $idUsuarioEstudiante = (int)($auxEst->fetchColumn());
    $auxTut = $pdo->prepare("SELECT id_usuario FROM tutores WHERE id_tutor = :id");
    $auxTut->execute([':id' => $tutoria['id_tutor']]);
    $idUsuarioTutor = (int)($auxTut->fetchColumn());

    $nombreEstudiante = trim(($tutoria['est_nombre'] ?? '') . ' ' . ($tutoria['est_apellido'] ?? ''));
    $fechaTutoria = date('d/m/Y', strtotime($tutoria['fecha']));

    if ($nuevoEstado === 'cancelada') {
        // Avisa a la otra parte involucrada (ambos si lo hace el administrador)
        if ($rol !== 'estudiante' && $idUsuarioEstudiante > 0) {
            $notificador->crear($idUsuarioEstudiante, 'tutoria', 'Tutoría cancelada',
                'Tu tutoría programada para el ' . $fechaTutoria . ' fue cancelada.', '/views/estudiante/panel.php');
        }
        if ($rol !== 'tutor' && $idUsuarioTutor > 0) {
            $notificador->crear($idUsuarioTutor, 'tutoria', 'Tutoría cancelada',
                'La tutoría del ' . $fechaTutoria . ' con ' . $nombreEstudiante . ' fue cancelada.', '/views/tutor/panel.php');
        }
    } elseif (in_array($nuevoEstado, ['confirmada', 'en_proceso', 'realizada'], true)) {
        $mensajesMap = [
            'confirmada'  => ['Tu tutoría fue aceptada', 'El tutor aceptó tu tutoría del ' . $fechaTutoria . '.'],
            'en_proceso'  => ['Tu tutoría está en proceso', 'El tutor inició tu tutoría del ' . $fechaTutoria . '.'],
            'realizada'   => ['Tutoría realizada', 'Tu tutoría del ' . $fechaTutoria . ' fue marcada como realizada.'],
        ];
        if ($idUsuarioEstudiante > 0) {
            $notificador->crear($idUsuarioEstudiante, 'tutoria', $mensajesMap[$nuevoEstado][0],
                $mensajesMap[$nuevoEstado][1], '/views/estudiante/panel.php');
        }
    }
}

// Redirección final según el rol
redirigir($volver);