<?php
// =========================================================
// CONTROLADOR: ELIMINAR MODALIDAD (modalidades_eliminar.php)
// ---------------------------------------------------------
// Retira una modalidad del catálogo por POST con CSRF.
// Si ya fue usada en declaraciones de estudiantes, las claves
// foráneas impedirán el borrado (solo administrador).
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/permisos.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/ModalidadModel.php';

requerirPermiso('registrar_modalidad_mg');

$volver = 'admin_modalidades.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir($volver);
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir($volver);
    }

    $modalidadModel = new ModalidadModel($pdo);
    try {
        $modalidadModel->eliminar($id);
        setMensaje('success', 'Modalidad retirada del catálogo.');
    } catch (PDOException $e) {
        setMensaje('danger', 'No se pudo eliminar: la modalidad ya tiene declaraciones asociadas.');
    }
}

redirigir($volver);