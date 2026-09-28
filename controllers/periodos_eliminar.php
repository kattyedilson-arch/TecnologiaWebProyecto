<?php
// =========================================================
// CONTROLADOR: ELIMINAR PERIODO (periodos_eliminar.php)
// ---------------------------------------------------------
// Borra un periodo académico (HU-044) por POST con CSRF.
// La eliminación puede fallar si el periodo ya es usado por
// declaraciones de modalidades de grado (claves foráneas).
// Permiso: gestionar_periodos.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/PeriodoModel.php';

requerirPermiso('gestionar_periodos');

$volver = 'admin_periodos.php';

// Solo se aceptan envíos por POST (evita borrados destructivos por GET)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir($volver);
    }

    $periodoModel = new PeriodoModel($pdo);
    try {
        $periodoModel->eliminar($id);
        setMensaje('success', 'Periodo eliminado correctamente.');
    } catch (PDOException $e) {
        setMensaje('danger', 'No se pudo eliminar: el periodo ya tiene modalidades o registros asociados.');
    }
}

redirigir($volver);