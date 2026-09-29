<?php
// =========================================================
// CONTROLADOR: LISTAR ESTUDIANTES (estudiantes_listar.php)
// ---------------------------------------------------------
// Lista estudiantes con ficha académica (carrera, semestre, RU)
// con búsqueda (q) y paginación server-side (lista_helper.php).
// Solo estudiantes con rol 'estudiante' real.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/lista_helper.php';
require_once __DIR__ . '/../config/conexion.php';

requerirRol('administrador');

$p = parametrosLista();

// Búsqueda por nombre, apellido, usuario, R.U., carrera o correo.
// 'u.usuario' se incluye porque la tabla muestra ese dato en cada fila:
// buscar por él es lo esperable aunque no esté en el placeholder.
$busqueda = condicionBusqueda(
    ['u.nombre', 'u.apellido', 'u.usuario', 'e.registro_universitario', 'c.nombre_carrera', 'u.correo'],
    $p['q']
);
$filtros = ["r.nombre_rol = 'estudiante'"];
$where = whereLista($busqueda['condicion'], $filtros);
$params = $busqueda['params'];

// Columnas ordenables permitidas
$ordenes = [
    'id'   => 'e.id_estudiante',
    'nombre' => 'u.nombre',
    'ru'   => 'e.registro_universitario',
    'carrera' => 'c.nombre_carrera',
    'semestre' => 'e.semestre',
];
$colSql = $ordenes[$p['col']] ?? 'u.nombre';
$orderSql = "ORDER BY " . $colSql . " " . $p['dir'] . ", u.nombre ASC";

$sqlConteo = "SELECT COUNT(*)
              FROM estudiantes e
              INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
              INNER JOIN roles r ON u.id_rol = r.id_rol
              INNER JOIN carreras c ON e.id_carrera = c.id_carrera" . $where;
$sqlDatos = "SELECT e.id_estudiante, e.id_usuario, e.id_carrera, e.semestre, e.registro_universitario,
                    u.nombre, u.apellido, u.correo, u.telefono, u.usuario, u.estado, u.foto_perfil,
                    c.nombre_carrera,
                    (SELECT COUNT(*) FROM tutorias t WHERE t.id_estudiante = e.id_estudiante) AS total_tutorias
             FROM estudiantes e
             INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
             INNER JOIN roles r ON u.id_rol = r.id_rol
             INNER JOIN carreras c ON e.id_carrera = c.id_carrera" . $where . ' ' . $orderSql;

$resultado = paginarConsulta($pdo, $sqlConteo, $sqlDatos, $params, $p['pagina'], $p['por_pagina']);
$estudiantes = $resultado['filas'];

// Métricas globales (sin paginar)
$metrica = $pdo->query("SELECT
                            (SELECT COUNT(*) FROM estudiantes e
                             INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                             INNER JOIN roles r ON u.id_rol = r.id_rol
                             WHERE r.nombre_rol = 'estudiante') AS total_estudiantes,
                            (SELECT COUNT(*) FROM estudiantes e
                             INNER JOIN usuarios u ON e.id_usuario = u.id_usuario
                             WHERE u.estado = 'activo') AS activos,
                            (SELECT COUNT(*) FROM tutorias) AS total_tutorias")->fetch();
$totalEstudiantes = (int)$metrica['total_estudiantes'];
$totalActivos     = (int)$metrica['activos'];
$totalTutorias    = (int)$metrica['total_tutorias'];

require_once __DIR__ . '/../views/estudiantes/listar.php';