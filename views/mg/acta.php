<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<?php
$actaEstado = $acta['estado'] ?? 'abierta';
$firmada = $actaEstado === 'firmada';
$notasPorJurado = [];
if ($acta && isset($acta['notas'])) {
    foreach ($acta['notas'] as $notasRow) {
        $notasPorJurado[(int)$notasRow['id_jurado']] = $notasRow;
    }
}
$resultadoBadge = [
    'aprobado'  => ['bg-success bg-opacity-10 text-success border border-success-subtle', 'bi-check-circle-fill', 'APROBADO'],
    'reprobado' => ['bg-danger bg-opacity-10 text-danger border border-danger-subtle', 'bi-x-circle-fill', 'REPROBADO'],
    'pendiente' => ['bg-secondary bg-opacity-10 text-secondary border', 'bi-hourglass-split', 'Sin calificación'],
][$acta['resultado'] ?? 'pendiente'] ?? ['bg-secondary bg-opacity-10 text-secondary border', 'bi-hourglass-split', 'Sin calificación'];
$fechaDefensa = !empty($acta['fecha_defensa']) ? date('Y-m-d\TH:i', strtotime($acta['fecha_defensa'])) : '';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <a href="/controllers/mg_tribunal.php?id=<?= (int)$declaracion['id_declaracion'] ?>" class="btn btn-sm btn-light border mb-2"><i class="bi bi-arrow-left me-1"></i>Tribunal y avales</a>
    <h2 class="h5 fw-bold mb-1">
      Acta de calificación
      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle ms-1"><?= e($declaracion['modalidad_codigo']) ?></span>
      <?php if ($firmada): ?>
        <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle"><i class="bi bi-patch-check-fill me-1"></i>Firmada</span>
      <?php else: ?>
        <span class="badge bg-warning bg-opacity-10 text-warning border"><i class="bi bi-pencil me-1"></i>Abierta</span>
      <?php endif; ?>
    </h2>
    <p class="text-muted small mb-0">
      <?= e($declaracion['nombre'] . ' ' . $declaracion['apellido']) ?> • <?= e($declaracion['carrera'] ? $declaracion['carrera'] : 'Sin carrera') ?>
      • <strong><?= e($declaracion['modalidad_nombre']) ?></strong>
      <?= $declaracion['titulo_proyecto'] ? '• <i class="bi bi-eject me-1"></i>' . e($declaracion['titulo_proyecto']) : '' ?>
    </p>
  </div>
  <?php if ($firmada && $tieneNotas): ?>
    <a href="/controllers/acta_imprimir.php?id=<?= (int)$declaracion['id_declaracion'] ?>" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-printer me-1"></i>Ver / imprimir acta
    </a>
  <?php endif; ?>
</div>

<?php if (empty($jurado)): ?>
  <div class="alert alert-warning d-flex justify-content-between align-items-center flex-wrap gap-2">
    <span class="small"><i class="bi bi-people-fill me-2"></i>Esta declaración aún no tiene tribunal asignado.</span>
    <a href="/controllers/mg_tribunal.php?id=<?= (int)$declaracion['id_declaracion'] ?>" class="btn btn-sm btn-warning">Asignar jurado</a>
  </div>
<?php endif; ?>

