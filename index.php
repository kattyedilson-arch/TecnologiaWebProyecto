<?php
// =========================================================
// PUNTO DE ENTRADA PRINCIPAL (index.php)
// ---------------------------------------------------------
// Este es el archivo que se abre al entrar al sistema.
// Todo el sistema está basado en PHP puro con patrón MVC:
//
//   controllers/  -> Lógica de negocio y validaciones
//   models/       -> Clases que comunican con la base de datos (PDO)
//   views/        -> Plantillas HTML con Bootstrap (solo presentación)
//   includes/     -> Funciones auxiliares y protección de sesión
//   config/       -> Configuración de la conexión a MySQL
//
// El flujo habitual es: el navegador inicia aquí, se redirige al
// login y, al validar credenciales, se envía a su panel según el rol.
// =========================================================

// Redirección inicial al login del sistema
header('Location: views/login/login.php');
exit;
