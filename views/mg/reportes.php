<?php
// =========================================================
// VISTA: REPORTES MG (views/mg/reportes.php)
// ---------------------------------------------------------
// Panel con tarjetas, tablas y semáforos del avance del módulo
// de modalidades de grado, filtrable por periodo. Variables del
// controlador (controllers/reportes_mg.php):
//   $resumen, $topModalidades, $sinJurado, $recientes,
//   $periodos, $idPeriodo, $periodoElegido, $estadoBadges
// =========================================================
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="hero-card mb-4">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
      <h2 class="h4 fw-bold mb-1"><i class="bi bi-bar-chart-line-fill me-2"></i>Reportes MG</h2>
      <p class="mb-0 text-muted">
        Avance general del flujo de modalidades de grado
        <?= $periodoElegido ? '• ' . e($periodoElegido['nombre']) : '' ?>.
      </p>
    </div>
    <form method="GET" action="/controllers/reportes_mg.php" class="d-flex align-items-center gap-2">
      <label for="periodo" class="form-label small fw-semibold text-muted mb-0">Periodo</label>
      <select name="periodo" id="periodo" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
        <option value="0" <?= $idPeriodo === 0 ? 'selected' : '' ?>>Todos los periodos</option>
        <?php foreach ($periodos as $periodo): ?>
          <option value="<?= (int)$periodo['id_periodo'] ?>" <?= (int)$periodo['id_periodo'] === $idPeriodo ? 'selected' : '' ?>>
            <?= e($periodo['nombre']) ?><?= ($periodo['estado'] ?? '') === 'abierto' ? ' (activo)' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </form>
  </div>
</div>

