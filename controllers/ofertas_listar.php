<?php
// =========================================================
// CONTROLADOR: GESTIONAR OFERTAS (ofertas_listar.php)
// ---------------------------------------------------------
// El administrador crea ofertas de materias con turnos.
// Los tutores pueden aceptar o rechazar estas ofertas.
// Solo el administrador tiene acceso a esta página.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/OfertaModel.php';
require_once __DIR__ . '/../models/MateriaModel.php';
require_once __DIR__ . '/../models/TurnoModel.php';

requerirRol('administrador');

$ofertaModel = new OfertaModel($pdo);
$materiaModel = new MateriaModel($pdo);
$turnoModel = new TurnoModel($pdo);

$errores = [];
$exito = '';

// ===== Procesar acciones POST =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('ofertas_listar.php');
    }

    $accion = $_POST['accion'] ?? '';

    // ACCIÓN: Crear nueva oferta
    if ($accion === 'crear_oferta') {
        $idMateria = (int)($_POST['id_materia'] ?? 0);
        $nivelAcademico = trim($_POST['nivel_academico'] ?? '');
        $nivelPersonalizado = trim($_POST['nivel_academico_otra'] ?? '');
        $modalidad = $_POST['modalidad'] ?? 'presencial';
        $lugarOEnlace = limpiarTexto($_POST['lugar_o_enlace'] ?? '');
        $idTurno = (int)($_POST['id_turno'] ?? 0);
        $modalidadesValidas = ['presencial', 'virtual'];

        // "Otra (Personalizar)": el nivel se toma del texto libre
        if ($nivelAcademico === 'otra') {
            $nivelAcademico = $nivelPersonalizado;
        }

        if (empty($idMateria) || empty($nivelAcademico) || empty($idTurno)) {
            $errores[] = "Todos los campos son obligatorios.";
        } elseif (mb_strlen($nivelAcademico) > 80) {
            $errores[] = "El nivel académico no puede superar los 80 caracteres.";
        } elseif (!in_array($modalidad, $modalidadesValidas, true)) {
            $errores[] = "La modalidad seleccionada no es válida.";
        } elseif ($modalidad === 'virtual' && (empty($lugarOEnlace) || !validarUrl($lugarOEnlace))) {
            $errores[] = "Para ofertas virtuales debes indicar un enlace de videoconferencia válido (https://...).";
        } elseif (!$materiaModel->obtenerPorId($idMateria)) {
            $errores[] = "La materia seleccionada no es válida.";
        } elseif (!$turnoModel->obtenerPorId($idTurno)) {
            $errores[] = "El turno seleccionado no es válido.";
        } else {
            try {
                $ofertaModel->crear($idMateria, $nivelAcademico, $idTurno, $modalidad, $lugarOEnlace);
                setMensaje('success', 'Oferta creada correctamente.');
                redirigir('ofertas_listar.php');
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'Duplicate')) {
                    $errores[] = "Ya existe una oferta para esa materia, nivel y turno.";
                } else {
                    $errores[] = "Error al crear la oferta: " . $e->getMessage();
                }
            }
        }
    }

    // ACCIÓN: Cerrar oferta
    if ($accion === 'cerrar_oferta') {
        $idOferta = (int)($_POST['id_oferta'] ?? 0);
        $ofertaModel->cerrar($idOferta);
        setMensaje('success', 'Oferta cerrada correctamente.');
        redirigir('ofertas_listar.php');
    }

    // ACCIÓN: Eliminar oferta
    if ($accion === 'eliminar_oferta') {
        $idOferta = (int)($_POST['id_oferta'] ?? 0);
        $ofertaModel->eliminar($idOferta);
        setMensaje('success', 'Oferta eliminada correctamente.');
        redirigir('ofertas_listar.php');
    }
}

// Datos para la vista
$filtroEstado = $_GET['estado'] ?? null;
$ofertas = $ofertaModel->obtenerTodas($filtroEstado);
$materias = $materiaModel->obtenerTodas();
$turnos = $turnoModel->obtenerTodos();

// Estadísticas (una sola consulta agregada por estado)
$contadorEstados = $ofertaModel->contarPorEstado();
$totalAbiertas   = $contadorEstados['abierta'];
$totalAsignadas  = $contadorEstados['asignada'];
$totalCerradas   = $contadorEstados['cerrada'];

require_once __DIR__ . '/../views/ofertas/listar.php';
