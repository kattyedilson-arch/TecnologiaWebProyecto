<?php
// =========================================================
// CONTROLADOR: GESTIÓN DE TUTOR (tutores_disponibilidad.php)
// ---------------------------------------------------------
// Página de configuración del docente tutor: administra sus
// bloques de disponibilidad (materia y turno), las materias que imparte
// y su perfil profesional (especialidad y biografía).
//
// Acceso: el tutor entra sin id (usa su propia sesión);
// el administrador pasa ?id= para gestionar a cualquier tutor.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutorModel.php';
require_once __DIR__ . '/../models/MateriaModel.php';
require_once __DIR__ . '/../models/TurnoModel.php';

$tutorModel = new TutorModel($pdo);
$materiaModel = new MateriaModel($pdo);
$turnoModel = new TurnoModel($pdo);

$rolSesion = $_SESSION['rol'] ?? '';
$idUsuario = $_SESSION['id_usuario'] ?? 0;

// Control de acceso:
//   - tutor        -> siempre gestiona SOLO su propio perfil
//   - administrador-> puede gestionar a cualquier tutor via ?id=
//   - cualquier otro rol (estudiante) -> sin permisos
$idTutor = null;

if ($rolSesion === 'tutor') {
    $tutorActual = $tutorModel->obtenerPorUsuario($idUsuario);
    if ($tutorActual) {
        $idTutor = $tutorActual['id_tutor']; // El tutor siempre gestiona solo SU perfil
    }
} elseif ($rolSesion === 'administrador') {
    $idTutor = $_GET['id'] ?? null;
} else {
    setMensaje('danger', 'No tienes permisos para gestionar la disponibilidad de tutores.');
    redirigir('../views/estudiante/panel.php');
}

// Sin un tutor identificado no hay nada que gestionar
if (!$idTutor) {
    setMensaje('danger', 'No se encontró el perfil del tutor.');
    redirigir($rolSesion === 'tutor' ? '../views/tutor/panel.php' : 'tutores_listar.php');
}

// El tutor indicado debe existir
$tutor = $tutorModel->obtenerPorId($idTutor);
if (!$tutor) {
    setMensaje('danger', 'El tutor solicitado no existe.');
    redirigir($rolSesion === 'tutor' ? '../views/tutor/panel.php' : 'tutores_listar.php');
}

$errores = [];

// ===== Procesar acciones POST (agregar horario, materias, perfil) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir($rolSesion === 'tutor' ? 'tutores_disponibilidad.php' : 'tutores_disponibilidad.php?id=' . $idTutor);
    }

    $accion = $_POST['accion'] ?? '';

    // ---------------------------------------------------------
    // ACCIÓN 1: Agregar nuevo turno al tutor (SOLO ADMIN)
    // ---------------------------------------------------------
    if ($accion === 'agregar_horario') {
        // Solo el administrador puede agregar turnos a tutores
        if ($rolSesion !== 'administrador') {
            $errores[] = "Solo el administrador puede asignar turnos a los tutores.";
        } else {
            $idTurno = (int)($_POST['id_turno'] ?? 0);
            $idMateria = (int)($_POST['id_materia'] ?? 0);

            // Materias que el tutor realmente imparte (solo esas pueden usarse)
            $materiasTutor = $tutorModel->obtenerMaterias($idTutor);
            $idsMateriasTutor = array_column($materiasTutor, 'id_materia');

            // Validaciones
            if (empty($idTurno) || empty($idMateria)) {
                $errores[] = "Todos los campos de horario (turno y materia) son obligatorios.";
            } elseif (!$turnoModel->obtenerPorId($idTurno)) {
                $errores[] = "El turno seleccionado no es válido.";
            } elseif (!in_array($idMateria, $idsMateriasTutor, true)) {
                $errores[] = "Debes elegir una de las materias que imparte el tutor.";
            } elseif ($tutorModel->existeConflictoDisponibilidad($idTutor, $idTurno, $idMateria)) {
                $errores[] = "El tutor ya tiene asignado ese turno para esa materia.";
            } else {
                $tutorModel->agregarDisponibilidad($idTutor, $idTurno, $idMateria);
                setMensaje('success', 'Turno asignado correctamente al tutor.');
                redirigir('tutores_disponibilidad.php?id=' . $idTutor);
            }
        }
    }

    // ---------------------------------------------------------
    // ACCIÓN 2: Actualizar las materias asignadas al tutor
    // ---------------------------------------------------------
    if ($accion === 'guardar_materias') {
        $materiasSeleccionadas = $_POST['materias'] ?? [];

        // Solo se guardan las materias que existan realmente en la BD
        $idsValidos = array_column($materiaModel->obtenerExistentes($materiasSeleccionadas), 'id_materia');

        $tutorModel->asignarMaterias($idTutor, $idsValidos);
        setMensaje('success', 'Materias asignadas actualizadas con éxito.');
        redirigir($rolSesion === 'tutor' ? 'tutores_disponibilidad.php' : 'tutores_disponibilidad.php?id=' . $idTutor);
    }

    // ---------------------------------------------------------
    // ACCIÓN 3: Actualizar perfil básico (especialidad y biografía)
    // ---------------------------------------------------------
    if ($accion === 'actualizar_perfil') {
        $esp = limpiarTexto($_POST['especialidad'] ?? '');
        $bio = trim($_POST['biografia'] ?? '');

        // Límites de longitud para evitar datos excesivos
        if (mb_strlen($esp) > 150) {
            $errores[] = "La especialidad no puede superar los 150 caracteres.";
        }
        if (mb_strlen($bio) > 1000) {
            $errores[] = "La biografía no puede superar los 1000 caracteres.";
        }
        if (empty($errores)) {
            $tutorModel->actualizarPerfil($idTutor, $esp, $bio);
            $tutor = $tutorModel->obtenerPorId($idTutor); // Refrescar datos en la vista
            setMensaje('success', 'Perfil docente actualizado con éxito.');
            redirigir($rolSesion === 'tutor' ? 'tutores_disponibilidad.php' : 'tutores_disponibilidad.php?id=' . $idTutor);
        }
    }
}

// ===== Eliminar un turno asignado (GET con token de seguridad, SOLO ADMIN) =====
if (isset($_GET['eliminar_horario'])) {
    if ($rolSesion !== 'administrador') {
        setMensaje('danger', 'Solo el administrador puede eliminar turnos.');
        redirigir('tutores_disponibilidad.php?id=' . $idTutor);
    }
    // Protección CSRF antes de borrar
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('tutores_disponibilidad.php?id=' . $idTutor);
    }
    $idDisp = (int)$_GET['eliminar_horario'];
    $tutorModel->eliminarDisponibilidad($idDisp, $idTutor);
    setMensaje('success', 'Turno eliminado del tutor.');
    redirigir('tutores_disponibilidad.php?id=' . $idTutor);
}

// Datos para renderizar la vista
$materiasAsignadas = $tutorModel->obtenerMaterias($idTutor);
$idsMateriasAsignadas = array_column($materiasAsignadas, 'id_materia');
$todasMaterias = $materiaModel->obtenerTodas();
$disponibilidades = $tutorModel->obtenerDisponibilidad($idTutor);
$turnos = $turnoModel->obtenerTodos();

require_once __DIR__ . '/../views/tutores/disponibilidad.php';