<!-- ============ TARJETAS PRINCIPALES ============ -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card card-custom p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <span class="stat-icon bg-primary bg-opacity-10 text-primary"><i class="bi bi-file-earmark-text-fill"></i></span>
        <div>
          <div class="fs-4 fw-bold"><?= (int)$resumen['conteo_estado']['total'] ?></div>
          <div class="text-muted small">declaraciones</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <span class="stat-icon bg-success bg-opacity-10 text-success"><i class="bi bi-mortarboard-fill"></i></span>
        <div>
          <div class="fs-4 fw-bold"><?= (int)$resumen['estudiantes_con_declaracion'] ?></div>
          <div class="text-muted small">estudiantes declarando</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <span class="stat-icon bg-warning bg-opacity-10 text-warning"><i class="bi bi-patch-check-fill"></i></span>
        <div>
          <div class="fs-4 fw-bold"><?= (int)$resumen['conteo_estado']['aprobada'] ?></div>
          <div class="text-muted small">aprobadas</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom p-3 border-0">
      <div class="d-flex align-items-center gap-3">
        <span class="stat-icon bg-danger bg-opacity-10 text-danger"><i class="bi bi-patch-exclamation-fill"></i></span>
        <div>
          <div class="fs-4 fw-bold"><?= (int)$resumen['acta']['firmadas'] ?></div>
          <div class="text-muted small">actas firmadas</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- ============ SEMÁFORO DEL FLUJO ============ -->
  <div class="col-lg-4">
    <div class="card card-custom p-4 h-100">
      <h6 class="fw-bold mb-3 d-flex align-items-center gap-2"><i class="bi bi-sign-turn-right-fill text-primary"></i>Flujo por estado</h6>
      <div class="d-flex flex-column gap-2">
        <?php foreach ($estadoBadges as $estado => [$clase, $icono]): ?>
          <?php $etiqueta = ['borrador' => 'Borrador', 'enviada' => 'Enviada', 'en_revision' => 'En revisión', 'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada', 'cancelada' => 'Cancelada'][$estado]; ?>
          <div class="d-flex justify-content-between align-items-center px-2 py-2 rounded-3 bg-light">
            <span class="small fw-semibold"><i class="bi <?= $icono ?> me-2 text-muted"></i><?= $etiqueta ?></span>
            <span class="badge <?= $clase ?>"><?= (int)$resumen['conteo_estado'][$estado] ?></span>
          </div>
        <?php endforeach; ?>
      </div>
      <hr>
      <div class="d-flex justify-content-between small text-muted">
        <span>Total</span>
        <strong><?= (int)$resumen['conteo_estado']['total'] ?></strong>
      </div>
    </div>
  </div>

  <!-- ============ GESTIÓN PENDIENTE ============ -->
  <div class="col-lg-4">
    <div class="card card-custom p-4 h-100">
      <h6 class="fw-bold mb-3 d-flex align-items-center gap-2"><i class="bi bi-exclamation-octagon-fill text-primary"></i>Atención necesaria</h6>
      <div class="d-flex flex-column gap-2">
        <a href="/controllers/mg_jurados.php" class="text-decoration-none">
          <div class="d-flex justify-content-between align-items-center p-2 rounded-3 <?= (int)$resumen['aprobadas_sin_jurado'] > 0 ? 'bg-warning bg-opacity-10' : 'bg-light' ?>">
            <span class="small fw-semibold text-dark">Aprobadas sin tribunal</span>
            <span class="badge <?= (int)$resumen['aprobadas_sin_jurado'] > 0 ? 'bg-warning text-dark' : 'bg-secondary bg-opacity-10 text-secondary border' ?>"><?= (int)$resumen['aprobadas_sin_jurado'] ?></span>
          </div>
        </a>
        <a href="/controllers/mg_jurados.php" class="text-decoration-none">
          <div class="d-flex justify-content-between align-items-center p-2 rounded-3 <?= (int)$resumen['aprobadas_sin_acta'] > 0 ? 'bg-warning bg-opacity-10' : 'bg-light' ?>">
            <span class="small fw-semibold text-dark">Aprobadas sin acta firmada</span>
            <span class="badge <?= (int)$resumen['aprobadas_sin_acta'] > 0 ? 'bg-warning text-dark' : 'bg-secondary bg-opacity-10 text-secondary border' ?>"><?= (int)$resumen['aprobadas_sin_acta'] ?></span>
          </div>
        </a>
        <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-light">
          <span class="small fw-semibold">Avales pendientes / observados</span>
          <span class="badge bg-secondary bg-opacity-10 text-secondary border"><?= (int)$resumen['avales']['pendientes'] ?> / <?= (int)$resumen['avales']['observados'] ?></span>
        </div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-light">
          <span class="small fw-semibold">Avales entregados</span>
          <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle"><?= (int)$resumen['avales']['entregados'] ?> / <?= (int)$resumen['avales']['total'] ?></span>
        </div>
      </div>
      <hr>
      <div class="progress" style="height:8px;">
        <?php $avPct = (int)$resumen['avales']['total'] > 0 ? (int)round($resumen['avales']['entregados'] / $resumen['avales']['total'] * 100) : 0; ?>
        <div class="progress-bar bg-success" style="width:<?= $avPct ?>%"></div>
      </div>
      <small class="text-muted mt-2"><?= $avPct ?>% de avales entregados</small>
    </div>
  </div>

  <!-- ============ ACTAS ============ -->
  <div class="col-lg-4">
    <div class="card card-custom p-4 h-100">
      <h6 class="fw-bold mb-3 d-flex align-items-center gap-2"><i class="bi bi-clipboard-pulse-fill text-primary"></i>Actas de calificación</h6>
      <?php if ((int)$resumen['acta']['firmadas'] > 0): ?>
        <div class="d-flex align-items-end justify-content-between mb-3">
          <div>
            <div class="display-6 fw-bold"><?= number_format((float)($resumen['acta']['nota_promedio'] ?? 0), 2) ?></div>
            <small class="text-muted">nota promedio en actas firmadas</small>
          </div>
          <div class="text-end">
            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i><?= (int)$resumen['acta']['aprobados'] ?> aprobados</span>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle ms-1"><i class="bi bi-x-circle me-1"></i><?= (int)$resumen['acta']['reprobados'] ?> reprobados</span>
          </div>
        </div>
        <a href="/controllers/mg_jurados.php" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-file-earmark-text me-1"></i>Revisar tribunales</a>
      <?php else: ?>
        <div class="text-center text-muted py-4">
          <i class="bi bi-clipboard2 fs-1 d-block mb-2 text-secondary"></i>
          <p class="small mb-0">Todavía no hay actas firmadas.</p>
          <p class="small text-secondary">Abiertas: <?= (int)$resumen['acta']['abiertas'] ?></p>
        </div>
        <a href="/controllers/mg_jurados.php" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-pencil me-1"></i>Abrir actas</a>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="row g-4 mt-0">
  <!-- ============ TOP MODALIDADES ============ -->
  <div class="col-lg-5">
    <div class="card card-custom p-4 h-100">
      <h6 class="fw-bold mb-3 d-flex align-items-center gap-2"><i class="bi bi-trophy-fill text-primary"></i>Modalidades más elegidas</h6>
      <?php if (empty($topModalidades)): ?>
        <p class="text-muted small mb-0">Sin declaraciones el <?= $periodoElegido ? 'periodo ' . e($periodoElegido['nombre']) : 'en este periodo' ?>.</p>
      <?php else: ?>
        <?php $maxTop = max(array_column($topModalidades, 'total')); ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($topModalidades as $i => $m): ?>
            <li class="list-group-item px-0 bg-transparent">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fw-semibold small">
                  <?= $i === 0 ? '<i class="bi bi-award-fill text-warning me-1"></i>' : ($i === 1 ? '<i class="bi bi-award text-warning me-1"></i>' : ($i === 2 ? '<i class="bi bi-patch-plus text-danger me-1"></i>' : '')) ?>
                  <?= e($m['codigo']) ?> — <?= e($m['nombre']) ?>
                </span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"><?= (int)$m['total'] ?></span>
              </div>
              <div class="progress" style="height:6px;">
                <div class="progress-bar <?= $i === 0 ? 'bg-warning' : ($i === 1 ? 'bg-primary' : 'bg-info') ?>" style="width:<?= (int)round($m['total'] / $maxTop * 100) ?>%"></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- ============ APROBADAS SIN JURADO ============ -->
  <div class="col-lg-7">
    <div class="card card-custom p-4 h-100">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0 d-flex align-items-center gap-2"><i class="bi bi-people-fill text-primary"></i>Aprobadas sin asignar tribunal</h6>
        <?php if (count($sinJurado) > 0): ?>
          <a href="/controllers/mg_jurados.php" class="btn btn-sm btn-light border">Ir a Tribunales</a>
        <?php endif; ?>
      </div>
      <?php if (empty($sinJurado)): ?>
        <div class="text-center text-success py-4">
          <i class="bi bi-check2-circle fs-1 d-block mb-2"></i>
          <p class="small mb-0">Todas las aprobadas tienen tribunal asignado.</p>
        </div>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($sinJurado as $d): ?>
            <li class="list-group-item px-0 bg-transparent d-flex justify-content-between align-items-center gap-2">
              <div class="min-w-0">
                <div class="fw-semibold small text-truncate"><?= e($d['nombre'] . ' ' . $d['apellido']) ?> <span class="text-muted fw-normal">— <?= e($d['modalidad_codigo']) ?></span></div>
                <small class="text-muted"><?= e($d['carrera'] ?: 'Sin carrera') ?> • <?= (int)$d['avales_entregados'] ?>/<?= (int)$d['avales_total'] ?> avales</small>
              </div>
              <a href="/controllers/mg_tribunal.php?id=<?= (int)$d['id_declaracion'] ?>" class="btn btn-sm btn-outline-primary flex-shrink-0">Asignar</a>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- ============ ACTIVIDAD RECIENTE ============ -->
