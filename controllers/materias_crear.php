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

requerirRol('administrador');

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

    // Corrección automática de typos y formato de título
    $nombreOriginal = $datos['nombre_materia'];
    $datos['nombre_materia'] = corregirNombre($nombreOriginal);
    if ($datos['nombre_materia'] !== $nombreOriginal) {
        setMensaje('info', "Se corrigió el nombre a: \"{$datos['nombre_materia']}\".");
    }

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
    if (!empty($datos['nombre_materia']) && !validarNombreLogico($datos['nombre_materia'])) {
        $errores[] = "El nombre de la materia no parece una materia real (se necesitan letras con vocales y sin repeticiones, p. ej. no \"hhh\").";
    }

    // La carrera es obligatoria y debe existir
    $carreraElegida = null;
    if (empty($datos['id_carrera'])) {
        $errores[] = "Debes seleccionar la carrera a la que pertenece la materia.";
    } else {
        $carreraElegida = $carreraModel->obtenerPorId((int)$datos['id_carrera']);
        if (!$carreraElegida) {
            $errores[] = "La carrera seleccionada no es válida.";
        }
    }
    // Duplicados: no se admite la misma materia repetida dentro de la misma carrera
    if (empty($errores) && $materiaModel->existeEnCarrera($datos['nombre_materia'], (int)$datos['id_carrera'])) {
        $errores[] = "Ya existe la materia \"{$datos['nombre_materia']}\" en la carrera \"{$carreraElegida['nombre_carrera']}\". No se permiten materias repetidas en la misma carrera.";
    }

    if (empty($errores)) {
        try {
            $materiaModel->crear($datos);
            $msgExito = 'Materia registrada correctamente.';
            if (isset($nombreOriginal) && $datos['nombre_materia'] !== $nombreOriginal) {
                $msgExito .= " Se corrigió el nombre a: \"{$datos['nombre_materia']}\".";
            }
            setMensaje('success', $msgExito);
            redirigir('materias_listar.php');
        } catch (PDOException $e) {
            $errores[] = "Ocurrió un error al guardar la materia. Inténtalo de nuevo.";
        }
    }
}

// Lista de carreras para el <select> del formulario
$carreras = $carreraModel->obtenerTodas();
require_once __DIR__ . '/../views/materias/crear.php';