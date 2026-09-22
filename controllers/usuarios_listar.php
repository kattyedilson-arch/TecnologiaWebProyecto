<?php
// =========================================================
// CONTROLADOR: LISTAR USUARIOS (usuarios_listar.php)
// ---------------------------------------------------------
// Carga todos los usuarios del sistema y calcula métricas
// rápidas (total, activos, por rol) para la cabecera de la
// vista, y pasa todo a views/usuarios/listar.php.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

requerirRol('administrador');

$usuarioModel = new UsuarioModel($pdo);
$usuarios = $usuarioModel->obtenerTodos();

// Métricas rápidas para la cabecera del listado
$totalUsuarios = count($usuarios);
$totalActivos = count(array_filter($usuarios, fn($u) => $u['estado'] === 'activo'));
$totalTutores = count(array_filter($usuarios, fn($u) => $u['nombre_rol'] === 'tutor'));
$totalEstud   = count(array_filter($usuarios, fn($u) => $u['nombre_rol'] === 'estudiante'));

require_once __DIR__ . '/../views/usuarios/listar.php';