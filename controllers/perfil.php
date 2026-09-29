<?php
// =========================================================
// CONTROLADOR: MI PERFIL (perfil.php)
// ---------------------------------------------------------
// Disponible para cualquier rol autenticado. Permite al usuario
// actualizar sus datos personales (nombre, apellido, correo,
// teléfono) y cambiar su contraseña de forma opcional.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

$usuarioModel = new UsuarioModel($pdo);
$idUsuario = $_SESSION['id_usuario'] ?? 0;

$errores = []; // Acumulador de errores de validación

// ===== Procesamiento del formulario (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('perfil.php');
    }

    $datos = [
        'nombre'      => limpiarTexto($_POST['nombre'] ?? ''),
        'apellido'    => limpiarTexto($_POST['apellido'] ?? ''),
        'correo'      => limpiarTexto($_POST['correo'] ?? ''),
        'telefono'    => limpiarTexto($_POST['telefono'] ?? ''),
        'clave_nueva' => $_POST['clave_nueva'] ?? '',
        'clave_conf'  => $_POST['clave_conf'] ?? '',
    ];

    // Validaciones de los datos personales
    if (mb_strlen($datos['nombre']) < 2 || !validarLetras($datos['nombre'])) {
        $errores[] = "El nombre debe tener al menos 2 caracteres y solo letras.";
    }
    if (mb_strlen($datos['apellido']) < 2 || !validarLetras($datos['apellido'])) {
        $errores[] = "El apellido debe tener al menos 2 caracteres y solo letras.";
    }
    if (!filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido.";
    }
    if (!empty($datos['telefono']) && !preg_match('/^[0-9+\s()\-]{7,20}$/', $datos['telefono'])) {
        $errores[] = "El teléfono no es válido.";
    }

    // Validaciones de la nueva contraseña (solo si decide cambiarla)
    if (!empty($datos['clave_nueva'])) {
        if (strlen($datos['clave_nueva']) < 8) {
            $errores[] = "La nueva contraseña debe tener al menos 8 caracteres.";
        }
        if (!preg_match('/[A-Za-z]/', $datos['clave_nueva']) || !preg_match('/[0-9]/', $datos['clave_nueva'])) {
            $errores[] = "La nueva contraseña debe combinar letras y números.";
        }
        if ($datos['clave_nueva'] !== $datos['clave_conf']) {
            $errores[] = "La confirmación de contraseña no coincide.";
        }
    }

    // Si todo está correcto, se actualiza el perfil
    if (empty($errores)) {
        try {
            $usuarioModel->actualizarPerfilPropio($idUsuario, $datos);
            $_SESSION['nombre'] = $datos['nombre']; // Refrescar el nombre visible en el navbar
            setMensaje('success', 'Tu perfil fue actualizado correctamente.');
            redirigir('perfil.php');
        } catch (PDOException $e) {
            // La excepción ocurre por el correo duplicado (columna UNIQUE)
            $errores[] = "No se pudo actualizar: el correo ya está en uso por otra cuenta.";
        }
    }
}

// Se cargan los datos actuales del usuario para pre-cargar el formulario
$usuario = $usuarioModel->obtenerPorId($idUsuario);
$tituloPagina = 'Mi Perfil - Sistema de Tutorías';
include __DIR__ . '/../views/perfil/index.php';