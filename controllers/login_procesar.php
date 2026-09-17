<?php

session_start();

require_once '../config/conexion.php';
require_once '../models/UsuarioModel.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../views/login/login.php');
    exit;
}

$usuarioInput = trim($_POST['usuario'] ?? '');
$contrasenaInput = $_POST['contrasena'] ?? '';

$modelo = new UsuarioModel($pdo);

$usuario = $modelo->obtenerPorUsuario($usuarioInput);

if (
    $usuario &&
    $usuario['estado'] === 'activo' &&
    password_verify(
        $contrasenaInput,
        $usuario['contrasena_hash']
    )
) {

    $_SESSION['id_usuario'] = $usuario['id_usuario'];
    $_SESSION['nombre'] = $usuario['nombre'];
    $_SESSION['rol'] = $usuario['nombre_rol'];
    $_SESSION['id_rol'] = $usuario['id_rol'];

    $pdo->prepare(
        "INSERT INTO registro_accesos
        (
            id_usuario,
            ip_origen,
            resultado
        )
        VALUES
        (
            ?,
            ?,
            'exitoso'
        )"
    )->execute([
        $usuario['id_usuario'],
        $_SERVER['REMOTE_ADDR']
    ]);

    header('Location: ../index.php');
    exit;

} else {

    if ($usuario) {

        $pdo->prepare(
            "INSERT INTO registro_accesos
            (
                id_usuario,
                ip_origen,
                resultado
            )
            VALUES
            (
                ?,
                ?,
                'fallido'
            )"
        )->execute([
            $usuario['id_usuario'],
            $_SERVER['REMOTE_ADDR']
        ]);
    }

    $_SESSION['login_error'] =
        'Usuario o contraseña incorrectos o cuenta inactiva.';

    header('Location: ../views/login/login.php');
    exit;
}