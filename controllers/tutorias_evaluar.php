<?php
// =========================================================
// CONTROLADOR: EVALUAR TUTORÍA (tutorias_evaluar.php)
// ---------------------------------------------------------
// Permite que el ESTUDIANTE califique (1 a 5 estrellas) una
// tutoría que ya fue marcada como 'realizada'.
//
// Flujo de dos pasos:
//   1) GET  ?id=N  -> valida permisos y muestra el formulario
//      de evaluación (views/tutorias/evaluar.php).
//   2) POST id_tutoria + calificacion + comentario -> valida
//      y guarda con EvaluacionModel::registrar (una evaluación
//      por tutoría mediante índice único).
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/EvaluacionModel.php';
require_once __DIR__ . '/../models/TutoriaModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

$rol = $_SESSION['rol'] ?? '';
$idUsuario = $_SESSION['id_usuario'] ?? 0;

// El estudiante siempre vuelve a su panel tras evaluar
$volver = '../views/estudiante/panel.php';

// Solo los estudiantes pueden evaluar tutorías
if ($rol !== 'estudiante') {
    setMensaje('danger', 'Solo los estudiantes pueden evaluar tutorías.');
    redirigir($volver);
}

// El id llega por GET (al abrir el formulario) o por POST (al guardar)
$idTutoria = $_GET['id'] ?? $_POST['id_tutoria'] ?? null;
if (!$idTutoria) {
    setMensaje('danger', 'Falta identificar la tutoría a evaluar.');
    redirigir($volver);
}

// La tutoría debe existir
$tutoriaModel = new TutoriaModel($pdo);
$tutoria = $tutoriaModel->obtenerPorId($idTutoria);
if (!$tutoria) {
    setMensaje('danger', 'La tutoría solicitada no existe.');
    redirigir($volver);
}

// Solo se evalúan sesiones completadas
if ($tutoria['estado'] !== 'realizada') {
    setMensaje('danger', 'Solo puedes evaluar tutorías que hayan sido completadas.');
    redirigir($volver);
}

// La tutoría debe pertenecer al estudiante que intenta evaluarla
$estudianteModel = new EstudianteModel($pdo);
$estudiantePerfil = $estudianteModel->obtenerPorUsuario($idUsuario);
if (!$estudiantePerfil || (int)$estudiantePerfil['id_estudiante'] !== (int)$tutoria['id_estudiante']) {
    setMensaje('danger', 'No tienes permisos para evaluar esta tutoría.');
    redirigir($volver);
}

$errores = [];

// ===== Guardar la evaluación (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir($volver);
    }

    $calificacion = (int)($_POST['calificacion'] ?? 0);
    $comentario = trim($_POST['comentario'] ?? '');

    // La calificación debe estar en el rango 1-5
    if ($calificacion < 1 || $calificacion > 5) {
        $errores[] = "Debes seleccionar una calificación de 1 a 5 estrellas.";
    }
    // Comentario con longitud máxima razonable
    if (mb_strlen($comentario) > 500) {
        $errores[] = "El comentario no puede superar los 500 caracteres.";
    }

    if (empty($errores)) {
        $evaluacionModel = new EvaluacionModel($pdo);
        try {
            // ON DUPLICATE KEY: si ya existía, la reemplaza
            $evaluacionModel->registrar($idTutoria, $calificacion, $comentario);
            setMensaje('success', '¡Gracias! Tu evaluación fue registrada correctamente.');
            redirigir($volver);
        } catch (PDOException $e) {
            $errores[] = "No se pudo registrar la evaluación. Inténtalo de nuevo.";
        }
    }

    // Si hubo errores, se conserva lo enviado para re-mostrar el formulario
    $tutoria['calificacion'] = $calificacion;
    $tutoria['ev_comentario'] = $comentario;
}

// Vista con el formulario de evaluación
require_once __DIR__ . '/../views/tutorias/evaluar.php';
