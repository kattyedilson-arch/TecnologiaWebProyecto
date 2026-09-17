<?php

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/EstudianteModel.php';

$model = new EstudianteModel($pdo);

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nombre = trim($_POST['nombre'] ?? '');
    $apellido = trim($_POST['apellido'] ?? '');
    $correo = trim($_POST['correo'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';
    $idCarrera = $_POST['id_carrera'] ?? '';
    $semestre = $_POST['semestre'] ?? '';

    if (strlen($nombre) < 2) {
        $errores[] = "Nombre inválido.";
    }

    if (strlen($apellido) < 2) {
        $errores[] = "Apellido inválido.";
    }

    if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "Correo inválido.";
    }

    if (strlen($clave) < 6) {
        $errores[] = "La contraseña debe tener al menos 6 caracteres.";
    }

    if ($semestre < 1 || $semestre > 12) {
        $errores[] = "Semestre inválido.";
    }

    if (empty($errores)) {

        try {

            $pdo->beginTransaction();

            $hash = password_hash(
                $clave,
                PASSWORD_DEFAULT
            );

            $stmt = $pdo->prepare(
                "INSERT INTO usuarios
                (
                    id_rol,
                    nombre,
                    apellido,
                    correo,
                    usuario,
                    contrasena_hash
                )
                VALUES
                (
                    3,
                    :nombre,
                    :apellido,
                    :correo,
                    :usuario,
                    :hash
                )"
            );

            $stmt->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':correo' => $correo,
                ':usuario' => $usuario,
                ':hash' => $hash
            ]);

            $idUsuario = $pdo->lastInsertId();

            $ru = $model->generarRU();

            $stmt = $pdo->prepare(
                "INSERT INTO estudiantes
                (
                    id_usuario,
                    id_carrera,
                    semestre,
                    registro_universitario
                )
                VALUES
                (
                    :id_usuario,
                    :id_carrera,
                    :semestre,
                    :ru
                )"
            );

            $stmt->execute([
                ':id_usuario' => $idUsuario,
                ':id_carrera' => $idCarrera,
                ':semestre' => $semestre,
                ':ru' => $ru
            ]);

            $pdo->commit();

            header("Location: estudiantes_listar.php");
            exit;

        } catch (PDOException $e) {

            $pdo->rollBack();

            $errores[] =
                "No se pudo registrar el estudiante.";

        }
    }
}

$carreras = $model->obtenerCarreras();

require_once __DIR__ . '/../views/estudiantes/crear.php';