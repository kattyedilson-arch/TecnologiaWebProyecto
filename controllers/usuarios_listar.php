<?php
// =========================================================
// CONTROLADOR: LISTAR USUARIOS (usuarios_listar.php)
// ---------------------------------------------------------
// Lista usuarios con búsqueda (q), filtro por rol (rol) y
// paginación/ordenamiento server-side (lista_helper.php).
// Además calcula métricas globales para la cabecera de la vista.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/lista_helper.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

requerirRol('administrador');

$p = parametrosLista();

// Filtro por rol (lista blanca)
$rolesPermitidos = ['administrador', 'tutor', 'estudiante', 'coordinador_mg', 'auxiliar_mg'];
$rolFiltro = $_GET['rol'] ?? '';
if (!in_array($rolFiltro, $rolesPermitidos, true)) {
    $rolFiltro = '';
}

// Búsqueda por nombre, apellido, correo, usuario o rol
$busqueda = condicionBusqueda(['u.nombre', 'u.apellido', 'u.correo', 'u.usuario', 'r.nombre_rol'], $p['q']);
$filtros = [];
if ($rolFiltro !== '') {
    $filtros[] = 'r.nombre_rol = :rol';
}
$where = whereLista($busqueda['condicion'], $filtros);
$params = $busqueda['params'];
if ($rolFiltro !== '') {
    $params[':rol'] = $rolFiltro;
}

// Columnas ordenables permitidas (nunca se usa un valor libre del GET)
$ordenes = [
    'id'     => 'u.id_usuario',
    'nombre' => 'u.nombre',
    'rol'    => 'r.nombre_rol',
    'estado' => 'u.estado',
];
$colSql = $ordenes[$p['col']] ?? 'u.id_usuario';
$orderSql = "ORDER BY " . $colSql . " " . $p['dir'] . ", u.id_usuario DESC";

$sqlConteo = "SELECT COUNT(*)
              FROM usuarios u
              INNER JOIN roles r ON u.id_rol = r.id_rol" . $where;
$sqlDatos = "SELECT u.id_usuario, u.nombre, u.apellido, u.correo, u.usuario,
                    u.foto_perfil, u.telefono, r.nombre_rol, u.estado, u.fecha_registro
             FROM usuarios u
             INNER JOIN roles r ON u.id_rol = r.id_rol" . $where . ' ' . $orderSql;

$resultado = paginarConsulta($pdo, $sqlConteo, $sqlDatos, $params, $p['pagina'], $p['por_pagina']);
$usuarios = $resultado['filas'];

// Métricas globales (sin paginar) para la cabecera
$metrica = $pdo->query("SELECT
                            (SELECT COUNT(*) FROM usuarios) AS total,
                            (SELECT COUNT(*) FROM usuarios WHERE estado = 'activo') AS activos,
                            (SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON u.id_rol = r.id_rol WHERE r.nombre_rol = 'tutor') AS tutores,
                            (SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON u.id_rol = r.id_rol WHERE r.nombre_rol = 'estudiante') AS estudiantes,
                            (SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON u.id_rol = r.id_rol WHERE r.nombre_rol = 'coordinador_mg') AS coordinadores,
                            (SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON u.id_rol = r.id_rol WHERE r.nombre_rol = 'auxiliar_mg') AS auxiliares")->fetch();
$totalUsuarios = (int)$metrica['total'];
$totalActivos  = (int)$metrica['activos'];
$totalTutores  = (int)$metrica['tutores'];
$totalEstud    = (int)$metrica['estudiantes'];
$totalCoord    = (int)$metrica['coordinadores'];
$totalAux      = (int)$metrica['auxiliares'];

require_once __DIR__ . '/../views/usuarios/listar.php';