<?php
// =========================================================
// CONTROLADOR: OFERTAS PARA EL TUTOR (ofertas_tutor.php)
// ---------------------------------------------------------
// El tutor ve las ofertas abiertas y puede aceptarlas o rechazarlas.
// Al aceptar, se crea automáticamente la disponibilidad y la
// materia se asigna al tutor.
// Solo tutores tienen acceso.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/OfertaModel.php';
require_once __DIR__ . '/../models/TutorModel.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

$rolSesion = $_SESSION['rol'] ?? '';
$idUsuario = $_SESSION['id_usuario'] ?? 0;

// Solo tutores pueden acceder
if ($rolSesion !== 'tutor') {
    setMensaje('danger', 'Solo los tutores pueden acceder a esta página.');
    redirigir($rolSesion === 'administrador' ? 'tutores_listar.php' : '../views/estudiante/panel.php');
}

$tutorModel = new TutorModel($pdo);
$ofertaModel = new OfertaModel($pdo);
$notifModel = new NotificacionModel($pdo);

// Obtener perfil del tutor
$tutorActual = $tutorModel->obtenerPorUsuario($idUsuario);
if (!$tutorActual) {
    setMensaje('danger', 'No se encontró tu perfil de tutor.');
    redirigir('../views/tutor/panel.php');
}
$idTutor = $tutorActual['id_tutor'];

// ===== Procesar acciones POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('ofertas_tutor.php');
    }

    $accion = $_POST['accion'] ?? '';
    $idOferta = (int)($_POST['id_oferta'] ?? 0);

    // ACCIÓN: Aceptar oferta
    if ($accion === 'aceptar_oferta' && $idOferta) {
        $oferta = $ofertaModel->obtenerPorId($idOferta);
        if ($oferta && $oferta['estado'] === 'abierta') {
            $resultado = $ofertaModel->responder($idOferta, $idTutor, 'aceptada');
            if ($resultado) {
                setMensaje('success', 'Has aceptado la oferta. La materia y horario se agregaron a tu disponibilidad.');
                notificarAdminOferta($pdo, $notifModel, $tutorActual, $oferta, 'aceptado');
            } else {
                setMensaje('danger', 'Error al procesar la aceptación. Intenta de nuevo.');
            }
        } else {
            setMensaje('danger', 'La oferta ya no está disponible.');
        }
        redirigir('ofertas_tutor.php');
    }

    // ACCIÓN: Rechazar oferta
    if ($accion === 'rechazar_oferta' && $idOferta) {
        $oferta = $ofertaModel->obtenerPorId($idOferta);
        $ofertaModel->responder($idOferta, $idTutor, 'rechazada');
        if ($tutorActual && $oferta) {
            notificarAdminOferta($pdo, $notifModel, $tutorActual, $oferta, 'rechazado');
        }
        setMensaje('info', 'Has rechazado la oferta.');
        redirigir('ofertas_tutor.php');
    }

    // ACCIÓN: Cancelar aceptación
    if ($accion === 'cancelar_aceptacion' && $idOferta) {
        $resultado = $ofertaModel->cancelarAceptacion($idOferta, $idTutor);
        if ($resultado) {
            setMensaje('success', 'Has cancelado tu aceptación. La disponibilidad fue removida.');
        } else {
            setMensaje('danger', 'Error al cancelar. Intenta de nuevo.');
        }
        redirigir('ofertas_tutor.php');
    }
}

// Avisa a todos los administradores activos que un tutor respondió una oferta
function notificarAdminOferta($pdo, $notifModel, $tutor, $oferta, $verbo)
{
    $titulo = 'Oferta ' . ($verbo === 'aceptado' ? 'aceptada' : 'rechazada');
    $mensaje = 'El tutor ' . trim(($tutor['nombre'] ?? '') . ' ' . ($tutor['apellido'] ?? ''))
        . ' ha ' . $verbo . ' la oferta de ' . ($oferta['nombre_materia'] ?? 'materia')
        . ' (' . ($oferta['nombre_turno'] ?? 'turno') . ').';
    $stmt = $pdo->prepare(
        "SELECT u.id_usuario
         FROM usuarios u INNER JOIN roles r ON u.id_rol = r.id_rol
         WHERE r.nombre_rol = 'administrador' AND u.estado = 'activo'"
    );
    $stmt->execute();
    foreach ($stmt->fetchAll() as $admin) {
        $notifModel->crear((int)$admin['id_usuario'], 'sistema', $titulo, $mensaje, '/controllers/ofertas_listar.php');
    }
}

// Datos para la vista
$ofertasAbiertas = $ofertaModel->obtenerAbiertasParaTutor($idTutor);
$misRespuestas = $ofertaModel->obtenerRespuestasTutor($idTutor);

require_once __DIR__ . '/../views/tutor/ofertas.php';