<?php if (!empty($recientes)): ?>
<div class="row mt-4">
  <div class="col-12">
    <div class="card card-custom p-4">
      <h6 class="fw-bold mb-3 d-flex align-items-center gap-2"><i class="bi bi-activity text-primary"></i>Actividad reciente</h6>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
          <thead class="table-light text-muted text-uppercase" style="font-size:.72rem;">
            <tr><th>Estudiante</th><th>Modalidad</th><th>Estado</th><th>Periodo</th><th class="text-end">Última actividad</th></tr>
          </thead>
          <tbody>
            <?php foreach ($recientes as $r): ?>
              <tr>
                <td class="fw-semibold small"><?= e($r['nombre'] . ' ' . $r['apellido']) ?></td>
                <td><span class="small"><?= e($r['modalidad_codigo']) ?> — <?= e($r['modalidad_nombre']) ?></span></td>
                <td>
                  <?php [$clase, $icono] = $estadoBadges[$r['estado']] ?? [null, 'bi-circle']; ?>
                  <span class="badge <?= $clase ?>"><i class="bi <?= $icono ?> me-1"></i><?= e($r['estado']) ?></span>
                </td>
                <td><span class="small text-muted"><?= e($r['periodo_nombre']) ?></span></td>
                <td class="small text-muted text-end"><?= date('d/m/Y H:i', strtotime($r['fecha_revision'] ?: $r['fecha_creacion'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>