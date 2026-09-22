<?php
// =========================================================
// PUNTO DE ENTRADA PRINCIPAL (index.php)
// ---------------------------------------------------------
// Este es el archivo que se abre al entrar al sistema
// (http://localhost:8001/). Se muestra la PORTADA o LANDING
// profesional (views/landing/index.php) ANTES del login.
//
// Flujo:
//   index.php  ->  Landing pública (?? no sesión ??)
//            \->  Panel según rol (?? ya inició sesión ??)
//   El formulario de acceso está en views/login/login.php
// =========================================================

require_once __DIR__ . '/includes/funciones.php';
iniciarSesion();

// Si el usuario ya inició sesión, va directo a su panel
if (isset($_SESSION['id_usuario'])) {
    if ($_SESSION['rol'] === 'administrador') {
        header('Location: controllers/dashboard.php');
    } elseif ($_SESSION['rol'] === 'tutor') {
        header('Location: views/tutor/panel.php');
    } elseif ($_SESSION['rol'] === 'estudiante') {
        header('Location: views/estudiante/panel.php');
    } else {
        header('Location: views/login/login.php');
    }
    exit;
}

// Sin sesión: mostrar la portada pública con Vue 3
include __DIR__ . '/views/landing/index.php';