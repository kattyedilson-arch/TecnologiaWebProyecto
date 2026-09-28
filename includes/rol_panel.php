<?php
// =========================================================
// MAPEO DE ROL -> PANEL (rol_panel.php)
// ---------------------------------------------------------
// Punto ÚNICO donde se define a qué panel va cada usuario según
// su rol. Lo usan verificar_sesion.php (requerirRol / acceso
// denegado) e index.php (redirección tras iniciar sesión).
// =========================================================

// Devuelve la ruta del panel principal según el rol de la sesión.
function destinoPanelRol()
{
    $rol = $_SESSION['rol'] ?? '';
    switch ($rol) {
        case 'administrador': return '/controllers/dashboard.php';
        case 'tutor':         return '/views/tutor/panel.php';
        case 'estudiante':    return '/views/estudiante/panel.php';
        case 'coordinador_mg':
        case 'auxiliar_mg':   return '/controllers/panel_mg.php';
        default:              return '/views/login/login.php';
    }
}

// Redirige al panel correspondiente al rol de la sesión y detiene.
function redirigirAlPanel()
{
    header('Location: ' . destinoPanelRol());
    exit;
}