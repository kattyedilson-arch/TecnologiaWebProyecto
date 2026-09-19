<?php
// =========================================================
// CONTROLADOR: PROCESAR LOGIN (login_procesar.php)
// ---------------------------------------------------------
// Recibe el POST del formulario de login. Verifica el usuario
// (por nombre de usuario o correo), su estado y su contraseña
// (password_verify contra el hash bcrypt). Si es correcto,
// guarda la sesión y redirige según el rol del usuario.
// También registra cada intento en la tabla 'registro_accesos'.
// =========================================================
session_start();
require_once '../config/conexion.php';
require_once '../includes/funciones.php';
require_once '../models/UsuarioModel.php';

// Si la petición no es POST (alguien entró directo por la URL) se regresa al login
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login/login.php');
    exit;
}

// Validación del token CSRF incluido en el formulario de login
if (!verificarTokenCsrf()) {
    $_SESSION['login_error'] = 'La solicitud expiró. Vuelve a intentar iniciar sesión.';
    header('Location: ../views/login/login.php');
    exit;
}

$usuarioInput = trim($_POST['usuario'] ?? '');        // Usuario o correo digitado
$contrasenaInput = $_POST['contrasena'] ?? '';        // Contraseña digitada

// Límite de intentos fallidos (anti fuerza bruta): máx. 10 fallos cada 15 minutos por IP
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$stmtIntentos = $pdo->prepare("SELECT COUNT(*) AS total FROM registro_accesos
                               WHERE ip_origen = :ip AND resultado = 'fallido'
                                 AND fecha_hora > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
$stmtIntentos->execute([':ip' => $ip]);
if ((int)$stmtIntentos->fetch()['total'] >= 10) {
    $_SESSION['login_error'] = 'Demasiados intentos fallidos. Espera 15 minutos e inténtalo de nuevo.';
    header('Location: ../views/login/login.php');
    exit;
}

$modelo = new UsuarioModel($pdo);
// Se busca la cuenta por usuario O por correo (incluye el rol)
$usuario = $modelo->obtenerPorUsuario($usuarioInput);

if ($usuario && $usuario['estado'] === 'activo' && password_verify($contrasenaInput, $usuario['contrasena_hash'])) {
    // ===== Login correcto: se construye la sesión =====
    // Nuevo ID de sesión para evitar fijación de sesión (session fixation)
    session_regenerate_id(true);
    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['rol'] = $usuario['nombre_rol'];

    // Registrar acceso exitoso (auditoría: usuario, IP y resultado)
    $pdo->prepare("INSERT INTO registro_accesos (id_usuario, ip_origen, resultado) VALUES (?, ?, 'exitoso')")
        ->execute([$usuario['id_usuario'], $_SERVER['REMOTE_ADDR']]);

    // Redirección según el rol de la cuenta
    switch ($usuario['nombre_rol']) {
        case 'administrador':
            header('Location: ../controllers/dashboard.php');   // Admin -> panel central
            break;
        case 'tutor':
            header('Location: ../views/tutor/panel.php');       // Tutor -> su panel
            break;
        case 'estudiante':
            header('Location: ../views/estudiante/panel.php');  // Estudiante -> su panel
            break;
        default:
            header('Location: ../views/login/login.php');       // Rol desconocido -> login
    }
    exit;

} else {
    // ===== Login fallido =====
    // Registrar el intento fallido si el usuario al menos existía
    if ($usuario) {
        $pdo->prepare("INSERT INTO registro_accesos (id_usuario, ip_origen, resultado) VALUES (?, ?, 'fallido')")
            ->execute([$usuario['id_usuario'], $_SERVER['REMOTE_ADDR']]);
    }
    // Mensaje genérico (no revela si falló el usuario o la contraseña)
    $_SESSION['login_error'] = 'Usuario o contraseña incorrectos, o cuenta inactiva.';
    header('Location: ../views/login/login.php');
    exit;
}