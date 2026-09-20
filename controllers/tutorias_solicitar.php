<?php
// =========================================================
// CONTROLADOR: SOLICITAR TUTORÍA (tutorias_solicitar.php)
// ---------------------------------------------------------
// Pantalla donde el ESTUDIANTE agenda una nueva sesión de
// tutoría. Valida en servidor: existencia de materia/tutor,
// que el tutor imparta la materia, fecha no pasada, horas
// coherentes, URL válida en modalidad virtual y que no exista
// conflicto de horario con otras sesiones del tutor.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutoriaModel.php';
require_once __DIR__ . '/../models/MateriaModel.php';
require_once __DIR__ . '/../models/TutorModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';
require_once __DIR__ . '/../models/CarreraModel.php';

$idUsuario = $_SESSION['id_usuario'] ?? 0;
$rolSesion = $_SESSION['rol'] ?? '';

// Solo los estudiantes pueden solicitar tutorías
// (evita que admin/tutor generen perfiles "fantasma" de estudiante)
if ($rolSesion !== 'estudiante') {
    setMensaje('danger', 'Solo los estudiantes pueden solicitar tutorías.');
    redirigir($rolSesion === 'tutor' ? '../views/tutor/panel.php' : 'tutorias_listar.php');
}

$estudianteModel = new EstudianteModel($pdo);
$materiaModel = new MateriaModel($pdo);
$tutorModel = new TutorModel($pdo);
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
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('tutorias_solicitar.php');
    }

    $datos = [
        'id_estudiante'  => $estudiante['id_estudiante'],
        'id_materia'     => $_POST['id_materia'] ?? '',
        'id_tutor'       => $_POST['id_tutor'] ?? '',
        'fecha'          => $_POST['fecha'] ?? '',
        'hora_inicio'    => $_POST['hora_inicio'] ?? '',
        'hora_fin'       => $_POST['hora_fin'] ?? '',
        'modalidad'      => $_POST['modalidad'] ?? '',
        'lugar_o_enlace' => limpiarTexto($_POST['lugar_o_enlace'] ?? ''),
        'observaciones'  => trim($_POST['observaciones'] ?? '')
    ];

    // 1. Campos obligatorios
    if (in_array('', [$datos['id_materia'], $datos['id_tutor'], $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin'], $datos['modalidad']], true)) {
        $errores[] = "Todos los campos marcados con asterisco (*) son obligatorios.";
    }

    // 2. Validar que la materia y el tutor existan
    if ($datos['id_materia'] !== '' && !$materiaModel->obtenerPorId((int)$datos['id_materia'])) {
        $errores[] = "La materia seleccionada no es válida.";
    }
    if ($datos['id_tutor'] !== '' && !$tutorModel->obtenerPorId((int)$datos['id_tutor'])) {
        $errores[] = "El docente tutor seleccionado no es válido.";
    }

    // 3. El tutor debe impartir la materia elegida
    if (!empty($datos['id_materia']) && !empty($datos['id_tutor'])) {
        $tutoresMateria = $tutorModel->obtenerTutoresPorMateria((int)$datos['id_materia']);
        $idsValidos = array_column($tutoresMateria, 'id_tutor');
        if (!in_array((int)$datos['id_tutor'], $idsValidos, true)) {
            $errores[] = "El docente tutor seleccionado no imparte esa materia.";
        }
    }

    // 4. Modalidad válida (presencial o virtual)
    if (!empty($datos['modalidad']) && !in_array($datos['modalidad'], ['presencial', 'virtual'], true)) {
        $errores[] = "La modalidad seleccionada no es válida.";
    }

    // 5. Fecha no puede ser en el pasado
    if (!empty($datos['fecha'])) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datos['fecha'])) {
            $errores[] = "El formato de la fecha no es válido.";
        } elseif ($datos['fecha'] < date('Y-m-d')) {
            $errores[] = "La fecha de la tutoría no puede ser en el pasado.";
        } elseif ($datos['fecha'] > date('Y-m-d', strtotime('+180 days'))) {
            $errores[] = "La fecha de la tutoría no puede exceder los 6 meses de anticipación.";
        }
    }

    // 6. Horarios coherentes (formato HH:MM real y fin posterior)
    if (!empty($datos['hora_inicio']) && !empty($datos['hora_fin'])) {
        $regexHora = '/^(2[0-3]|[01][0-9]):[0-5][0-9]$/';
        if (!preg_match($regexHora, $datos['hora_inicio']) || !preg_match($regexHora, $datos['hora_fin'])) {
            $errores[] = "El formato de las horas no es válido (usa HH:MM con horas entre 00 y 23).";
        } elseif ($datos['hora_inicio'] >= $datos['hora_fin']) {
            $errores[] = "La hora de finalización debe ser posterior a la hora de inicio.";
        } elseif ($datos['fecha'] === date('Y-m-d') && $datos['hora_inicio'] <= date('H:i')) {
            $errores[] = "La hora de inicio ya pasó para hoy. Elige una hora futura.";
        }
    }

    // 7. Para modalidad virtual el enlace es obligatorio y debe ser URL válida
    if ($datos['modalidad'] === 'virtual') {
        if (empty($datos['lugar_o_enlace'])) {
            $errores[] = "Para tutorías virtuales debes indicar el enlace de la videoconferencia.";
        } elseif (!validarUrl($datos['lugar_o_enlace'])) {
            $errores[] = "El enlace de la videoconferencia no es una URL válida (ej: https://meet.google.com/...).";
        }
    }

    // 8. Límites de longitud (acordes a las columnas de la BD)
    if (!empty($datos['lugar_o_enlace']) && mb_strlen($datos['lugar_o_enlace']) > 200) {
        $errores[] = "El lugar o enlace no puede superar los 200 caracteres.";
    }
    if (mb_strlen($datos['observaciones']) > 1000) {
        $errores[] = "Las observaciones no pueden superar los 1000 caracteres.";
    }

    // 9. Evitar solapamiento con otra tutoría activa del mismo tutor
    if (empty($errores) && !empty($datos['id_tutor']) && !empty($datos['fecha'])) {
        if ($tutoriaModel->existeConflictoHorario($datos['id_tutor'], $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin'])) {
            $errores[] = "El docente tutor ya tiene una sesión agendada en ese horario. Por favor elige otro rango.";
        }
    }

    // 10. Evitar que el mismo estudiante se doble a sí mismo
    if (empty($errores) && !empty($datos['fecha'])) {
        if ($tutoriaModel->existeConflictoHorarioEstudiante($estudiante['id_estudiante'], $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin'])) {
            $errores[] = "Ya tienes una tutoría agendada en ese horario. Elige otro rango.";
        }
    }

    // 11. La sesión debe estar dentro del horario declarado por el tutor
    if (empty($errores) && !empty($datos['id_tutor']) && !empty($datos['fecha'])) {
        if (!$tutoriaModel->disponibilidadCubreHorario($datos['id_tutor'], $datos['fecha'], $datos['hora_inicio'], $datos['hora_fin'])) {
            $errores[] = "El docente tutor no tiene disponibilidad en esa fecha y horario. Revisa sus horarios declarados.";
        }
    }

    // Si todas las validaciones pasan, se crea la tutoría (estado: pendiente)
    if (empty($errores)) {
        try {
            $tutoriaModel->crear($datos);
            setMensaje('success', 'Tu solicitud de tutoría fue enviada correctamente.');
            redirigir('../views/estudiante/panel.php');
        } catch (PDOException $e) {
            error_log("Error al agendar tutoría: " . $e->getMessage());
            $errores[] = "Error al agendar la sesión. Inténtalo de nuevo.";
        }
    }
}

// Datos para el formulario: SOLO las materias de la carrera del estudiante
$materias = $materiaModel->obtenerPorCarrera($estudiante['id_carrera']);
$tutores = $tutorModel->obtenerTutoresConMaterias();
$carreraEstudiante = $estudiante['nombre_carrera'] ?? '';

require_once __DIR__ . '/../views/tutorias/solicitar.php';