<div class="row g-4">
  <!-- ============ CUADRO DE NOTAS ============ -->
  <div class="col-lg-7">
    <div class="card card-custom p-4">
      <h6 class="fw-bold mb-1 d-flex align-items-center gap-2"><i class="bi bi-table text-primary"></i> Cuadro de notas del jurado</h6>
      <p class="text-muted small mb-3">Escala <strong>0 a <?= (int)$notaMax ?></strong>. La nota final es el promedio ponderado igual entre jurados. Aprobado desde <?= (int)$aprobadoMin ?>.</p>

      <form method="POST" action="/controllers/acta_guardar.php" id="form-acta">
        <?= campoCsrf() ?>
        <input type="hidden" name="id_declaracion" value="<?= (int)$declaracion['id_declaracion'] ?>">
        <input type="hidden" name="accion" value="notas">

        <?php if (empty($jurado)): ?>
          <div class="text-center text-muted small py-4"><i class="bi bi-people me-1"></i>Sin jurado no hay notas.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm align-middle" style="--bs-table-bg:transparent;">
              <thead>
                <tr class="text-muted small">
                  <th>Jurado</th>
                  <th style="width:130px;">Nota (0-<?= (int)$notaMax ?>)</th>
                  <th>Comentario</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($jurado as $miembro): ?>
                  <?php $notaFila = $notasPorJurado[(int)$miembro['id_jurado']] ?? null; ?>
                  <tr>
                    <td>
                      <div class="fw-semibold small"><?= e($miembro['nombre'] . ' ' . $miembro['apellido']) ?></div>
                      <small class="text-muted text-uppercase" style="font-size:.6rem;"><?= e($miembro['rol_jurado']) ?><?= !empty($miembro['especialidad']) ? ' • ' . e($miembro['especialidad']) : '' ?></small>
                    </td>
                    <td>
                      <input type="number" name="nota[<?= (int)$miembro['id_jurado'] ?>]"
                             class="form-control form-control-sm text-center"
                             min="0" max="<?= (int)$notaMax ?>" step="0.01"
                             value="<?= $notaFila !== null ? (float)$notaFila['nota'] : '' ?>"
                             placeholder="—" <?= ($puedeGuardar && !$firmada) ? '' : 'disabled' ?>>
                    </td>
                    <td>
                      <input type="text" name="comentario[<?= (int)$miembro['id_jurado'] ?>]"
                             class="form-control form-control-sm"
                             maxlength="255"
                             value="<?= $notaFila !== null ? e((string)$notaFila['comentario']) : '' ?>"
                             placeholder="Observación del jurado" <?= ($puedeGuardar && !$firmada) ? '' : 'disabled' ?>>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <div class="row g-2 mt-1">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Fecha de la defensa</label>
            <input type="datetime-local" name="fecha_defensa" class="form-control form-control-sm"
                   value="<?= e($fechaDefensa) ?>" <?= ($puedeGuardar && !$firmada) ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Lugar</label>
            <input type="text" name="lugar" class="form-control form-control-sm" maxlength="255"
                   value="<?= e((string)($acta['lugar'] ?? '')) ?>" placeholder="Aula / enlace virtual"
                   <?= ($puedeGuardar && !$firmada) ? '' : 'disabled' ?>>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Observaciones</label>
            <input type="text" name="observaciones" class="form-control form-control-sm"
                   value="<?= e((string)($acta['observaciones'] ?? '')) ?>" maxlength="500"
                   <?= ($puedeGuardar && !$firmada) ? '' : 'disabled' ?>>
          </div>
        </div>

        <div class="d-flex gap-2 mt-4">
          <button type="submit" class="btn btn-primary" <?= ($puedeGuardar && !$firmada && !empty($jurado)) ? '' : 'disabled' ?>>
            <i class="bi bi-check2-square me-1"></i>Guardar notas
          </button>
          <a href="/controllers/mg_acta.php?id=<?= (int)$declaracion['id_declaracion'] ?>" class="btn btn-light border">Descartar</a>
        </div>
        <?php if (!$puedeGuardar): ?>
          <p class="text-muted small mb-0 mt-2">Solo el equipo MG (coordinador/auxiliar) registra notas.</p>
        <?php elseif ($firmada): ?>
          <p class="text-muted small mb-0 mt-2"><i class="bi bi-lock-fill me-1"></i>Acta firmada: las notas ya no se pueden modificar.</p>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- ============ RESUMEN Y FIRMA ============ -->
  <div class="col-lg-5">
    <div class="card card-custom p-4 mb-4">
      <h6 class="fw-bold mb-3 d-flex align-items-center gap-2"><i class="bi bi-clipboard-pulse text-primary"></i>Resultado</h6>
      <div class="d-flex align-items-end justify-content-between">
        <div>
          <div class="display-6 fw-bold"><?= $acta['nota_final'] !== null ? number_format((float)$acta['nota_final'], 2) : '—' ?></div>
          <small class="text-muted">sobre <?= (int)$notaMax ?></small>
        </div>
        <span class="badge px-3 py-2 <?= $resultadoBadge[0] ?>"><i class="bi <?= $resultadoBadge[1] ?> me-1"></i><?= $resultadoBadge[2] ?></span>
      </div>
      <div class="progress mt-3" style="height:8px;">
        <div class="progress-bar <?= ($acta['resultado'] ?? '') === 'aprobado' ? 'bg-success' : 'bg-danger' ?>" style="width:<?= min((float)($acta['nota_final'] ?? 0) / $notaMax * 100, 100) ?>%"></div>
      </div>
      <?php if ($tieneNotas): ?>
        <p class="text-muted small mb-0 mt-3">
          <?= count($acta['notas']) ?> nota(s) registrada(s). La nota final se recalcula al guardar.
        </p>
      <?php else: ?>
        <p class="text-muted small mb-0 mt-3"><i class="bi bi-info-circle me-1"></i>El resultado aparece cuando se guarde al menos una nota.</p>
      <?php endif; ?>
    </div>

    <div class="card card-custom p-4">
      <h6 class="fw-bold mb-3 d-flex align-items-center gap-2"><i class="bi bi-pen-fill text-primary"></i>Firma del acta</h6>
      <?php if ($firmada): ?>
        <div class="alert alert-success mb-3 py-2 px-3 small">
          <i class="bi bi-patch-check-fill me-1"></i>Firmada por <strong><?= e(trim(($acta['firmante_nombre'] ?? '') . ' ' . ($acta['firmante_apellido'] ?? ''))) ?: '—' ?></strong>
          el <?= !empty($acta['fecha_firma']) ? date('d/m/Y H:i', strtotime($acta['fecha_firma'])) : '—' ?>.
          <?php if (!empty($acta['presidente'])): ?>
            <div class="mt-1"><i class="bi bi-person-badge me-1"></i>Presidente del tribunal: <strong><?= e($acta['presidente']) ?></strong></div>
          <?php endif; ?>
        </div>
        <a href="/controllers/acta_imprimir.php?id=<?= (int)$declaracion['id_declaracion'] ?>" class="btn btn-outline-primary w-100">
          <i class="bi bi-printer me-1"></i>Ver / imprimir acta
        </a>
      <?php else: ?>
        <?php if (!$puedeFirmar): ?>
          <p class="text-muted small mb-0">Solo el coordinador de MG puede firmar esta acta.</p>
        <?php else: ?>
          <form method="POST" action="/controllers/acta_guardar.php"
                data-confirm="¿Firmar el acta? Los pasos se cierran y la nota quedará definitiva e imprimible."
                data-confirm-title="Firmar acta">
            <?= campoCsrf() ?>
            <input type="hidden" name="id_declaracion" value="<?= (int)$declaracion['id_declaracion'] ?>">
            <input type="hidden" name="accion" value="firmar">
            <button type="submit" class="btn btn-success w-100" <?= $tieneNotas ? '' : 'disabled' ?>>
              <i class="bi bi-pen me-1"></i>Firmar acta y cerrar
            </button>
          </form>
          <?php if (!$tieneNotas): ?>
            <p class="text-muted small mb-0 mt-2">Guardá al menos una nota para poder firmar.</p>
          <?php endif; ?>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>