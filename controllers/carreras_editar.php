<?php
// =========================================================
// CONTROLADOR: EDITAR CARRERA (carreras_editar.php)
// ---------------------------------------------------------
// Carga una carrera para su edición y procesa el POST del
// formulario con las mismas validaciones que al crear.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/CarreraModel.php';

requerirRol('administrador');

$carreraModel = new CarreraModel($pdo);

// El id puede venir por GET (al entrar) o por POST (al guardar)
$id = $_GET['id'] ?? $_POST['id_carrera'] ?? null;
if (!$id) {
    redirigir('carreras_listar.php');
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('carreras_listar.php');
    }

    $nombreOriginal = limpiarTexto($_POST['nombre_carrera'] ?? '');

    // Corrección automática de typos y formato de título (p. ej. "ingenieria en shistemas")
    $nombre = corregirNombre($nombreOriginal);
    if ($nombre !== $nombreOriginal) {
        setMensaje('info', "Se corrigió el nombre a: \"$nombre\".");
    }

    // Validaciones de longitud y caracteres permitidos
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
    // Duplicados: se excluye la propia carrera que se está editando
    if (empty($errores) && $carreraModel->existePorNombre($nombre, (int)$id)) {
        $errores[] = "Ya existe otra carrera registrada con el nombre \"$nombre\". No se permiten carreras duplicadas.";
    }

    if (empty($errores)) {
        try {
            $carreraModel->actualizar($id, $nombre);
            $msgExito = 'Carrera actualizada correctamente.';
            if (isset($nombreOriginal) && $nombre !== $nombreOriginal) {
                $msgExito .= " Se corrigió el nombre a: \"$nombre\".";
            }
            setMensaje('success', $msgExito);
            redirigir('carreras_listar.php');
        } catch (PDOException $e) {
            $errores[] = "Error al actualizar la carrera. Inténtalo de nuevo.";
        }
    }
}

// Se carga la carrera actual para pre-cargar el formulario
$carrera_actual = $carreraModel->obtenerPorId($id);
if (!$carrera_actual) {
    setMensaje('danger', 'La carrera solicitada no existe.');
    redirigir('carreras_listar.php');
}

require_once __DIR__ . '/../views/carreras/editar.php';