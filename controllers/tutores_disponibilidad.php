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
//
// Quién puede editar cada bloque:
//   - Turnos (agregar/eliminar): solo el administrador.
//   - Materias que imparte:    el administrador y el propio tutor.
//   - Perfil profesional:      el administrador y el propio tutor.
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
    if ($rolSesion === 'administrador') {
        // El admin gestiona horarios desde el listado, eligiendo un tutor concreto
        redirigir('tutores_listar.php');
    }
    setMensaje('danger', 'No se encontró el perfil del tutor.');
    redirigir('../views/tutor/panel.php');
}

// El tutor indicado debe existir
$tutor = $tutorModel->obtenerPorId($idTutor);
if (!$tutor) {
    setMensaje('danger', 'El tutor solicitado no existe.');
    redirigir($rolSesion === 'tutor' ? '../views/tutor/panel.php' : 'tutores_listar.php');
}

$errores = [];
// Datos del último POST, para repintar los formularios aunque la validación
// falle. Vacíos = usar lo que hay en la BD.
$materiasPost = null;
$perfilPost = null;

// ===== Procesar acciones POST (agregar horario, materias, perfil) =====
// Nota de seguridad: el bloque de control de acceso de arriba ya garantiza
// que $rolSesion es 'administrador' o 'tutor' y que $idTutor es la ficha del
// propio tutor cuando el rol es 'tutor' (ignora cualquier ?id= recibido).
// Por eso las acciones 2 y 3 no repiten esa comprobación: solo la acción 1
// (agregar_horario) necesita un guard propio por ser exclusiva del admin.
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
    // Disponible para el administrador y para el propio tutor
    // (siempre sobre su propia ficha: $idTutor ya está acotado por rol).
    // ---------------------------------------------------------
    if ($accion === 'guardar_materias') {
        // Checkboxes marcados en el formulario; se repintan en la vista
        // aunque la validación falle para no perder la selección.
        $materiasPost = $_POST['materias'] ?? [];
        $idsSeleccionados = array_map('intval', (array)$materiasPost);

        // Materias que el tutor tiene ahora mismo asignadas
        $idsActuales = array_column($tutorModel->obtenerMaterias($idTutor), 'id_materia');

        // Una materia no se puede desmarcar si tiene bloques de horario:
        // la fila de disponibilidad_tutor quedaría huérfana. Se avisa en vez
        // de borrar en cascada, para que el turno se retire de forma explícita.
        $aQuitar = array_diff($idsActuales, $idsSeleccionados);
        if (!empty($aQuitar)) {
            $bloqueadas = $tutorModel->obtenerMateriasBloqueadas($idTutor, $aQuitar);
            foreach ($bloqueadas as $mat) {
                $errores[] = '"' . $mat['nombre_materia'] . '" no se puede quitar: tiene '
                    . $mat['total_turnos'] . ' turno(s) asignado(s). '
                    . 'Solicita al administrador que retire primero ese horario.';
            }
        }

        if (empty($errores)) {
            // Solo se guardan las materias que existan realmente en la BD
            $idsValidos = array_column($materiaModel->obtenerExistentes($idsSeleccionados), 'id_materia');
            $tutorModel->asignarMaterias($idTutor, $idsValidos);
            setMensaje('success', 'Materias asignadas actualizadas con éxito.');
            redirigir($rolSesion === 'tutor' ? 'tutores_disponibilidad.php' : 'tutores_disponibilidad.php?id=' . $idTutor);
        }
    }

    // ---------------------------------------------------------
    // ACCIÓN 3: Actualizar perfil básico (especialidad y biografía)
    // El tutor edita el suyo propio; el administrador, el que indique ?id=
    // ---------------------------------------------------------
    if ($accion === 'actualizar_perfil') {
        $esp = limpiarTexto($_POST['especialidad'] ?? '');
        $bio = trim($_POST['biografia'] ?? '');
        $perfilPost = ['especialidad' => $esp, 'biografia' => $bio];

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
$todasMaterias = $materiaModel->obtenerTodas('nombre', 'ASC');
$disponibilidades = $tutorModel->obtenerDisponibilidad($idTutor);
$turnos = $turnoModel->obtenerTodos();

// Materias asignadas que NO se pueden desmarcar por tener turnos: la vista las
// pinta bloqueadas. Solo importa para las ya asignadas; una materia no
// asignada nunca tiene disponibilidad, así que siempre se puede marcar.
$turnosPorMateria = [];
foreach ($tutorModel->obtenerMateriasBloqueadas($idTutor, $idsMateriasAsignadas) as $mat) {
    $turnosPorMateria[(int)$mat['id_materia']] = (int)$mat['total_turnos'];
}

require_once __DIR__ . '/../views/tutores/disponibilidad.php';