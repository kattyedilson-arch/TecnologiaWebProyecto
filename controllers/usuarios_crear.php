<?php
// =========================================================
// CONTROLADOR: CREAR USUARIO (usuarios_crear.php)
// ---------------------------------------------------------
// Muestra el formulario de alta de usuario y procesa su POST.
// Valida todos los campos (rol, nombres, correo, usuario,
// teléfono y contraseña) antes de llamar a UsuarioModel::crear.
// Los duplicados de correo/usuario se capturan por excepción.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/RolModel.php';
require_once __DIR__ . '/../models/CarreraModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';
require_once __DIR__ . '/../models/TutorModel.php';

requerirRol('administrador');

$usuarioModel = new UsuarioModel($pdo);
$rolModel = new RolModel($pdo);
$carreraModel = new CarreraModel($pdo);
$estudianteModel = new EstudianteModel($pdo);
$tutorModel = new TutorModel($pdo);
$errores = [];

// Ids de los roles "estudiante" y "tutor" (para las fichas de perfil)
$roles = $rolModel->obtenerTodos();
$idRolEstudiante = null;
$idRolTutor = null;
foreach ($roles as $r) {
    $nombreRol = strtolower(trim($r['nombre_rol']));
    if ($nombreRol === 'estudiante') {
        $idRolEstudiante = (int)$r['id_rol'];
    } elseif ($nombreRol === 'tutor') {
        $idRolTutor = (int)$r['id_rol'];
    }
}
$carreras = $carreraModel->obtenerTodas();

// Rol preseleccionado en el formulario:
//  - si vuelve de un POST con errores, se mantiene lo que el admin eligió;
//  - si llega ?rol=<nombre> (botón "Nuevo Tutor"), se marca ese rol.
// El valor de ?rol solo se acepta si coincide con un rol existente.
$rolPreseleccionado = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_rol']) && $_POST['id_rol'] !== '') {
    $rolPreseleccionado = (int)$_POST['id_rol'];
} elseif (isset($_GET['rol']) && $_GET['rol'] !== '') {
    $rolPedido = strtolower(trim($_GET['rol']));
    foreach ($roles as $r) {
        if (strtolower(trim($r['nombre_rol'])) === $rolPedido) {
            $rolPreseleccionado = (int)$r['id_rol'];
            break;
        }
    }
}

/**
 * Devuelve el listado al que corresponde volver tras guardar, según el rol
 * con el que quedó registrado el usuario. Rutas absolutas para no depender
 * de la URL desde la que se envió el formulario.
 * @param int $idRolElegido
 * @return string
 */
function listadoTrasGuardar($idRolElegido)
{
    global $idRolTutor, $idRolEstudiante;
    if ($idRolTutor !== null && (int)$idRolElegido === $idRolTutor) {
        return '/controllers/tutores_listar.php';
    }
    if ($idRolEstudiante !== null && (int)$idRolElegido === $idRolEstudiante) {
        return '/controllers/estudiantes_listar.php';
    }
    return '/controllers/usuarios_listar.php';
}

