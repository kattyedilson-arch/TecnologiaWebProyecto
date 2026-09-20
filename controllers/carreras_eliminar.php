<?php
// =========================================================
// CONTROLADOR: ELIMINAR CARRERA (carreras_eliminar.php)
// ---------------------------------------------------------
// Recibe el id por GET y borra la carrera. Si la carrera tiene
// materias o estudiantes vinculados, la clave foránea impide
// la eliminación y se muestra el error correspondiente.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/CarreraModel.php';

// Solo el administrador puede eliminar carreras
verificarRol('administrador');

$id = $_GET['id'] ?? null;

if ($id) {
    // Protección CSRF: el enlace de eliminación debe traer el token
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('carreras_listar.php');
    }

    $carreraModel = new CarreraModel($pdo);
    try {
        $carreraModel->eliminar($id);
        setMensaje('success', 'Carrera eliminada correctamente.');
    } catch (PDOException $e) {
        // FK de la tabla materias/estudiantes bloquea el borrado
        setMensaje('danger', 'No se pudo eliminar: la carrera tiene materias o estudiantes vinculados.');
    }
}

redirigir('carreras_listar.php');