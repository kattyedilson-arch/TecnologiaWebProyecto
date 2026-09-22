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

requerirRol('administrador');

$carreraModel = new CarreraModel($pdo);
$errores = [];

// ===== Procesamiento del formulario (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('carreras_crear.php');
    }

    $nombreOriginal = limpiarTexto($_POST['nombre_carrera'] ?? '');

    // Corrección automática de typos y formato de título (p. ej. "ingenieria en shistemas")
    $nombre = corregirNombre($nombreOriginal);
    if ($nombre !== $nombreOriginal) {
        setMensaje('info', "Se corrigió el nombre a: \"$nombre\".");
    }

    // Validaciones: longitud mínima, máxima y juego de caracteres
    if (mb_strlen($nombre) < 5) {
        $errores[] = "El nombre de la carrera debe tener al menos 5 caracteres.";
    }
    if (mb_strlen($nombre) > 150) {
        $errores[] = "El nombre de la carrera no puede superar los 150 caracteres.";
    }
    if (!empty($nombre) && !validarEtiqueta($nombre)) {
        $errores[] = "El nombre de la carrera contiene caracteres no permitidos.";
    }
    if (!empty($nombre) && !validarNombreLogico($nombre)) {
        $errores[] = "El nombre de la carrera no parece una carrera real (se necesitan letras con vocales y sin repeticiones, p. ej. no \"hhh\").";
    }
    // Regla principal: SOLO se permiten carreras reales de la lista global.
    // (así ya no es posible registrar nombres inventados, con typos o fragmentos)
    if (empty($errores)) {
        $nombreCanonico = buscarCarreraGlobalExacta($nombre);
        if ($nombreCanonico === null) {
            $errores[] = "El nombre \"$nombre\" no es una carrera real reconocida. Escribe y elige una de las sugerencias (carreras reales de todo el mundo).";
        } else {
            $nombre = $nombreCanonico;
        }
    }
    // Duplicados: se compara ignorando mayúsculas, tildes y espacios extra
    if (empty($errores) && $carreraModel->existePorNombre($nombre)) {
        $errores[] = "Ya existe una carrera registrada con el nombre \"$nombre\". No se permiten carreras duplicadas.";
    }

    if (empty($errores)) {
        try {
            $carreraModel->crear($nombre);
            $msgExito = 'Carrera registrada correctamente.';
            if (isset($nombreOriginal) && $nombre !== $nombreOriginal) {
                $msgExito .= " Se corrigió el nombre a: \"$nombre\".";
            }
            setMensaje('success', $msgExito);
            redirigir('carreras_listar.php');
        } catch (PDOException $e) {
            $errores[] = "Error al registrar la carrera. Inténtalo de nuevo.";
        }
    }
}

require_once __DIR__ . '/../views/carreras/crear.php';