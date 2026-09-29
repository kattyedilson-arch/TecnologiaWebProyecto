<?php
// =========================================================
// CONTROLADOR: REGISTRAR INTERÉS EN UNA MATERIA SIN DOCENTE
// (interes_registrar.php)
// ---------------------------------------------------------
// El estudiante ve en "Materias Disponibles" las ofertas de las
// materias de su carrera. Una oferta 'asignada' se reserva aquí
// mismo; una oferta 'abierta' todavía no tiene docente, porque
// ningún tutor la ha aceptado todavía.
//
// Este endpoint NO agenda nada: tutorias.id_tutor es NOT NULL y
// no hay quién imparta la sesión. Registra el INTERÉS del
// estudiante en solicitudes_interes, avisa a los administradores
// activos para que busquen docente y deja al estudiante a la
// espera hasta que un tutor acepte la oferta.
//
// Acepta únicamente POST con token CSRF.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/OfertaModel.php';
require_once __DIR__ . '/../models/SolicitudInteresModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';
require_once __DIR__ . '/../models/MateriaModel.php';
require_once __DIR__ . '/../models/TutoriaModel.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

// Destino de retorno: siempre la pantalla de materias del estudiante
$volver = 'tutorias_solicitar.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    setMensaje('danger', 'Operación no válida.');
    redirigir($volver);
}

$rolSesion = $_SESSION['rol'] ?? '';
$idUsuario = $_SESSION['id_usuario'] ?? 0;

if ($rolSesion !== 'estudiante') {
    setMensaje('danger', 'Solo los estudiantes pueden registrar interés en una materia.');
    redirigirAlPanel();
    exit;
}

if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$idOferta      = (int)($_POST['id_oferta'] ?? 0);
$mensaje       = trim((string)($_POST['mensaje'] ?? ''));
$mensajeLargo  = mb_strlen($mensaje);

if ($mensajeLargo > 500) {
    setMensaje('danger', 'La nota para la administración no puede superar los 500 caracteres.');
    redirigir($volver);
}

$ofertaModel      = new OfertaModel($pdo);
$interesModel     = new SolicitudInteresModel($pdo);
$estudianteModel  = new EstudianteModel($pdo);
$materiaModel     = new MateriaModel($pdo);
$tutoriaModel     = new TutoriaModel($pdo);
$notificacionModel = new NotificacionModel($pdo);

$estudiante = $estudianteModel->obtenerPorUsuario($idUsuario);
if (!$estudiante) {
    setMensaje('danger', 'No se encontró tu ficha de estudiante.');
    redirigir($volver);
}
$idEstudiante = (int)$estudiante['id_estudiante'];

// La oferta debe existir y estar ABIERTA: si ya tiene docente el
// estudiante tiene que reservarla, no registrar su interés.
$oferta = $idOferta ? $ofertaModel->obtenerPorId($idOferta) : false;

if (!$oferta) {
    setMensaje('danger', 'Ese horario ya no está disponible.');
    redirigir($volver);
}

if ($oferta['estado'] !== 'abierta' || !empty($oferta['id_tutor_assigned'])) {
    setMensaje('info', 'Esa materia ya tiene docente asignado. Ya puedes reservarla desde Materias Disponibles.');
    redirigir($volver);
}

// La materia debe ser de la carrera del estudiante, igual que en la reserva.
$materiasCarrera = array_column($materiaModel->obtenerPorCarrera($estudiante['id_carrera']), 'id_materia');
if (!in_array((int)$oferta['id_materia'], array_map('intval', $materiasCarrera), true)) {
    setMensaje('danger', 'La oferta seleccionada no corresponde a las materias de tu carrera.');
    redirigir($volver);
}

// Mismo bloqueo que al reservar: una sola solicitud viva por materia.
if ($tutoriaModel->tieneMateriaActiva($idEstudiante, $oferta['id_materia'])) {
    setMensaje('danger', 'Ya tienes una solicitud activa para esta materia. Cancela la anterior si deseas cambiarla.');
    redirigir($volver);
}

// Si ya había un interés vivo en esta oferta no se duplica: se
// avisa al administrador solo la primera vez.
$interesPrevio = $interesModel->obtenerInteresDeEstudiante($idEstudiante, $idOferta);
$yaEstabaVivo = $interesPrevio && $interesPrevio['estado'] !== 'cancelada';

try {
    $idSolicitud = $interesModel->registrar(
        $idEstudiante,
        $oferta['id_materia'],
        $idOferta,
        $mensaje
    );
} catch (PDOException $e) {
    error_log('interes_registrar: ' . $e->getMessage());
    $idSolicitud = false;
}

if (!$idSolicitud) {
    setMensaje('danger', 'No se pudo registrar tu solicitud. Intenta de nuevo en unos minutos.');
    redirigir($volver);
}

if ($yaEstabaVivo) {
    setMensaje('info', 'Tu solicitud de esta materia ya estaba registrada. Te avisaremos en cuanto haya docente.');
    redirigir($volver);
}

// ---- Aviso a los administradores activos ----
$stmt = $pdo->prepare(
    "SELECT u.id_usuario
     FROM usuarios u
     INNER JOIN roles r ON u.id_rol = r.id_rol
     WHERE r.nombre_rol = 'administrador' AND u.estado = 'activo'"
);
$stmt->execute();

$nombreEstudiante = trim($_SESSION['nombre'] ?? '');
$detalle = $nombreEstudiante !== '' ? ' ' . $nombreEstudiante : '';
$nota = $mensaje !== '' ? ' Nota: "' . $mensaje . '"' : '';
$mensajeNotif = 'Un estudiante' . $detalle . ' espera docente para la oferta de '
    . ($oferta['nombre_materia'] ?? 'materia') . ' (' . ($oferta['nombre_turno'] ?? 'turno') . ').' . $nota;

foreach ($stmt->fetchAll() as $admin) {
    $notificacionModel->crear(
        (int)$admin['id_usuario'],
        'sistema',
        'Estudiante espera docente',
        $mensajeNotif,
        '/controllers/ofertas_listar.php'
    );
}

setMensaje('success', 'Registraste tu interés en ' . ($oferta['nombre_materia'] ?? 'la materia') . '. Te avisaremos en cuanto se asigne un docente.');
redirigir($volver);
