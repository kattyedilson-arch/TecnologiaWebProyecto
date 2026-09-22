<?php
// =========================================================
// CONTROLADOR: GESTIÓN DE TUTOR (tutores_disponibilidad.php)
// ---------------------------------------------------------
// Página de configuración del docente tutor: administra sus
// bloques de disponibilidad semanal, las materias que imparte
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

$tutorModel = new TutorModel($pdo);
$materiaModel = new MateriaModel($pdo);

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
$diasValidos = ['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

// ===== Procesar acciones POST (agregar horario, materias, perfil) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir($rolSesion === 'tutor' ? 'tutores_disponibilidad.php' : 'tutores_disponibilidad.php?id=' . $idTutor);
    }

    $accion = $_POST['accion'] ?? '';

    // ---------------------------------------------------------
    // ACCIÓN 1: Agregar nuevo bloque horario
    // ---------------------------------------------------------
    if ($accion === 'agregar_horario') {
        $dia = $_POST['dia_semana'] ?? '';
        $inicio = $_POST['hora_inicio'] ?? '';
        $fin = $_POST['hora_fin'] ?? '';
        $idMateria = (int)($_POST['id_materia'] ?? 0);

        // Materias que el tutor realmente imparte (solo esas pueden usarse en un bloque)
        $materiasTutor = $tutorModel->obtenerMaterias($idTutor);
        $idsMateriasTutor = array_column($materiasTutor, 'id_materia');

        // Validaciones del bloque horario
        if (empty($dia) || empty($inicio) || empty($fin) || empty($idMateria)) {
            $errores[] = "Todos los campos de horario (día, hora y materia) son obligatorios.";
        } elseif (!in_array($dia, $diasValidos, true)) {
            $errores[] = "El día seleccionado no es válido.";
        } elseif (!preg_match('/^(2[0-3]|[01][0-9]):[0-5][0-9]$/', $inicio) || !preg_match('/^(2[0-3]|[01][0-9]):[0-5][0-9]$/', $fin)) {
            $errores[] = "El formato de las horas no es válido (usa HH:MM con horas entre 00 y 23).";
        } elseif ($inicio >= $fin) {
            $errores[] = "La hora de fin debe ser mayor a la hora de inicio.";
        } elseif (!in_array($idMateria, $idsMateriasTutor, true)) {
            $errores[] = "Debes elegir una de las materias que impartes para este bloque.";
        } elseif ($tutorModel->existeConflictoDisponibilidad($idTutor, $dia, $inicio, $fin)) {
            // Se impide que dos bloques del mismo día se solapen
            $errores[] = "Ya existe un bloque horario que se solapa con el que intentas agregar.";
        } else {
            $tutorModel->agregarDisponibilidad($idTutor, $dia, $inicio, $fin, $idMateria);
            setMensaje('success', 'Bloque horario agregado correctamente.');
            redirigir($rolSesion === 'tutor' ? 'tutores_disponibilidad.php' : 'tutores_disponibilidad.php?id=' . $idTutor);
        }
    }

    // ---------------------------------------------------------
    // ACCIÓN 2: Actualizar las materias asignadas al tutor
    // ---------------------------------------------------------
    if ($accion === 'guardar_materias') {
        $materiasSeleccionadas = $_POST['materias'] ?? [];

        // Solo se guardan las materias que existan realmente en la BD
        $idsValidos = [];
        foreach ((array)$materiasSeleccionadas as $idMateria) {
            if ($materiaModel->obtenerPorId((int)$idMateria)) {
                $idsValidos[] = (int)$idMateria;
            }
        }

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

// ===== Eliminar un horario (GET con token de seguridad) =====
// El enlace de la papelera pasa ?eliminar_horario=ID&token=...
if (isset($_GET['eliminar_horario'])) {
    // Protección CSRF antes de borrar el bloque
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir($rolSesion === 'tutor' ? 'tutores_disponibilidad.php' : 'tutores_disponibilidad.php?id=' . $idTutor);
    }
    $idDisp = (int)$_GET['eliminar_horario'];
    // eliminarDisponibilidad verifica que el bloque pertenezca a este tutor
    $tutorModel->eliminarDisponibilidad($idDisp, $idTutor);
    setMensaje('success', 'Bloque horario eliminado.');
    redirigir($rolSesion === 'tutor' ? 'tutores_disponibilidad.php' : 'tutores_disponibilidad.php?id=' . $idTutor);
}

// Datos para renderizar la vista
$materiasAsignadas = $tutorModel->obtenerMaterias($idTutor);
$idsMateriasAsignadas = array_column($materiasAsignadas, 'id_materia');
$todasMaterias = $materiaModel->obtenerTodas();
$disponibilidades = $tutorModel->obtenerDisponibilidad($idTutor);

require_once __DIR__ . '/../views/tutores/disponibilidad.php';