<?php
// =========================================================
// HELPER DE LISTADOS (includes/lista_helper.php)
// ---------------------------------------------------------
// Paginación, ordenamiento y búsqueda SERVIDOR-side,
// reutilizables por todos los listados (tutorías, usuarios,
// tutores, expedientes MG, reportes...).
//
// Funciones:
//   parametrosLista()        -> lee página/búsqueda/orden de $_GET
//   paginarConsulta($pdo, $sqlConteo, $sqlDatos, $params)
//                            -> ejecuta conteo + datos con LIMIT/OFFSET
//   renderPaginacion($resultado)
//                            -> HTML de paginación con enlaces que
//                               conservan q/orden/dir/filtros
//   renderBuscador($placeholder)
//                            -> HTML del campo de búsqueda
// =========================================================

/**
 * Lee los parámetros de listado desde la query string, sanearizados.
 * @return array ['q','col','dir','pagina','por_pagina','signo','flip']
 */
function parametrosLista()
{
    $q = trim((string)($_GET['q'] ?? ''));
    if ($q !== '') {
        $q = mb_substr($q, 0, 100);
    }
    $col = trim((string)($_GET['col'] ?? ''));
    $col = preg_replace('/[^a-zA-Z0-9_\.]/', '', $col);
    $dir = ($_GET['dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
    $pagina = max(1, (int)($_GET['pagina'] ?? 1));
    $porPagina = (int)($_GET['por_pagina'] ?? 15);
    if (!in_array($porPagina, [10, 15, 25, 50], true)) {
        $porPagina = 15;
    }
    return [
        'q' => $q,
        'col' => $col,
        'dir' => $dir,
        'pagina' => $pagina,
        'por_pagina' => $porPagina,
        'signo' => $dir === 'asc' ? '>' : '<',
        'flip' => $dir === 'asc' ? 'desc' : 'asc',
    ];
}

/**
 * Genera la condición SQL de búsqueda con LIKE sobre columnas dadas.
 * @param array  $columnas Columnas permitidas (con alias, p. ej. 'u.nombre')
 * @param string $q        Término de búsqueda
 * @return array ['condicion' => string, 'params' => [':busqueda' => string]]
 */
function condicionBusqueda(array $columnas, $q)
{
    if ($q === '' || empty($columnas)) {
        return ['condicion' => '', 'params' => []];
    }
    $trozos = [];
    foreach ($columnas as $col) {
        $trozos[] = trim($col) . " LIKE :busqueda";
    }
    return [
        'condicion' => '(' . implode(' OR ', $trozos) . ')',
        'params' => [':busqueda' => '%' . $q . '%'],
    ];
}

/**
 * Combina una condición de búsqueda con filtros adicionales (WHERE ...).
 * @param string $condicionBusqueda Condición LIKE ya generada
 * @param string|array $filtros     Filtros SQL adicionales (string con claves
 *                                  :filtroN o array de strings)
 * @return string Parte WHERE completa (con la palabra WHERE o vacío)
 */
function whereLista($condicionBusqueda, $filtros = [])
{
    $partes = [];
    if ($condicionBusqueda !== '') {
        $partes[] = $condicionBusqueda;
    }
    if (is_string($filtros) && trim($filtros) !== '') {
        $partes[] = $filtros;
    } elseif (is_array($filtros)) {
        foreach ($filtros as $f) {
            if (trim($f) !== '') {
                $partes[] = $f;
            }
        }
    }
    if (empty($partes)) {
        return '';
    }
    return ' WHERE ' . implode(' AND ', $partes);
}

/**
 * Ejecuta una consulta paginada.
 * @param PDO    $pdo        Conexión PDO
 * @param string $sqlConteo  SELECT COUNT(*) ... (sin LIMIT)
 * @param string $sqlDatos   SELECT ... (sin LIMIT; crn cuenta con la fila de sort ya incluida)
 * @param array  $params     Parámetros con nombre compartidos por ambas consultas
 * @param int    $pagina     Página actual (>= 1)
 * @param int    $porPagina  Filas por página (10/15/25/50)
 * @return array ['filas','total','total_paginas','pagina','por_pagina','offset']
 */
function paginarConsulta($pdo, $sqlConteo, $sqlDatos, array $params, $pagina = 1, $porPagina = 15)
{
    $pagina = max(1, (int)$pagina);
    if (!in_array((int)$porPagina, [10, 15, 25, 50], true)) {
        $porPagina = 15;
    } else {
        $porPagina = (int)$porPagina;
    }

    $stmtC = $pdo->prepare($sqlConteo);
    foreach ($params as $clave => $valor) {
        $stmtC->bindValue($clave, $valor);
    }
    $stmtC->execute();
    $total = (int)$stmtC->fetchColumn();

    $totalPaginas = max(1, (int)ceil($total / $porPagina));
    $pagina = min($pagina, $totalPaginas);
    $offset = ($pagina - 1) * $porPagina;

    $stmtD = $pdo->prepare($sqlDatos . ' LIMIT ' . $porPagina . ' OFFSET ' . $offset);
    foreach ($params as $clave => $valor) {
        $stmtD->bindValue($clave, $valor);
    }
    $stmtD->execute();

    return [
        'filas' => $stmtD->fetchAll(),
        'total' => $total,
        'pagina' => $pagina,
        'por_pagina' => $porPagina,
        'total_paginas' => $totalPaginas,
        'offset' => $offset,
    ];
}

/**
 * Construye una URL de listado conservando q/col/dir/filtros y cambiando un
 * solo parámetro. $sobrescribe = ['col'=>'nombre','dir'=>'asc','pagina'=>2].
 */
function enlaceLista(array $sobrescribe = [])
{
    $consulta = $_GET;
    foreach ($sobrescribe as $clave => $valor) {
        if ($valor === '' || $valor === null) {
            unset($consulta[$clave]);
        } else {
            $consulta[$clave] = $valor;
        }
    }
    return '?' . http_build_query($consulta);
}

/**
 * HTML de paginación Bootstrap (botones anterior/página/números/siguiente).
 * @param array $resultado Resultado de paginarConsulta()
 */
function renderPaginacion(array $resultado)
{
    $totalPaginas = $resultado['total_paginas'];
    $pagina = $resultado['pagina'];
    if ($totalPaginas <= 1) {
        return '';
    }
    $html = '<nav class="d-flex justify-content-between align-items-center flex-wrap gap-2 pt-3">';
    $html .= '<small class="text-muted">Página ' . $pagina . ' de ' . $totalPaginas
          . ' · ' . $resultado['total'] . ' registro(s)</small>';
    $html .= '<ul class="pagination pagination-sm mb-0">';

    $html .= '<li class="page-item ' . ($pagina <= 1 ? 'disabled' : '') . '">'
          . '<a class="page-link" href="' . e(enlaceLista(['pagina' => $pagina - 1])) . '"><i class="bi bi-chevron-left"></i></a></li>';

    $primero = max(1, $pagina - 2);
    $ultimo = min($totalPaginas, $pagina + 2);
    for ($i = $primero; $i <= $ultimo; $i++) {
        $activa = $i === $pagina ? ' active' : '';
        $html .= '<li class="page-item' . $activa . '"><a class="page-link" href="' . e(enlaceLista(['pagina' => $i])) . '">' . $i . '</a></li>';
    }

    $html .= '<li class="page-item ' . ($pagina >= $totalPaginas ? 'disabled' : '') . '">'
          . '<a class="page-link" href="' . e(enlaceLista(['pagina' => $pagina + 1])) . '"><i class="bi bi-chevron-right"></i></a></li>';
    $html .= '</ul></nav>';
    return $html;
}

/**
 * HTML del campo de búsqueda (envía q y limpia filtros no deseados).
 * @param string $placeholder Texto de ayuda del campo
 */
function renderBuscador($placeholder = 'Buscar...')
{
    $q = e($_GET['q'] ?? '');
    $form = '<form method="get" class="input-group" style="max-width: 340px;" role="search">';
    foreach ($_GET as $clave => $valor) {
        if ($clave === 'q' || $clave === 'pagina') {
            continue;
        }
        if (is_array($valor)) {
            continue;
        }
        $form .= '<input type="hidden" name="' . e($clave) . '" value="' . e($valor) . '">';
    }
    $form .= '<span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>';
    $form .= '<input type="text" name="q" value="' . $q . '" class="form-control bg-light border-start-0" placeholder="' . e($placeholder) . '">';
    $form .= '<button class="btn btn-outline-secondary" type="submit" title="Buscar"><i class="bi bi-arrow-right"></i></button>';
    if ($q !== '') {
        $form .= '<a class="btn btn-outline-secondary" href="' . e(enlaceLista(['q' => ''])) . '" title="Limpiar"><i class="bi bi-x-lg"></i></a>';
    }
    $form .= '</form>';
    return $form;
}

/**
 * Devuelve true si la columna dada es la columna de orden actual (para
 * resaltar el encabezado). Parámetro de conveniencia para las vistas.
 */
function columnaOrdenadaActiva($col, $colActual)
{
    return $col === $colActual;
}