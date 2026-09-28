<?php
// =========================================================
// CONTROLADOR: ELIMINAR EVENTO DE CALENDARIO (calendario_eliminar.php)
// ---------------------------------------------------------
// Borra un hito del calendario MG (HU-022) por POST con CSRF.
// Permiso: gestionar_calendario_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/CalendarioMgModel.php';

requerirPermiso('gestionar_calendario_mg');

$volver = 'mg_calendario.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}

$id = (int)($_POST['id'] ?? 0);
$volver .= (int)($_POST['id_cohorte'] ?? 0) > 0 ? '?id_cohorte=' . (int)$_POST['id_cohorte'] : '';

if ($id > 0) {
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir($volver);
    }

    $calendarioModel = new CalendarioMgModel($pdo);
    try {
        $calendarioModel->eliminar($id);
        setMensaje('success', 'Hito eliminado correctamente.');
    } catch (PDOException $e) {
        setMensaje('danger', 'No se pudo eliminar el hito.');
    }
}

redirigir($volver);