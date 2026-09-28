<?php
// =========================================================
// CONTROLADOR: ELIMINAR MATERIA (materias_eliminar.php)
// ---------------------------------------------------------
// Recibe el id por POST y borra la materia del catálogo. Si la
// materia está vinculada a tutorías o tutores, la clave foránea
// impide la eliminación y se muestra el error.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/MateriaModel.php';

requerirRol('administrador');

// Solo se aceptan envíos por POST (evita borrados destructivos por GET)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    redirigir('materias_listar.php');
}

$id = $_POST['id'] ?? null;

if ($id) {
    // Protección CSRF: el enlace de eliminación debe traer el token
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('materias_listar.php');
    }

    $materiaModel = new MateriaModel($pdo);
    try {
        $materiaModel->eliminar($id);
        setMensaje('success', 'Materia eliminada del catálogo correctamente.');
    } catch (PDOException $e) {
        // FK de tutor_materia / tutorias bloquea el borrado
        setMensaje('danger', 'No se pudo eliminar: la materia está asociada a tutorías o tutores.');
    }
}

redirigir('materias_listar.php');