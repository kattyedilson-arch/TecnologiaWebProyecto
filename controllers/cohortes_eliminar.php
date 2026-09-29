<?php
// =========================================================
// CONTROLADOR: ELIMINAR COHORTE MG (cohortes_eliminar.php)
// ---------------------------------------------------------
// Borra una cohorte de Modalidades de Grado (HU-022) por POST
// con CSRF. Sus eventos de calendario se eliminan en cascada.
// Permiso: gestionar_cohortes_mg.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/CohorteMgModel.php';

requerirPermiso('gestionar_cohortes_mg');

$volver = 'admin_cohortes.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir($volver);
    }

    $cohorteModel = new CohorteMgModel($pdo);
    try {
        $cohorteModel->eliminar($id);
        setMensaje('success', 'Cohorte eliminada correctamente.');
    } catch (PDOException $e) {
        setMensaje('danger', 'No se pudo eliminar la cohorte.');
    }
}

redirigir($volver);