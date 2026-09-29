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
$carreraModel = new CarreraModel($pdo);

// Obtener o crear perfil de estudiante (si aún no tiene ficha académica)
$estudiante = $estudianteModel->obtenerPorUsuario($idUsuario);
if (!$estudiante) {
    // Si no tiene ficha, asociar a la primera carrera disponible
    $carreras = $carreraModel->obtenerTodas();
    $idCarreraDefault = !empty($carreras) ? $carreras[0]['id_carrera'] : 1;
    $estudianteModel->guardarOActualizar($idUsuario, $idCarreraDefault, 1, 'RU-' . rand(10000, 99999));
    $estudiante = $estudianteModel->obtenerPorUsuario($idUsuario);
}

// Ficha académica efectiva, usada por el POST y por las consultas
if (empty($estudiante)) {
    setMensaje('danger', 'No se encontró tu ficha de estudiante. Completa tu perfil antes de solicitar tutorías.');
    redirigir('perfil.php');
}
$idEstudiante = (int)$estudiante['id_estudiante'];

// Datos institucionales de la carrera: alimentan el bloque de metadatos
// (modelo y sistema de estudio) del formulario. El desplegable de carrera
// es informativo: el estudiante solicita para SU programa y el backend
// sigue validando que la oferta pertenezca a su carrera.
$carrera = $carreraModel->obtenerPorId($estudiante['id_carrera']);

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
        if ($tutoriaModel->tieneMateriaActiva($idEstudiante, $oferta['id_materia'])) {
            $errores[] = "Ya tienes registrada una solicitud para esta materia. No puedes inscribirte dos veces en la misma materia; cancela la solicitud anterior si deseas cambiarla.";
        }
    }

    // 4. La fecha de la sesión se asigna automáticamente: el estudiante se
    //    suma a la sesión de la oferta que aún tenga cupo libre; si todas
    //    están llenas, se abre una nueva el primer día libre del turno.
    $fechaAutom = '';
    if (empty($errores) && $oferta) {
        $stmtTurno = $pdo->prepare("SELECT hora_inicio, hora_fin FROM turnos WHERE id_turno = :id");
        $stmtTurno->execute([':id' => $oferta['id_turno']]);
        $turno = $stmtTurno->fetch();
        if ($turno) {
            $cupo = $ofertaModel->obtenerCupo($oferta['id_oferta']);
            $fechaAutom = $tutoriaModel->proximaFechaDisponible(
                $oferta['id_tutor_assigned'],
                $idEstudiante,
                $turno['hora_inicio'],
                $turno['hora_fin'],
                180,
                $oferta['id_oferta'],
                $cupo
            );
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
            'id_estudiante'   => $idEstudiante,
            'id_materia'      => $oferta['id_materia'],
            'id_tutor'        => $oferta['id_tutor_assigned'],
            'id_oferta'       => $oferta['id_oferta'],
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
            // El mensaje crudo de MySQL no se muestra al estudiante: solo
            // se distinguen las causas que el usuario puede corregir.
            if (str_contains($e->getMessage(), 'uq_estud_fecha_hora')) {
                $errores[] = "Ya tienes otra sesión agendada a esa misma hora. Elige un horario distinto.";
            } else {
                error_log('tutorias_solicitar: ' . $e->getMessage());
                $errores[] = "No se pudo agendar la sesión. Intenta de nuevo en unos minutos.";
            }
        }
    }
}

// Datos para la vista:
// - SOLO materias de la carrera del estudiante
// - Ofertas de esas materias en dos estados: 'asignada' (tiene
//   docente, se reserva) y 'abierta' (sin docente aún, el estudiante
//   puede dejar su interés registrado)
// Los 4 turnos fijos (Mañana/Mediodía/Tarde/Noche) siempre se muestran;
// la vista los deshabilita cuando no hay horario publicado.
$materias = $materiaModel->obtenerPorCarrera($estudiante['id_carrera']);
$turnos = (new TurnoModel($pdo))->obtenerTodos();
$carreraEstudiante = $estudiante['nombre_carrera'] ?? '';

// La consulta ya viene filtrada por la carrera del estudiante, así que
// no hace falta recortar el resultado a mano.
$ofertasPorMateria = [];
foreach ($ofertaModel->obtenerOfertasVisiblesEstudiante($estudiante['id_carrera'], $idEstudiante) as $oferta) {
    $ofertasPorMateria[(int)$oferta['id_materia']][] = $oferta;
}

// Una card por materia, pero solo por las que la administración tiene
// algo publicado: una materia sin ningún horario no es una opción para el
// estudiante, así que no se lista. Si la carrera no tiene ninguna oferta
// en absoluto, la vista muestra su estado vacío.
$materiasAgrupadas = [];
$ofertasDisponibles = []; // lista plana: la usan los filtros de la vista

foreach ($materias as $materia) {
    $ofertas = $ofertasPorMateria[(int)$materia['id_materia']] ?? [];

    // Sin oferta no hay card.
    if (empty($ofertas)) {
        continue;
    }

    // Primero las reservables (con docente), después las que esperan
    // docente: la acción disponible queda siempre arriba.
    usort($ofertas, function ($a, $b) {
        return [(int)$a['sin_docente'], $a['turno_hora_inicio']]
            <=> [(int)$b['sin_docente'], $b['turno_hora_inicio']];
    });

    $materiasAgrupadas[] = [
        'id_materia'     => (int)$materia['id_materia'],
        'nombre_materia' => $materia['nombre_materia'],
        'nombre_carrera' => $materia['nombre_carrera'] ?? '',
        'ofertas'        => $ofertas,
        'total_horarios' => count($ofertas),
        'con_docente'    => count(array_filter($ofertas, function ($o) { return empty($o['sin_docente']); })),
        'esperando'      => count(array_filter($ofertas, function ($o) { return !empty($o['sin_docente']); })),
    ];

    $ofertasDisponibles = array_merge($ofertasDisponibles, $ofertas);
}

// El sistema de estudio es un filtro sobre la modalidad del horario
// (ofertas_admin.modalidad) y el dominio tiene solo dos valores.
// Sin selección el grupo está "en blanco" y se ven ambas modalidades.
$sistemasEstudio = ['presencial' => 'Presencial', 'virtual' => 'Virtual'];

// Lo que declara la carrera del estudiante (carreras.sistema_estudio) es
// un dato informativo: se normaliza al dominio de dos valores y, si el
// valor guardado no corresponde (dato histórico), se muestra presencial.
$sistemaDeclarado = strtolower(trim((string)($carrera['sistema_estudio'] ?? '')));
if (!isset($sistemasEstudio[$sistemaDeclarado])) {
    $sistemaDeclarado = 'presencial';
}

// Etiqueta de la carrera con su código, p. ej. "Ingeniería de Sistemas (320-04)".
$carreraEtiqueta = $carreraEstudiante;
if (!empty($carrera['codigo'])) {
    $carreraEtiqueta .= ' (' . $carrera['codigo'] . ')';
}

require_once __DIR__ . '/../views/tutorias/solicitar.php';