<?php
// =========================================================
// VISTA: LISTADO DE EXPEDIENTES MG (views/mg/expedientes.php)
// ---------------------------------------------------------
// HU-024. Tabla paginada de expedientes con búsqueda y filtros.
// Variables: $resultado, $resumen, $cohortes, $modalidades
// (controllers/mg_expedientes.php)
// =========================================================
require_once __DIR__ . '/../layouts/header.php';

$colActual = $_GET['col'] ?? 'fecha';
$dirActual = $_GET['dir'] ?? 'desc';
$puedeGestionar = tienePermiso('gestionar_expedientes_mg');
$q = e($_GET['q'] ?? '');

$etiquetasEtapa = ['previa' => 'Previa', 'mg1' => 'MG1', 'mg2' => 'MG2', 'finalizado' => 'Finalizado'];
$etiquetasEstado = [
    'borrador' => 'Borrador', 'enviada' => 'Enviada', 'en_revision' => 'En revisión',
    'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada', 'cancelada' => 'Cancelada',
    'reprobado' => 'Reprobado', 'abandono' => 'Abandono',
];
$clasesEstado = [
    'borrador' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle',
    'enviada' => 'bg-primary bg-opacity-10 text-primary border border-primary-subtle',
    'en_revision' => 'bg-warning bg-opacity-10 text-warning border border-warning-subtle',
    'aprobada' => 'bg-success bg-opacity-10 text-success border border-success-subtle',
    'rechazada' => 'bg-danger bg-opacity-10 text-danger border border-danger-subtle',
    'cancelada' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle',
    'reprobado' => 'bg-danger bg-opacity-10 text-danger border border-danger-subtle',
    'abandono' => 'bg-dark bg-opacity-10 text-dark border border-dark-subtle',
];
$clasesEtapa = [
    'previa' => 'bg-light text-dark border',
    'mg1' => 'bg-info bg-opacity-10 text-info border border-info-subtle',
    'mg2' => 'bg-primary bg-opacity-10 text-primary border border-primary-subtle',
    'finalizado' => 'bg-success bg-opacity-10 text-success border border-success-subtle',
];
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="h5 fw-bold mb-1">Expedientes de Modalidades de Grado</h2>
    <p class="text-muted small mb-0">
      Área de trabajo del coordinador MG: consulta y avance de expedientes (HU-024).
    </p>
  </div>
  <div class="d-flex gap-2">
    <?php if ($puedeGestionar): ?>
      <a class="btn btn-light border" href="/controllers/mg_importar.php"><i class="bi bi-upload me-1"></i>Importar padrón</a>
    <?php endif; ?>
  </div>
</div>

<!-- Resumen -->
<div class="row g-2 mb-4">
  <?php foreach (['previa' => 'Etapa previa', 'mg1' => 'En MG1', 'mg2' => 'En MG2'] as $etapa => $texto): ?>
    <div class="col-6 col-lg-2">
      <div class="card card-custom p-3 text-center">
        <div class="h5 fw-bold mb-0 text-primary"><?= (int)($resumen['etapas'][$etapa] ?? 0) ?></div>
        <div class="small text-muted"><?= $texto ?></div>
      </div>
    </div>
  <?php endforeach; ?>
  <div class="col-6 col-lg-2">
    <div class="card card-custom p-3 text-center">
      <div class="h5 fw-bold mb-0 text-danger"><?= (int)($resumen['estados']['reprobado'] ?? 0) ?></div>
      <div class="small text-muted">Reprobados</div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card card-custom p-3 text-center">
      <div class="h5 fw-bold mb-0 text-dark"><?= (int)($resumen['estados']['abandono'] ?? 0) ?></div>
      <div class="small text-muted">Abandonos</div>
    </div>
  </div>
  <div class="col-6 col-lg-2">
    <div class="card card-custom p-3 text-center">
      <div class="h5 fw-bold mb-0"><?= (int)$resumen['total'] ?></div>
      <div class="small text-muted">Total</div>
    </div>
  </div>
</div>

<div class="card card-custom p-3 mb-3">
  <form method="GET" action="/controllers/mg_expedientes.php" class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Buscar</label>
      <div class="input-group">
        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
        <input type="text" name="q" value="<?= $q ?>" class="form-control bg-light border-start-0" placeholder="Estudiante, registro o correo...">
      </div>
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold">Cohorte</label>
      <select name="id_cohorte" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($cohortes as $c): ?>
          <option value="<?= (int)$c['id_cohorte'] ?>" <?= (int)$_GET['id_cohorte'] === (int)$c['id_cohorte'] ? 'selected' : '' ?>><?= e($c['codigo']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Modalidad</label>
      <select name="id_modalidad" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($modalidades as $mo): ?>
          <option value="<?= (int)$mo['id_modalidad'] ?>" <?= (int)$_GET['id_modalidad'] === (int)$mo['id_modalidad'] ? 'selected' : '' ?>><?= e($mo['codigo']) ?> — <?= e($mo['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold">Etapa</label>
      <select name="etapa_actual" class="form-select">
        <option value="">Todas</option>
        <?php foreach ($etiquetasEtapa as $k => $v): ?>
          <option value="<?= $k ?>" <?= ($_GET['etapa_actual'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold">Estado</label>
      <select name="estado" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($etiquetasEstado as $k => $v): ?>
          <option value="<?= $k ?>" <?= ($_GET['estado'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    </div>
    <div class="col-md-2">
      <a class="btn btn-light border w-100" href="/controllers/mg_expedientes.php">Limpiar</a>
    </div>
  </form>
</div>

<div class="card card-custom p-3">
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th>Estudiante</th>
          <th>Registro</th>
          <th>Modalidad</th>
          <th>Cohorte</th>
          <th>Etapa</th>
          <th>Estado</th>
          <th>Tutor</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($resultado['filas'])): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No se encontraron expedientes con esos filtros.</td></tr>
        <?php else: ?>
          <?php foreach ($resultado['filas'] as $f): ?>
            <tr>
              <td>
                <span class="fw-semibold d-block"><?= e(trim($f['nombre'] . ' ' . $f['apellido'])) ?></span>
                <span class="text-muted small"><?= e($f['nombre_carrera'] ?: '—') ?></span>
              </td>
              <td class="small"><?= e($f['registro_universitario']) ?></td>
              <td class="small"><?= e($f['modalidad_codigo']) ?></td>
              <td class="small"><?= e($f['cohorte_codigo'] ?: '—') ?></td>
              <td>
                <span class="badge <?= $clasesEtapa[$f['etapa_actual']] ?? '' ?>"><?= $etiquetasEtapa[$f['etapa_actual']] ?? e($f['etapa_actual']) ?></span>
              </td>
              <td>
                <span class="badge <?= $clasesEstado[$f['estado']] ?? '' ?>"><?= $etiquetasEstado[$f['estado']] ?? e($f['estado']) ?></span>
              </td>
              <td class="small">
                <?php if ($f['tutor_nombre']): ?>
                  <?= e(trim($f['tutor_nombre'] . ' ' . $f['tutor_apellido'])) ?>
                  <?php if ($f['numero_carta']): ?><span class="d-block text-muted" style="font-size:.68rem;"><?= e($f['numero_carta']) ?></span><?php endif; ?>
                <?php else: ?>
                  <span class="text-muted">Sin asignar</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <?php if ($puedeGestionar): ?>
                  <a class="btn btn-sm btn-primary" href="/controllers/mg_expediente.php?id=<?= (int)$f['id_declaracion'] ?>"><i class="bi bi-folder2-open"></i></a>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?= renderPaginacion($resultado) ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>