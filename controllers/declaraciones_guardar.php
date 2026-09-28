<?php
// =========================================================
// CONTROLADOR: GUARDAR DECLARACIÓN (declaraciones_guardar.php)
// ---------------------------------------------------------
// Desde el formulario del estudiante: guarda borrador, envía a
// revisión o cancela. POST-only con CSRF. El estudiante solo
// puede operar SOBRE SU declaración del periodo activo mientras
// esté en estado editable (borrador, rechazada o cancelada).
// Al enviar, se notifica al equipo de MG.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/EstudianteModel.php';
require_once __DIR__ . '/../models/DeclaracionModel.php';
require_once __DIR__ . '/../models/ModalidadModel.php';
require_once __DIR__ . '/../models/PeriodoModel.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

requerirRol('estudiante');

$volver = 'estudiante_declaracion.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volver);
}

$accion = $_POST['accion'] ?? 'guardar';
$idUsuario = (int)$_SESSION['id_usuario'];

$estudianteModel = new EstudianteModel($pdo);
$estudiante = $estudianteModel->obtenerPorUsuario($idUsuario);
if (!$estudiante) {
    setMensaje('danger', 'Debes completar tu ficha de estudiante antes de declarar tu modalidad.');
    redirigir('/controllers/perfil.php');
}

$declaracionModel = new DeclaracionModel($pdo);
$periodoModel = new PeriodoModel($pdo);
$periodoActivo = $periodoModel->obtenerActivo();

if (!$periodoActivo) {
    setMensaje('danger', 'No hay un periodo académico abierto para declarar tu modalidad.');
    redirigir($volver);
}
if ($periodoActivo['estado'] !== 'abierto') {
    setMensaje('danger', 'El periodo actual está cerrado para las declaraciones.');
    redirigir($volver);
}

$declaraciones = $declaracionModel->obtenerPorEstudiante((int)$estudiante['id_estudiante']);
$declaracion = null;
foreach ($declaraciones as $d) {
    if ((int)$d['id_periodo'] === (int)$periodoActivo['id_periodo']) {
        $declaracion = $d;
        break;
    }
}

$estadosEditables = ['borrador', 'rechazada', 'cancelada'];

if ($accion === 'cancelar') {
    // Solo se puede cancelar una declaración propia en estado editable o enviada
    if (!$declaracion) {
        setMensaje('danger', 'No tienes una declaración activa en este periodo.');
        redirigir($volver);
    }
    if (!in_array($declaracion['estado'], array_merge($estadosEditables, ['enviada']), true)) {
        setMensaje('danger', 'Tu declaración ya fue resuelta y no puede cancelarse.');
        redirigir($volver);
    }
    $declaracionModel->cancelar((int)$declaracion['id_declaracion']);
    setMensaje('success', 'Declaración cancelada. Puedes volver a declarar cuando quieras.');
    redirigir($volver);
}

// Acciones 'guardar' (borrador) y 'enviar' (a revisión)
$idModalidad = (int)($_POST['id_modalidad'] ?? 0);
$titulo = limpiarTexto($_POST['titulo_proyecto'] ?? '');
$empresa = limpiarTexto($_POST['empresa_org'] ?? '');
$tutor = limpiarTexto($_POST['tutor_facultativo'] ?? '');

$modalidadModel = new ModalidadModel($pdo);
$modalidad = $idModalidad > 0 ? $modalidadModel->obtenerPorId($idModalidad) : null;

// Validaciones de negocio
if (!$modalidad || $modalidad['estado'] !== 'publicada') {
    setMensaje('danger', 'Selecciona una modalidad publicada del catálogo.');
    redirigir($volver);
}
if ($accion === 'enviar' && trim($titulo) === '') {
    setMensaje('danger', 'Indica el título o tema de tu trabajo final para enviar la declaración.');
    redirigir($volver);
}

try {
    if ($declaracion) {
        if (!in_array($declaracion['estado'], $estadosEditables, true)) {
            setMensaje('danger', 'Tu declaración ya está en trámite y no puede modificarse.');
            redirigir($volver);
        }
        $declaracionModel->actualizarDatos((int)$declaracion['id_declaracion'], [
            'id_modalidad'      => $idModalidad,
            'titulo_proyecto'   => $titulo,
            'empresa_org'       => $empresa,
            'tutor_facultativo' => $tutor,
        ]);
        $idDeclaracion = (int)$declaracion['id_declaracion'];
    } else {
        $idDeclaracion = (int)$declaracionModel->crear(
            (int)$estudiante['id_estudiante'],
            $idModalidad,
            (int)$periodoActivo['id_periodo']
        );
        if (!$idDeclaracion) {
            throw new PDOException('No se pudo crear la declaración.');
        }
        $declaracionModel->actualizarDatos($idDeclaracion, [
            'id_modalidad'      => $idModalidad,
            'titulo_proyecto'   => $titulo,
            'empresa_org'       => $empresa,
            'tutor_facultativo' => $tutor,
        ]);
    }

    if ($accion === 'enviar') {
        $declaracionModel->enviar($idDeclaracion);

        // Notifica al equipo de MG (coordinador y auxiliar) del nuevo ingreso
        $notificador = new NotificacionModel($pdo);
        $stmt = $pdo->prepare(
            "SELECT u.id_usuario, r.nombre_rol
             FROM usuarios u INNER JOIN roles r ON u.id_rol = r.id_rol
             WHERE r.nombre_rol IN ('coordinador_mg', 'auxiliar_mg') AND u.estado = 'activo'"
        );
        $stmt->execute();
        $nombreEstudiante = trim($estudiante['nombre'] . ' ' . $estudiante['apellido']);
        foreach ($stmt->fetchAll() as $miembro) {
            $notificador->crear(
                (int)$miembro['id_usuario'],
                'modalidad',
                'Nueva declaración de modalidad',
                $nombreEstudiante . ' envió su declaración del periodo ' . $periodoActivo['nombre'] . '.',
                '/controllers/mg_declaraciones.php?estado=enviada'
            );
        }

        setMensaje('success', 'Declaración enviada a revisión del equipo MG.');
    } else {
        setMensaje('success', 'Borrador guardado. Puedes editar o enviar cuando estés listo.');
    }
} catch (PDOException $e) {
    setMensaje('danger', 'No se pudo guardar la declaración. Ya tienes una declaración en este periodo: edítala desde tu listado.');
}

redirigir($volver);