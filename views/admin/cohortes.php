<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="h5 fw-bold mb-1">Cohortes de Modalidades de Grado</h2>
    <p class="text-muted small mb-0">
      Agrupación de estudiantes que cursan la modalidad dentro de un periodo académico.
    </p>
  </div>
</div>

<div class="row g-4">
  <!-- Columna del formulario -->
  <div class="col-lg-4">
    <div class="card card-custom p-4">
      <h5 class="fw-bold mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-people-fill text-primary"></i>
        <?= $cohorteEditar ? 'Editar Cohorte' : 'Nueva Cohorte' ?>
      </h5>
      <p class="text-muted small mb-3">Cada cohorte se asocia a un periodo del calendario académico.</p>

      <form method="POST" action="/controllers/cohortes_guardar.php">
        <?php if ($cohorteEditar): ?>
          <input type="hidden" name="id" value="<?= (int)$cohorteEditar['id_cohorte'] ?>">
        <?php endif; ?>
        <?= campoCsrf() ?>

        <div class="mb-2">
          <label class="form-label small fw-semibold">Código</label>
          <input type="text" name="codigo" class="form-control" placeholder="C-2026-1"
                 value="<?= e($cohorteEditar['codigo'] ?? '') ?>" required>
        </div>

        <div class="mb-2">
          <label class="form-label small fw-semibold">Nombre</label>
          <input type="text" name="nombre" class="form-control" placeholder="Cohorte 2026-1"
                 value="<?= e($cohorteEditar['nombre'] ?? '') ?>" required>
        </div>

        <div class="mb-2">
          <label class="form-label small fw-semibold">Periodo académico</label>
          <select name="id_periodo" class="form-select">
            <option value="">— Sin periodo —</option>
            <?php foreach ($periodos as $periodo): ?>
              <option value="<?= (int)$periodo['id_periodo'] ?>"
                      <?= (int)($cohorteEditar['id_periodo'] ?? 0) === (int)$periodo['id_periodo'] ? 'selected' : '' ?>>
                <?= e($periodo['nombre']) ?> (<?= ($periodo['estado'] ?? '') === 'abierto' ? 'abierto' : 'cerrado' ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label small fw-semibold">Inicio</label>
            <input type="date" name="fecha_inicio" class="form-control"
                   value="<?= e($cohorteEditar['fecha_inicio'] ?? date('Y-m-d')) ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Fin</label>
            <input type="date" name="fecha_fin" class="form-control"
                   value="<?= e($cohorteEditar['fecha_fin'] ?? date('Y-m-d')) ?>" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Estado</label>
          <select name="estado" class="form-select">
            <option value="vigente" <?= ($cohorteEditar['estado'] ?? '') === 'vigente' ? 'selected' : '' ?>>Vigente</option>
            <option value="cerrada" <?= ($cohorteEditar['estado'] ?? '') === 'cerrada' ? 'selected' : '' ?>>Cerrada</option>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-lg me-1"></i><?= $cohorteEditar ? 'Guardar cambios' : 'Registrar cohorte' ?>
          </button>
          <?php if ($cohorteEditar): ?>
            <a href="/controllers/admin_cohortes.php" class="btn btn-light border"><i class="bi bi-x-lg"></i></a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <!-- Columna de la tabla -->
  <div class="col-lg-8">
    <div class="card card-custom p-3">
      <div class="d-flex justify-content-between align-items-center px-2 py-1 mb-2">
        <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-calendar-range text-primary"></i> Cohortes registradas
          <span class="badge bg-light text-dark border"><?= count($cohortes) ?></span>
        </h6>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Código</th>
              <th>Nombre</th>
              <th>Periodo</th>
              <th>Ventana</th>
              <th>Estado</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($cohortes)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Aún no hay cohortes registradas.</td></tr>
            <?php else: ?>
              <?php foreach ($cohortes as $cohorte): ?>
                <tr>
                  <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"><?= e($cohorte['codigo']) ?></span></td>
                  <td class="fw-semibold"><?= e($cohorte['nombre']) ?></td>
                  <td class="text-muted small"><?= e($cohorte['periodo_nombre'] ?: '—') ?></td>
                  <td class="text-muted small">
                    <?= date('d/m/Y', strtotime($cohorte['fecha_inicio'])) ?> — <?= date('d/m/Y', strtotime($cohorte['fecha_fin'])) ?>
                  </td>
                  <td>
                    <?php if (($cohorte['estado'] ?? '') === 'vigente'): ?>
                      <span class="badge badge-state bg-success bg-opacity-10 text-success border border-success-subtle"><i class="bi bi-unlock me-1"></i>Vigente</span>
                    <?php else: ?>
                      <span class="badge badge-state bg-secondary bg-opacity-10 text-secondary border"><i class="bi bi-lock me-1"></i>Cerrada</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1">
                      <a class="btn btn-sm btn-light border" href="/controllers/mg_calendario.php?id_cohorte=<?= (int)$cohorte['id_cohorte'] ?>" title="Calendario de la cohorte"><i class="bi bi-calendar3"></i></a>
                      <a class="btn btn-sm btn-light border" href="/controllers/admin_cohortes.php?editar=<?= (int)$cohorte['id_cohorte'] ?>" title="Editar"><i class="bi bi-pencil-square"></i></a>
                      <form method="POST" action="/controllers/cohortes_eliminar.php" class="d-inline"
                            data-confirm="¿Eliminar la cohorte <?= e($cohorte['nombre']) ?>?"
                            data-confirm-title="Confirmar eliminación"
                            data-confirm-text="Se eliminarán también sus eventos de calendario asociados.">
                        <input type="hidden" name="id" value="<?= (int)$cohorte['id_cohorte'] ?>">
                        <?= campoCsrf() ?>
                        <button type="submit" class="btn btn-sm btn-light border text-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>