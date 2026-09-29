<?php
// =========================================================
// CONTROLADOR: GUARDAR ACTA (acta_guardar.php)
// ---------------------------------------------------------
// POST-only con CSRF. Guarda las notas por jurado del acta
// (accion=notas, recalcula nota_final/resultado) o firma el acta
// bloqueándola (accion=firmar, solo coordinador/admin). Notifica
// al estudiante cuando su acta queda firmada.
// Permiso: tramitar_modalidad_mg (admin, coord, auxiliar MG).
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ActaModel.php';
require_once __DIR__ . '/../models/DeclaracionModel.php';
require_once __DIR__ . '/../models/NotificacionModel.php';
require_once __DIR__ . '/../models/JuradoModel.php';

if (!tienePermiso('tramitar_modalidad_mg')) {
    requerirPermiso('tramitar_modalidad_mg');
}

$volverLista = 'mg_jurados.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volverLista);
}
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir($volverLista);
}

$id = (int)($_POST['id_declaracion'] ?? 0);
$accion = $_POST['accion'] ?? '';

$declaracionModel = new DeclaracionModel($pdo);
$declaracion = $id > 0 ? $declaracionModel->obtenerPorId($id) : null;

if (!$declaracion || $declaracion['estado'] !== 'aprobada') {
    setMensaje('danger', 'La declaración no existe o aún no está aprobada.');
    redirigir($volverLista);
}

$volver = 'mg_acta.php?id=' . $id;

$actaModel = new ActaModel($pdo);
$actaModel->abrirSiVacio($id);
$notaMax = $actaModel->notaMax();
$acta = $actaModel->obtenerPorDeclaracion($id);
if (!$acta) {
    setMensaje('danger', 'No se pudo crear el acta para esta declaración.');
    redirigir($volverLista);
}

// ---- Guardar / actualizar notas por jurado ----
if ($accion === 'notas') {
    if ($acta['estado'] === 'firmada') {
        setMensaje('warning', 'El acta ya fue firmada y no admite más cambios.');
        redirigir($volver);
    }

    $notasPost = $_POST['nota'] ?? [];
    $comentariosPost = $_POST['comentario'] ?? [];

    $porJurado = [];
    $invalida = false;
    foreach ($notasPost as $idJurado => $valor) {
        $idJurado = (int)$idJurado;
        $nota = trim((string)$valor);
        if ($nota === '') {
            continue; // jurado sin nota se omite
        }
        if (!is_numeric($nota) || (float)$nota < 0 || (float)$nota > $notaMax) {
            $invalida = true;
            break;
        }
        $porJurado[$idJurado] = [
            'nota'       => (float)$nota,
            'comentario' => $comentariosPost[$idJurado] ?? '',
        ];
    }

    if ($invalida) {
        setMensaje('danger', 'Las notas deben ser numéricas y estar entre 0 y ' . $notaMax . '.');
        redirigir($volver);
    }
    if ($porJurado === []) {
        setMensaje('danger', 'Registrá al menos la nota de un jurado.');
        redirigir($volver);
    }

    $resumen = $actaModel->guardarNotas(
        (int)$acta['id_acta'],
        $porJurado,
        $_POST['fecha_defensa'] ?? '',
        $_POST['lugar'] ?? '',
        $_POST['observaciones'] ?? ''
    );

    if ($resumen === false) {
        setMensaje('warning', 'El acta ya fue firmada y no admite más cambios.');
        redirigir($volver);
    }

    $textoResultado = $resumen['resultado'] === 'aprobado' ? 'APROBADO' : 'REPROBADO';
    setMensaje(
        'success',
        'Notas guardadas. Nota final: ' . number_format((float)$resumen['nota_final'], 2) . ' (' . $textoResultado . ').'
    );
    redirigir($volver);
}

// ---- Firmar el acta (bloquea + notifica al estudiante) ----
elseif ($accion === 'firmar') {
    if (!tienePermiso('aprobar_modalidad_mg')) {
        setMensaje('danger', 'Solo el coordinador de MG puede firmar actas.');
        redirigir($volver);
    }
    if ($acta['nota_final'] === null || $acta['resultado'] === 'pendiente') {
        setMensaje('danger', 'Para firmar el acta debes registrar al menos una nota.');
        redirigir($volver);
    }

    // Presidente del tribunal: da fe junto al coordinador
    $juradoModel = new JuradoModel($pdo);
    $presidente = null;
    foreach ($juradoModel->obtenerPorDeclaracion($id) as $miembro) {
        if (($miembro['rol_jurado'] ?? '') === 'presidente') {
            $presidente = trim(($miembro['nombre'] ?? '') . ' ' . ($miembro['apellido'] ?? ''));
            break;
        }
    }

    $firmado = $actaModel->firmar((int)$acta['id_acta'], (int)$_SESSION['id_usuario'], $presidente);

    if (!$firmado) {
        setMensaje('danger', 'No se pudo firmar el acta. Debe estar abierta y con notas registradas.');
        redirigir($volver);
    }

    $resultado = $acta['resultado'];
    $textoResultado = $resultado === 'aprobado' ? 'APROBADO' : 'REPROBADO';
    $nombreEstudiante = trim($declaracion['nombre'] . ' ' . $declaracion['apellido']);
    $notifier = new NotificacionModel($pdo);
    $notifier->crear(
        (int)$declaracion['id_usuario'],
        'acta',
        'Acta de calificación firmada',
        'Hola ' . $nombreEstudiante . ', tu acta de defensa de ' . $declaracion['modalidad_nombre']
            . ' fue firmada con nota ' . number_format((float)$acta['nota_final'], 2) . ' (' . $textoResultado . ').',
        '/controllers/estudiante_declaracion.php'
    );

    setMensaje('success', 'Acta firmada: ' . $textoResultado . ' con nota ' . number_format((float)$acta['nota_final'], 2) . '.');
    redirigir($volver);
} else {
    setMensaje('danger', 'Acción no válida.');
    redirigir($volver);
}