// ===== Procesamiento del formulario (POST) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Protección CSRF: la solicitud debe traer el token de la sesión
    if (!verificarTokenCsrf()) {
        setMensaje('danger', 'La solicitud expiró. Vuelve a intentarlo.');
        redirigir('/controllers/usuarios_crear.php');
    }

    // Se capturan y normalizan los datos recibidos
    $datos = [
        'id_rol'   => $_POST['id_rol'] ?? '',
        'nombre'   => limpiarTexto($_POST['nombre'] ?? ''),
        'apellido' => limpiarTexto($_POST['apellido'] ?? ''),
        'correo'   => limpiarTexto($_POST['correo'] ?? ''),
        'usuario'  => limpiarTexto($_POST['usuario'] ?? ''),
        'telefono' => limpiarTexto($_POST['telefono'] ?? ''),
        'clave'    => $_POST['clave'] ?? '',
        'id_carrera' => $_POST['id_carrera'] ?? '',
        'semestre'   => $_POST['semestre'] ?? '1',
    ];

    // Roles existentes para validar que el id_rol enviado sea legítimo
    $idsRoles = array_column($roles, 'id_rol');
    $esEstudiante = ($idRolEstudiante !== null && (int)$datos['id_rol'] === $idRolEstudiante);

    // ---- Validaciones ----
    // 1. Campos obligatorios
    if (in_array('', [$datos['nombre'], $datos['apellido'], $datos['correo'], $datos['usuario'], $datos['clave'], $datos['id_rol']], true)) {
        $errores[] = "Todos los campos marcados con * son obligatorios.";
    }
    // 1b. Si el rol es estudiante, la carrera es obligatoria y debe existir
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
    // 2. El rol debe existir en la BD
    if ($datos['id_rol'] !== '' && !in_array((int)$datos['id_rol'], $idsRoles, true)) {
        $errores[] = "El rol seleccionado no es válido.";
    }
    // 3. Nombres solo letras, mínimo 2 caracteres
    if (!empty($datos['nombre']) && (mb_strlen($datos['nombre']) < 2 || !validarLetras($datos['nombre']))) {
        $errores[] = "El nombre debe tener al menos 2 caracteres y solo puede contener letras.";
    }
    if (!empty($datos['apellido']) && (mb_strlen($datos['apellido']) < 2 || !validarLetras($datos['apellido']))) {
        $errores[] = "El apellido debe tener al menos 2 caracteres y solo puede contener letras.";
    }
    // 4. Correo válido y usuario alfanumérico
    if (!empty($datos['correo']) && !filter_var($datos['correo'], FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo no tiene un formato válido.";
    }
    if (!empty($datos['usuario']) && (mb_strlen($datos['usuario']) < 3 || !preg_match('/^[a-zA-Z0-9_.]+$/', $datos['usuario']))) {
        $errores[] = "El usuario debe tener al menos 3 caracteres y solo letras, números, punto o guión bajo.";
    }
    // 5. Teléfono opcional con formato aceptable
    if (!empty($datos['telefono']) && !preg_match('/^[0-9+\s()\-]{7,20}$/', $datos['telefono'])) {
        $errores[] = "El teléfono no es válido. Usa solo números, espacios o el símbolo +.";
    }
    // 6. Contraseña: mínimo 8 caracteres con letras y números
    if (!empty($datos['clave'])) {
        if (strlen($datos['clave']) < 8) {
            $errores[] = "La contraseña debe tener al menos 8 caracteres.";
        }
        if (!preg_match('/[A-Za-z]/', $datos['clave']) || !preg_match('/[0-9]/', $datos['clave'])) {
            $errores[] = "La contraseña debe combinar letras y números.";
        }
    }

    // Si las validaciones pasan, se intenta crear el usuario
    if (empty($errores)) {
        try {
            $usuarioModel->crear($datos);
            $idNuevo = (int)$pdo->lastInsertId();

            // Si es un estudiante se crea/actualiza su ficha académica con la carrera elegida
            if ($esEstudiante) {
                $estudianteModel->guardarOActualizar(
                    $idNuevo,
                    (int)$datos['id_carrera'],
                    (int)$datos['semestre'],
                    'RU-' . date('y') . mt_rand(10000, 99999)
                );
            }

            // Si es un tutor se crea su ficha docente. Sin ella el usuario
            // queda guardado pero NO aparece en el listado de tutores.
            $esTutor = ($idRolTutor !== null && (int)$datos['id_rol'] === $idRolTutor);
            if ($esTutor) {
                $tutorModel->crearFicha($idNuevo);
            }

            setMensaje('success', 'Usuario registrado correctamente.');
            redirigir(listadoTrasGuardar($datos['id_rol']));
        } catch (PDOException $e) {
            // Excepción por las restricciones UNIQUE de correo o usuario
            $errores[] = "No se pudo registrar: el correo o el usuario ya existen en el sistema.";
        }
    }
} else {
    // Petición GET: los roles y carreras ya se cargaron al inicio
}

require_once __DIR__ . '/../views/usuarios/crear.php';