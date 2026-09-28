<?php
// =========================================================
// VISTA: TRIBUNALES Y AVALES (views/mg/jurados.php)
// ---------------------------------------------------------
// Declaraciones aprobadas con su avance de gestión: jurado y
// checklist de avales. Variable del controlador:
//   $aprobadas (controllers/mg_jurados.php)
// =========================================================
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="hero-card mb-4">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
      <h2 class="h4 fw-bold mb-1"><i class="bi bi-people-fill me-2"></i>Tribunales y Avales</h2>
      <p class="mb-0 text-muted">Asigná el jurado evaluador y controlá los documentos de cada declaración aprobada.</p>
    </div>
    <span class="badge text-bg-light border px-3 py-2">
      <?= count($aprobadas) ?> declaración(es) aprobada(s)
    </span>
  </div>
</div>

<div class="card card-custom p-3">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light text-muted text-uppercase" style="font-size:.72rem; letter-spacing:.5px;">
        <tr>
          <th>Estudiante</th>
          <th>Modalidad</th>
          <th>Periodo</th>
          <th>Jurado</th>
          <th>Avales</th>
          <th class="text-end pe-2">Gestión</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($aprobadas)): ?>
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="bi bi-patch-check fs-1 d-block mb-2 text-secondary"></i>
              Aún no hay declaraciones aprobadas para gestionar.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($aprobadas as $d): ?>
            <?php
              $juradoCompleto = (int)$d['total_jurado'] >= 2;
              $avalesCompletos = (int)$d['avales_total'] > 0 && (int)$d['avales_entregados'] === (int)$d['avales_total'];
            ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= e($d['nombre'] . ' ' . $d['apellido']) ?></div>
                <small class="text-muted"><?= e($d['carrera'] ?: '') ?></small>
              </td>
              <td>
                <span class="fw-semibold d-block"><?= e($d['modalidad_nombre']) ?></span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"><?= e($d['modalidad_codigo']) ?></span>
              </td>
              <td><span class="small"><?= e($d['periodo_nombre']) ?></span></td>
              <td>
                <?php if ((int)$d['total_jurado'] > 0): ?>
                  <span class="badge <?= $juradoCompleto ? 'bg-success bg-opacity-10 text-success border border-success-subtle' : 'bg-warning bg-opacity-10 text-warning border' ?>">
                    <i class="bi bi-<?= $juradoCompleto ? 'check-circle' : 'exclamation-circle' ?> me-1"></i>
                    <?= (int)$d['total_jurado'] ?> miembro(s)
                  </span>
                <?php else: ?>
                  <span class="badge bg-secondary bg-opacity-10 text-secondary border"><i class="bi bi-person-x me-1"></i>Sin asignar</span>
                <?php endif; ?>
              </td>
              <td>
                <?php
                  $actaFirma = $d['acta_estado'] ?? null;
                  $actaClase = $actaFirma === 'firmada'
                      ? 'bg-success bg-opacity-10 text-success border border-success-subtle'
                      : ($actaFirma === 'abierta' ? 'bg-warning bg-opacity-10 text-warning border' : 'bg-secondary bg-opacity-10 text-secondary border');
                ?>
                <?php if ((int)$d['avales_total'] > 0): ?>
                  <div class="d-flex align-items-center gap-2">
                    <div class="progress" style="width:90px;height:6px;">
                      <div class="progress-bar bg-success" style="width:<?= (int)round($d['avales_entregados'] / $d['avales_total'] * 100) ?>%"></div>
                    </div>
                    <small class="text-muted"><?= (int)$d['avales_entregados'] ?>/<?= (int)$d['avales_total'] ?></small>
                  </div>
                  <?php if ($avalesCompletos): ?>
                    <small class="text-success d-block"><i class="bi bi-check2-circle me-1"></i>Todos entregados</small>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge bg-secondary bg-opacity-10 text-secondary border">S/ checklist</span>
                <?php endif; ?>
                <div class="d-flex flex-wrap align-items-center gap-1 mt-1">
                  <?php if ($actaFirma === 'firmada'): ?>
                    <span class="badge <?= $actaClase ?>"><i class="bi bi-patch-check-fill me-1"></i>Acta firmada</span>
                  <?php elseif ($actaFirma === 'abierta'): ?>
                    <span class="badge <?= $actaClase ?>"><i class="bi bi-pencil me-1"></i>Acta abierta <?= $d['acta_nota'] !== null ? '• ' . number_format((float)$d['acta_nota'], 1) : '' ?></span>
                  <?php endif; ?>
                </div>
              </td>
              <td class="text-end pe-2">
                <div class="d-flex justify-content-end gap-2">
                  <a href="/controllers/mg_tribunal.php?id=<?= (int)$d['id_declaracion'] ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-gear me-1"></i>Gestionar
                  </a>
                  <a href="/controllers/mg_acta.php?id=<?= (int)$d['id_declaracion'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-clipboard-check me-1"></i>Acta
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>