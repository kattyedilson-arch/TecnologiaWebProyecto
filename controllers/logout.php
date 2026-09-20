<?php
// =========================================================
// CONTROLADOR: CERRAR SESIÓN (logout.php)
// ---------------------------------------------------------
// Destruye la sesión actual de PHP (la información del usuario
// logueado) y devuelve al usuario a la pantalla de inicio.
// =========================================================

require_once '../includes/funciones.php';
iniciarSesion();          // Reabre la sesión para poder destruirla
session_unset();          // Elimina todas las variables de sesión (id_usuario, rol, nombre...)
session_destroy();        // Destruye físicamente el archivo de sesión del servidor
header('Location: ../views/login/login.php');
exit;