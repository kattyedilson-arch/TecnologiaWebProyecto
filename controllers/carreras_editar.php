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

// Solo el administrador puede editar carreras
verificarRol('administrador');

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

    $nombre = capitalizarNombre(limpiarTexto($_POST['nombre_carrera'] ?? ''));

    // Validaciones de longitud y caracteres permitidos
    if (mb_strlen($nombre) < 4) {
        $errores[] = "El nombre de la carrera debe tener al menos 4 caracteres.";
    }
    if (mb_strlen($nombre) > 150) {
        $errores[] = "El nombre de la carrera no puede superar los 150 caracteres.";
    }
    if (!empty($nombre) && !validarEtiqueta($nombre)) {
        $errores[] = "El nombre de la carrera contiene caracteres no permitidos.";
    }
    // Evitar duplicados (ignora mayúsculas/minúsculas), excluyendo la propia carrera
    if (!empty($nombre) && $carreraModel->existeNombre($nombre, $id)) {
        $errores[] = "Ya existe una carrera con ese nombre.";
    }

    if (empty($errores)) {
        try {
            $carreraModel->actualizar($id, $nombre);
            setMensaje('success', 'Carrera actualizada correctamente.');
            redirigir('carreras_listar.php');
        } catch (PDOException $e) {
            error_log("Error al actualizar carrera (id=$id): " . $e->getMessage());
            $errores[] = "No se pudo actualizar la carrera. Inténtalo de nuevo.";
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