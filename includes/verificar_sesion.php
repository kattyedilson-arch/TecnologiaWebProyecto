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

require_once __DIR__ . '/funciones.php';
iniciarSesion();
if (!isset($_SESSION['id_usuario'])) {
    header('Location: ' . base_url() . '/views/login/login.php');
    exit;
}