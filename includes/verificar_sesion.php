<?php
// =========================================================
// PROTECCIÓN DE SESIÓN (verificar_sesion.php)
// ---------------------------------------------------------
// Este archivo se incluye al inicio de TODOS los controllers.
// Si el usuario no ha iniciado sesión (no existe 'id_usuario'
// en $_SESSION), se le redirige al login y se detiene la
// ejecución. Así ninguna página interna queda accesible
// sin autenticación.
// =========================================================

require_once __DIR__ . '/rol_panel.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['id_usuario'])) {
    header('Location: /views/login/login.php');
    exit;
}

// =========================================================
// CONTROL DE ROLES (reutilizable por controllers y vistas)
// ---------------------------------------------------------
// Verifica que el rol de la sesión esté entre los permitidos.
// Si no, redirige al panel de su rol (o al login) y detiene.
// Uso: requerirRol('administrador');  o  requerirRol('tutor', 'administrador');
// =========================================================
function requerirRol(...$rolesPermitidos)
{
    $rol = $_SESSION['rol'] ?? '';
    if (in_array($rol, $rolesPermitidos, true)) {
        return;
    }
    redirigirAlPanel();
}