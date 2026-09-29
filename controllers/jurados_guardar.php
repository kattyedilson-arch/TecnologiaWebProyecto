<?php
// =========================================================
// CONTROLADOR: GUARDAR JURADO (jurados_guardar.php)
// ---------------------------------------------------------
// Reemplaza el tribunal completo de una declaración aprobada.
// El formulario envía hasta 3 pares id_usuario + rol_jurado.
// POST-only con CSRF. Requiere permiso de tramitación MG.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/DeclaracionModel.php';
require_once __DIR__ . '/../models/JuradoModel.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

requerirPermiso('tramitar_modalidad_mg');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir('mg_jurados.php');
}
if (!verificarTokenCsrf()) {
    setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
    redirigir('mg_jurados.php');
}

$idDeclaracion = (int)($_POST['id_declaracion'] ?? 0);
$volver = 'mg_tribunal.php?id=' . $idDeclaracion;

$declaracionModel = new DeclaracionModel($pdo);
$declaracion = $idDeclaracion > 0 ? $declaracionModel->obtenerPorId($idDeclaracion) : null;
if (!$declaracion || $declaracion['estado'] !== 'aprobada') {
    setMensaje('danger', 'La declaración no existe o no está aprobada.');
    redirigir('mg_jurados.php');
}

$rolesValidos = ['presidente', 'titular', 'suplente'];
$miembros = [];
$usados = [];

for ($i = 1; $i <= 3; $i++) {
    $idUsuario = (int)($_POST['jurado_usuario_' . $i] ?? 0);
    $rol = $_POST['jurado_rol_' . $i] ?? '';
    if ($idUsuario > 0) {
        if (!in_array($rol, $rolesValidos, true)) {
            setMensaje('danger', 'El rol del jurado N.º ' . $i . ' no es válido.');
            redirigir($volver);
        }
        if (in_array($idUsuario, $usados, true)) {
            setMensaje('danger', 'Un docente no puede repetirse en el tribunal.');
            redirigir($volver);
        }
        $usados[] = $idUsuario;
        $miembros[] = ['id_usuario' => $idUsuario, 'rol_jurado' => $rol];
    }
}

if (empty($miembros)) {
    // Vaciar el jurado (seleccionar "Sin asignar" en los tres cupos)
    $juradoModel = new JuradoModel($pdo);
    try {
        $juradoModel->asignar($idDeclaracion, []);
    } catch (RuntimeException $e) {
        setMensaje('danger', $e->getMessage());
        redirigir($volver);
    }
    setMensaje('success', 'El tribunal quedó sin miembros asignados.');
    redirigir($volver);
}

// Verifica que los docentes existan y sean rol tutor
$juradoModel = new JuradoModel($pdo);
$docentes = $juradoModel->obtenerDocentes();
$idsDocentes = array_map(fn($d) => (int)$d['id_usuario'], $docentes);
foreach ($miembros as $miembro) {
    if (!in_array((int)$miembro['id_usuario'], $idsDocentes, true)) {
        setMensaje('danger', 'Uno de los docentes seleccionados no es válido.');
        redirigir($volver);
    }
}

try {
    // Miembros anteriores para avisar únicamente a los docentes nuevos
    $juradoModel = new JuradoModel($pdo);
    $anteriores = $juradoModel->obtenerPorDeclaracion($idDeclaracion);
    $idsAnteriores = array_map(fn($m) => (int)$m['id_usuario'], $anteriores);

    $juradoModel->asignar($idDeclaracion, $miembros);

    // Avisa a cada docente recién incorporado al tribunal
    $notificacionModel = new NotificacionModel($pdo);
    $etiquetasRol = [
        'presidente' => 'presidente',
        'titular'    => 'titular',
        'suplente'   => 'suplente',
    ];
    foreach ($miembros as $miembro) {
        if (in_array((int)$miembro['id_usuario'], $idsAnteriores, true)) {
            continue;
        }
        $notificacionModel->crear(
            (int)$miembro['id_usuario'],
            'acta',
            'Nombrado como jurado de defensa',
            'Has sido asignado como ' . ($etiquetasRol[$miembro['rol_jurado']] ?? $miembro['rol_jurado'])
                . ' del tribunal de ' . trim(($declaracion['nombre'] ?? '') . ' ' . ($declaracion['apellido'] ?? ''))
                . ' (' . ($declaracion['modalidad_nombre'] ?? 'Modalidad de grado') . ').',
            '/views/tutor/defensas.php'
        );
    }

    setMensaje('success', 'Tribunal asignado correctamente.');
} catch (RuntimeException $e) {
    setMensaje('danger', $e->getMessage());
    redirigir($volver);
} catch (PDOException $e) {
    setMensaje('danger', 'No se pudo guardar el tribunal.');
}

redirigir($volver);