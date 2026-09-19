<?php
// =========================================================
// CONTROLADOR: EDITAR USUARIO (usuarios_editar.php)
// ---------------------------------------------------------
// Carga los datos de un usuario para su edición y procesa el
// POST del formulario. Valida los mismos campos que al crear,
// además de impedir que el administrador se desactive a sí mismo
// y permitir cambiar la contraseña de forma opcional.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/RolModel.php';
require_once __DIR__ . '/../models/CarreraModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

$usuarioModel = new UsuarioModel($pdo);
$rolModel = new RolModel($pdo);
$carreraModel = new CarreraModel($pdo);
$estudianteModel = new EstudianteModel($pdo);

// Id del rol "estudiante" (para mostrar/validar carrera y semestre)
$roles = $rolModel->obtenerTodos();
$idRolEstudiante = null;
foreach ($roles as $r) {
    if (strtolower(trim($r['nombre_rol'])) === 'estudiante') {
        $idRolEstudiante = (int)$r['id_rol'];
        break;
    }
}
$carreras = $carreraModel->obtenerTodas();

// El id puede venir por GET (al entrar a editar) o por POST (al guardar)
$id = $_GET['id'] ?? $_POST['id_usuario'] ?? null;
if (!$id) {
    redirigir('usuarios_listar.php'); // Sin id no hay edición
}

$errores = [];

// ===== Procesamiento del formulario (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('usuarios_listar.php');
    }

    $datos = [
        'id_rol'     => $_POST['id_rol'] ?? '',
        'nombre'     => limpiarTexto($_POST['nombre'] ?? ''),
        'apellido'   => limpiarTexto($_POST['apellido'] ?? ''),
        'correo'     => limpiarTexto($_POST['correo'] ?? ''),
        'usuario'    => limpiarTexto($_POST['usuario'] ?? ''),
        'telefono'   => limpiarTexto($_POST['telefono'] ?? ''),
        'estado'     => $_POST['estado'] ?? 'activo',
        'clave_nueva' => $_POST['clave_nueva'] ?? '',
        'clave_conf'  => $_POST['clave_conf'] ?? '',
        'id_carrera'  => $_POST['id_carrera'] ?? '',
        'semestre'    => $_POST['semestre'] ?? '1',
    ];

    $esEstudiante = ($idRolEstudiante !== null && (int)$datos['id_rol'] === $idRolEstudiante);

    $idsRoles = array_column($roles, 'id_rol');

    // ---- Validaciones (mismas reglas que en crear) ----
    if (in_array('', [$datos['nombre'], $datos['apellido'], $datos['correo'], $datos['usuario'], $datos['id_rol']], true)) {
        $errores[] = "Todos los campos marcados con * son obligatorios.";
    }
    // Carrera y semestre obligatorios cuando el rol es estudiante
    if ($esEstudiante) {
        if ($datos['id_carrera'] === '') {
            $errores[] = "Debes elegir la carrera del estudiante.";
        } elseif (!in_array((int)$datos['id_carrera'], array_column($carreras, 'id_carrera'), true)) {
            $errores[] = "La carrera seleccionada no es válida.";
        }
        if (!preg_match('/^[1-9]$|^1[0-2]$/', $datos['semestre'])) {
            $errores[] = "El semestre debe ser un número entre 1 y 12.";
        }
    }
    if ($datos['id_rol'] !== '' && !in_array((int)$datos['id_rol'], $idsRoles, true)) {
        $errores[] = "El rol seleccionado no es válido.";
    }
    if (!in_array($datos['estado'], ['activo', 'inactivo'], true)) {
        $errores[] = "El estado seleccionado no es válido.";
    }
    if (!empty($datos['nombre']) && (mb_strlen($datos['nombre']) < 2 || !validarLetras($datos['nombre']))) {
        $errores[] = "El nombre debe tener al menos 2 caracteres y solo puede contener letras.";
    }
    if (!empty($datos['apellido']) && (mb_strlen($datos['apellido']) < 2 || !validarLetras($datos['apellido']))) {
        $errores[] = "El apellido debe tener al menos 2 caracteres y solo puede contener letras.";
    }
    if (!empty($datos['correo']) && !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo no tiene un formato válido.";
    }
    if (!empty($datos['usuario']) && (mb_strlen($datos['usuario']) < 3 || !preg_match('/^[a-zA-Z0-9_.]+$/', $datos['usuario']))) {
        $errores[] = "El usuario debe tener al menos 3 caracteres y solo letras, números, punto o guión bajo.";
    }
    if (!empty($datos['telefono']) && !preg_match('/^[0-9+\s()\-]{7,20}$/', $datos['telefono'])) {
        $errores[] = "El teléfono no es válido. Usa solo números, espacios o el símbolo +.";
    }
    // Seguridad: el admin no puede desactivar su propia cuenta
    if ((int)$id === (int)($_SESSION['id_usuario'] ?? 0) && $datos['estado'] === 'inactivo') {
        $errores[] = "No puedes desactivar tu propia cuenta de administrador.";
    }
    // Validaciones de nueva contraseña (opcional)
    if (!empty($datos['clave_nueva'])) {
        if (strlen($datos['clave_nueva']) < 8) {
            $errores[] = "La nueva contraseña debe tener al menos 8 caracteres.";
        }
        if (!preg_match('/[A-Za-z]/', $datos['clave_nueva']) || !preg_match('/[0-9]/', $datos['clave_nueva'])) {
            $errores[] = "La nueva contraseña debe combinar letras y números.";
        }
        if ($datos['clave_nueva'] !== $datos['clave_conf']) {
            $errores[] = "La confirmación de la nueva contraseña no coincide.";
        }
    }

    // Si no hay errores, se actualizan los datos
    if (empty($errores)) {
        try {
            $usuarioModel->actualizar($id, $datos);

            // Si el usuario es (o pasó a ser) estudiante se actualiza su ficha académica
            if ($esEstudiante) {
                $ficha = $estudianteModel->obtenerPorUsuario($id);
                $estudianteModel->guardarOActualizar(
                    (int)$id,
                    (int)$datos['id_carrera'],
                    (int)$datos['semestre'],
                    $ficha['registro_universitario'] ?? ('RU-' . date('y') . mt_rand(10000, 99999))
                );
            }

            setMensaje('success', 'Usuario actualizado correctamente.');
            redirigir('usuarios_listar.php');
        } catch (PDOException $e) {
            // Excepción por duplicados de correo/usuario
            $errores[] = "No se pudo actualizar: el correo o el usuario ya están en uso.";
        }
    }
}

// Se cargan los datos del usuario para pre-cargar el formulario de edición
$usuario_actual = $usuarioModel->obtenerPorId($id);
if (!$usuario_actual) {
    setMensaje('danger', 'El usuario solicitado no existe.');
    redirigir('usuarios_listar.php');
}

// Ficha de estudiante (si la tiene) para pre-cargar carrera y semestre
$estudiante_ficha = $estudianteModel->obtenerPorUsuario((int)$id);

require_once __DIR__ . '/../views/usuarios/editar.php';