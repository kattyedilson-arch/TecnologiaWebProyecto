<?php
// =========================================================
// CONTROLADOR: LISTAR MATERIAS (materias_listar.php)
// ---------------------------------------------------------
// Carga el catálogo completo de materias y lo envía a la vista
// de listado.
// =========================================================
require_once __DIR__ . '/../includes/verificar_sesion.php';
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/../models/MateriaModel.php';

requerirRol('administrador');

$materiaModel = new MateriaModel($pdo);

// Orden server-side. La columna y la dirección se validan contra la lista
// blanca del modelo; nunca se interpola el valor crudo del GET.
// Por defecto se ordena por ID ascendente.
$col = isset($_GET['col']) && array_key_exists($_GET['col'], MateriaModel::columnasOrden())
    ? (string)$_GET['col']
    : 'id';
$dir = (isset($_GET['dir']) && strtoupper((string)$_GET['dir']) === 'DESC') ? 'DESC' : 'ASC';

$materias = $materiaModel->obtenerTodas($col, $dir);

// Datos para las cabeceras ordenables de la vista
$columnasOrden = MateriaModel::columnasOrden();
$titulosOrden = ['id' => 'ID', 'nombre' => 'Nombre de la Materia', 'carrera' => 'Carrera Universitaria'];

/** Construye la URL del listado alternando el orden de una columna. */
function enlaceOrden($colActual, $dirActual, $col, $titulos)
{
    $nuevaDir = ($col === $colActual && $dirActual === 'ASC') ? 'DESC' : 'ASC';
    $flecha = '';
    if ($col === $colActual) {
        $flecha = $dirActual === 'ASC'
            ? ' <i class="bi bi-caret-up-fill small"></i>'
            : ' <i class="bi bi-caret-down-fill small"></i>';
    }
    return '<a href="?col=' . urlencode($col) . '&dir=' . $nuevaDir . '"'
        . ' class="text-decoration-none text-reset"'
        . ' title="Ordenar por ' . htmlspecialchars($titulos[$col] ?? $col) . '">'
        . htmlspecialchars($titulos[$col] ?? $col) . $flecha . '</a>';
}

require_once __DIR__ . '/../views/materias/listar.php';