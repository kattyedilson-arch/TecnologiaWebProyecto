<?php
// =========================================================
// CONTROLADOR: CREAR MATERIA (materias_crear.php)
// ---------------------------------------------------------
// Muestra el formulario de alta de materias y procesa su POST.
// Valida el nombre (3-150 caracteres permitidos) y que la
// carrera seleccionada exista realmente.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/MateriaModel.php';
require_once __DIR__ . '/../models/CarreraModel.php';

$materiaModel = new MateriaModel($pdo);
$carreraModel = new CarreraModel($pdo);

$errores = [];

// ===== Procesamiento del formulario (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('materias_crear.php');
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

    // Validar que la carrera seleccionada exista (si eligió una)
    if (!empty($datos['id_carrera']) && !$carreraModel->obtenerPorId((int)$datos['id_carrera'])) {
        $errores[] = "La carrera seleccionada no es válida.";
    }

    if (empty($errores)) {
        try {
            $materiaModel->crear($datos);
            setMensaje('success', 'Materia registrada correctamente.');
            redirigir('materias_listar.php');
        } catch (PDOException $e) {
            $errores[] = "Ocurrió un error al guardar la materia: " . $e->getMessage();
        }
    }
}

// Lista de carreras para el <select> del formulario
$carreras = $carreraModel->obtenerTodas();
require_once __DIR__ . '/../views/materias/crear.php';