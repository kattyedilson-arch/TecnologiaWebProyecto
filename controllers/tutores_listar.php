<?php
// =========================================================
// CONTROLADOR: LISTAR TUTORES (tutores_listar.php)
// ---------------------------------------------------------
// Lista docentes tutores con búsqueda (q) y paginación
// server-side (lista_helper.php). Las reseñas se cargan en una
// sola consulta y se agrupan en PHP para evitar N+1.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/lista_helper.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/TutorModel.php';

requerirRol('administrador');

$tutorModel = new TutorModel($pdo);

$p = parametrosLista();

// Búsqueda por nombre, apellido, especialidad o correo
$busqueda = condicionBusqueda(['u.nombre', 'u.apellido', 't.especialidad', 'u.correo'], $p['q']);
$where = whereLista($busqueda['condicion']);
$params = $busqueda['params'];

// Columnas ordenables permitidas
$ordenes = [
    'id'     => 't.id_tutor',
    'nombre' => 'u.nombre',
];
$colSql = $ordenes[$p['col']] ?? 'u.nombre';
$orderSql = "ORDER BY " . $colSql . " " . $p['dir'] . ", u.nombre ASC";

$sqlConteo = "SELECT COUNT(*)
              FROM tutores t
              INNER JOIN usuarios u ON t.id_usuario = u.id_usuario" . $where;
$sqlDatos = "SELECT t.id_tutor, t.id_usuario, t.especialidad, t.biografia,
                    u.nombre, u.apellido, u.correo, u.telefono, u.usuario, u.estado, u.foto_perfil,
                    (SELECT COUNT(*) FROM tutor_materia tm WHERE tm.id_tutor = t.id_tutor) AS total_materias,
                    (SELECT COUNT(*) FROM disponibilidad_tutor dt WHERE dt.id_tutor = t.id_tutor) AS total_horarios
             FROM tutores t
             INNER JOIN usuarios u ON t.id_usuario = u.id_usuario" . $where . ' ' . $orderSql;

$resultado = paginarConsulta($pdo, $sqlConteo, $sqlDatos, $params, $p['pagina'], $p['por_pagina']);
$tutores = $resultado['filas'];

// Métricas globales (sin paginar)
$metrica = $pdo->query("SELECT
                            (SELECT COUNT(*) FROM tutores) AS total_tutores,
                            (SELECT COUNT(*) FROM tutor_materia) AS total_materias,
                            (SELECT COUNT(*) FROM disponibilidad_tutor) AS total_horarios")->fetch();
$totalTutores  = (int)$metrica['total_tutores'];
$totalMaterias = (int)$metrica['total_materias'];
$totalHorarios = (int)$metrica['total_horarios'];

// Reseñas de todos los tutores en una sola consulta (evita N+1)
$resenasPorTutor = [];
foreach ($tutorModel->obtenerResenasDeTodos() as $resena) {
    $resenasPorTutor[$resena['id_tutor']][] = $resena;
}

require_once __DIR__ . '/../views/tutores/listar.php';