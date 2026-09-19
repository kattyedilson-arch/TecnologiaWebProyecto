<?php
// =========================================================
// CONTROLADOR: CREAR CARRERA (carreras_crear.php)
// ---------------------------------------------------------
// Muestra el formulario de alta de carreras y procesa su POST.
// Valida longitud (4-150) y que solo contenga caracteres
// permitidos antes de guardar.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/CarreraModel.php';

$carreraModel = new CarreraModel($pdo);
$errores = [];

// ===== Procesamiento del formulario (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('carreras_crear.php');
    }

    $nombre = limpiarTexto($_POST['nombre_carrera'] ?? '');

    // Validaciones: longitud mínima, máxima y juego de caracteres
    if (mb_strlen($nombre) < 4) {
        $errores[] = "El nombre de la carrera debe tener al menos 4 caracteres.";
    }
    if (mb_strlen($nombre) > 150) {
        $errores[] = "El nombre de la carrera no puede superar los 150 caracteres.";
    }
    if (!empty($nombre) && !validarEtiqueta($nombre)) {
        $errores[] = "El nombre de la carrera contiene caracteres no permitidos.";
    }

    if (empty($errores)) {
        try {
            $carreraModel->crear($nombre);
            setMensaje('success', 'Carrera registrada correctamente.');
            redirigir('carreras_listar.php');
        } catch (PDOException $e) {
            $errores[] = "Error al registrar la carrera: " . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../views/carreras/crear.php';