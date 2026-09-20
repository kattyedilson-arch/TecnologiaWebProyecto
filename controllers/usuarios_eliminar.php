<?php
// =========================================================
// CONTROLADOR: ELIMINAR USUARIO (usuarios_eliminar.php)
// ---------------------------------------------------------
// Recibe el id por GET (el enlace de eliminar) y borra el
// usuario. Impide que el administrador se elimine a sí mismo.
// Si el usuario tiene un perfil de estudiante/tutor asociado,
// la restricción de clave foránea lanza excepción y se informa.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

requerirRol('administrador');

$usuarioModel = new UsuarioModel($pdo);

$id = $_GET['id'] ?? null;

if ($id) {
    // Protección CSRF: el enlace de eliminación debe traer el token
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('usuarios_listar.php');
    }

    // El administrador no puede eliminarse a sí mismo
    if ((int)$id === (int)($_SESSION['id_usuario'] ?? 0)) {
        setMensaje('danger', 'No puedes eliminar tu propia cuenta de administrador.');
        redirigir('usuarios_listar.php');
    }

    try {
        $usuarioModel->eliminar($id);
        setMensaje('success', 'Usuario eliminado correctamente.');
    } catch (PDOException $e) {
        // Las FK de estudiantes/tutores impiden borrar perfiles con datos asociados
        setMensaje('danger', 'No se pudo eliminar: este usuario tiene un perfil de estudiante o tutor asociado. Elimina primero ese registro.');
    }
}

redirigir('usuarios_listar.php');