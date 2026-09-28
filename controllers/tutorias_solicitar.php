<?php
// =========================================================
// CONTROLADOR: SOLICITAR TUTORÍA (tutorias_solicitar.php)
// ---------------------------------------------------------
// Pantalla donde el ESTUDIANTE solicita una nueva sesión de
// tutoría a partir de los HORARIOS PREESTABLECIDOS por el
// administrador (ofertas asignadas a un tutor). El estudiante
// NO elige aula, modalidad ni fecha: solo selecciona la materia
// y uno de los horarios publicados.
// El sistema deriva tutor, turno, modalidad y aula desde la oferta elegida,
// y asigna automáticamente la fecha de la sesión (primer día libre del turno).
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutoriaModel.php';
require_once __DIR__ . '/../models/MateriaModel.php';
require_once __DIR__ . '/../models/OfertaModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';
require_once __DIR__ . '/../models/CarreraModel.php';
require_once __DIR__ . '/../models/TurnoModel.php';

$idUsuario = $_SESSION['id_usuario'] ?? 0;
$rolSesion = $_SESSION['rol'] ?? '';

// Solo los estudiantes pueden solicitar tutorías
if ($rolSesion !== 'estudiante') {
    setMensaje('danger', 'Solo los estudiantes pueden solicitar tutorías.');
    redirigir($rolSesion === 'tutor' ? '../views/tutor/panel.php' : 'tutorias_listar.php');
}

$estudianteModel = new EstudianteModel($pdo);
$materiaModel = new MateriaModel($pdo);
$ofertaModel = new OfertaModel($pdo);
$tutoriaModel = new TutoriaModel($pdo);

// Obtener o crear perfil de estudiante (si aún no tiene ficha académica)
$estudiante = $estudianteModel->obtenerPorUsuario($idUsuario);
if (!$estudiante) {
    // Si no tiene ficha, asociar a la primera carrera disponible
    $carreraModel = new CarreraModel($pdo);
    $carreras = $carreraModel->obtenerTodas();
    $idCarreraDefault = !empty($carreras) ? $carreras[0]['id_carrera'] : 1;
    $estudianteModel->guardarOActualizar($idUsuario, $idCarreraDefault, 1, 'RU-' . rand(10000, 99999));
    $estudiante = $estudianteModel->obtenerPorUsuario($idUsuario);
}

$errores = [];

// ===== Procesamiento del formulario (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('tutorias_solicitar.php');
    }

    $idOferta      = (int)($_POST['id_oferta'] ?? 0);
    $observaciones = trim($_POST['observaciones'] ?? '');

    // 1. Campos obligatorios
    if (!$idOferta) {
        $errores[] = "Debes seleccionar un horario disponible.";
    }

    // 2. La oferta debe existir, estar asignada y tener tutor
    $oferta = $idOferta ? $ofertaModel->obtenerPorId($idOferta) : false;
    if ($oferta) {
        if ($oferta['estado'] !== 'asignada' || empty($oferta['id_tutor_assigned'])) {
            $errores[] = "Ese horario ya no está disponible (sin tutor asignado).";
            $oferta = false;
        } elseif (!in_array($oferta['id_materia'], array_column($materiaModel->obtenerPorCarrera($estudiante['id_carrera']), 'id_materia'), true)) {
            $errores[] = "La oferta seleccionada no corresponde a las materias de tu carrera.";
            $oferta = false;
        }
    } elseif (!$errores) {
        $errores[] = "El horario seleccionado no es válido.";
    }

    // 3. Bloquear duplicados: el estudiante NO puede registrar la misma materia dos veces
    if (empty($errores) && $oferta) {
        if ($tutoriaModel->tieneMateriaActiva($estudiante['id_estudiante'], $oferta['id_materia'])) {
            $errores[] = "Ya tienes registrada una solicitud para esta materia. No puedes inscribirte dos veces en la misma materia; cancela la solicitud anterior si deseas cambiarla.";
        }
    }

    // 4. La fecha de la sesión se asigna automáticamente: el primer día libre
    //    del turno elegido, sin conflictos de horario del tutor ni del estudiante.
    $fechaAutom = '';
    if (empty($errores) && $oferta) {
        $stmtTurno = $pdo->prepare("SELECT hora_inicio, hora_fin FROM turnos WHERE id_turno = :id");
        $stmtTurno->execute([':id' => $oferta['id_turno']]);
        $turno = $stmtTurno->fetch();
        if ($turno) {
            $fechaAutom = $tutoriaModel->proximaFechaDisponible($oferta['id_tutor_assigned'], $estudiante['id_estudiante'], $turno['hora_inicio'], $turno['hora_fin']);
            if (!$fechaAutom) {
                $errores[] = "No hay fechas disponibles en los próximos 6 meses para este horario. Prueba con otro turno o vuelve más tarde.";
            }
        }
    }

    // 5. Observaciones con límite de longitud
    if (mb_strlen($observaciones) > 1000) {
        $errores[] = "Las observaciones no pueden superar los 1000 caracteres.";
    }

    // Si todas las validaciones pasan, se crea la tutoría (estado: pendiente)
    if (empty($errores) && $oferta) {
        $stmtTurno = $pdo->prepare("SELECT hora_inicio, hora_fin FROM turnos WHERE id_turno = :id");
        $stmtTurno->execute([':id' => $oferta['id_turno']]);
        $turno = $stmtTurno->fetch();

        $datos = [
            'id_estudiante'   => $estudiante['id_estudiante'],
            'id_materia'      => $oferta['id_materia'],
            'id_tutor'        => $oferta['id_tutor_assigned'],
            'fecha'           => $fechaAutom,
            'hora_inicio'     => $turno['hora_inicio'],
            'hora_fin'        => $turno['hora_fin'],
            'modalidad'       => $oferta['modalidad'],
            'nivel_academico' => $oferta['nivel_academico'],
            'lugar_o_enlace'  => $oferta['lugar_o_enlace'],
            'observaciones'   => $observaciones
        ];

        try {
            $tutoriaModel->crear($datos);
            setMensaje('success', 'Tu solicitud de tutoría fue enviada correctamente.');
            redirigir('../views/estudiante/panel.php');
        } catch (PDOException $e) {
            $errores[] = "Error al agendar la sesión: " . $e->getMessage();
        }
    }
}

// Datos para el formulario:
// - SOLO materias de la carrera del estudiante
// - Ofertas asignadas (horarios preestablecidos) de esas materias
// - Los 4 turnos fijos (Mañana/Mediodía/Tarde/Noche) siempre se muestran;
//   la vista los deshabilita cuando no hay horario publicado+asignado.
$materias = $materiaModel->obtenerPorCarrera($estudiante['id_carrera']);
$turnos = (new TurnoModel($pdo))->obtenerTodos();
$idsMaterias = array_column($materias, 'id_materia');
$ofertasDisponibles = [];
if (!empty($idsMaterias)) {
    foreach ($ofertaModel->obtenerOfertasEstudiante() as $oferta) {
        if (in_array($oferta['id_materia'], $idsMaterias, true)) {
            $ofertasDisponibles[] = $oferta;
        }
    }
}
$carreraEstudiante = $estudiante['nombre_carrera'] ?? '';

require_once __DIR__ . '/../views/tutorias/solicitar.php';