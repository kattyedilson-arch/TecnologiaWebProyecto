<?php
// =========================================================
// CONTROLADOR: EDITAR MATERIA (materias_editar.php)
// ---------------------------------------------------------
// Carga una materia para su edición y procesa el POST del
// formulario con las mismas validaciones que al crear.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/MateriaModel.php';
require_once __DIR__ . '/../models/CarreraModel.php';

$materiaModel = new MateriaModel($pdo);
$carreraModel = new CarreraModel($pdo);

$id = $_GET['id'] ?? $_POST['id_materia'] ?? null;
if (!$id) {
    redirigir('materias_listar.php');
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('materias_listar.php');
    }

    $datos = [
        'nombre_materia' => limpiarTexto($_POST['nombre_materia'] ?? ''),
        'id_carrera'     => $_POST['id_carrera'] ?? '',
    ];

    // Validaciones del nombre
    if (mb_strlen($datos['nombre_materia']) < 3) {
        $errores[] = "El nombre de la materia debe tener al menos 3 caracteres.";
    }
    if (mb_strlen($datos['nombre_materia']) > 150) {
        $errores[] = "El nombre de la materia no puede superar los 150 caracteres.";
    }
    if (!empty($datos['nombre_materia']) && !validarEtiqueta($datos['nombre_materia'])) {
        $errores[] = "El nombre de la materia contiene caracteres no permitidos.";
    }
    // Validar que la carrera seleccionada exista
    if (!empty($datos['id_carrera']) && !$carreraModel->obtenerPorId((int)$datos['id_carrera'])) {
        $errores[] = "La carrera seleccionada no es válida.";
    }

    if (empty($errores)) {
        try {
            $materiaModel->actualizar($id, $datos);
            setMensaje('success', 'Materia actualizada correctamente.');
            redirigir('materias_listar.php');
        } catch (PDOException $e) {
            $errores[] = "Error al actualizar la materia: " . $e->getMessage();
        }
    }
}

// Se carga la materia actual para pre-cargar el formulario
$materia_actual = $materiaModel->obtenerPorId($id);
if (!$materia_actual) {
    setMensaje('danger', 'La materia solicitada no existe.');
    redirigir('materias_listar.php');
}

$carreras = $carreraModel->obtenerTodas();
require_once __DIR__ . '/../views/materias/editar.php';