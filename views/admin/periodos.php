<?php
// =========================================================
// VISTA: GESTIÓN DE PERIODOS ACADÉMICOS (views/admin/periodos.php)
// ---------------------------------------------------------
// Card con el formulario de alta/edición y tabla de periodos
// con su estado, ventana de fechas y acciones (editar/eliminar
// por POST). Variables esperadas del controlador:
//   $periodos, $periodoEditarCompleto (controllers/admin_periodos.php)
// =========================================================
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="row g-4">
  <!-- Columna del formulario -->
  <div class="col-lg-4">
    <div class="card card-custom p-4">
      <h5 class="fw-bold mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-calendar-plus text-primary"></i>
        <?= $periodoEditarCompleto ? 'Editar Periodo' : 'Nuevo Periodo' ?>
      </h5>
      <p class="text-muted small mb-3">
        Ventana académica usada por las Modalidades de Grado para declaraciones.
      </p>

      <form method="POST" action="/controllers/periodos_guardar.php">
        <?php if ($periodoEditarCompleto): ?>
          <input type="hidden" name="id" value="<?= (int)$periodoEditarCompleto['id_periodo'] ?>">
        <?php endif; ?>
        <?= campoCsrf() ?>

        <div class="mb-2">
          <label class="form-label small fw-semibold">Nombre del periodo</label>
          <input type="text" name="nombre" class="form-control" placeholder="2026-1"
                 value="<?= e($periodoEditarCompleto['nombre'] ?? '') ?>" required>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label small fw-semibold">Inicio</label>
            <input type="date" name="fecha_inicio" class="form-control"
                   value="<?= e($periodoEditarCompleto['fecha_inicio'] ?? date('Y-m-d')) ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Fin</label>
            <input type="date" name="fecha_fin" class="form-control"
                   value="<?= e($periodoEditarCompleto['fecha_fin'] ?? date('Y-m-d')) ?>" required>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Estado</label>
          <select name="estado" class="form-select">
            <option value="abierto" <?= ($periodoEditarCompleto['estado'] ?? '') === 'abierto' ? 'selected' : '' ?>>Abierto (recibe declaraciones)</option>
            <option value="cerrado" <?= ($periodoEditarCompleto['estado'] ?? '') === 'cerrado' ? 'selected' : '' ?>>Cerrado</option>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Descripción (opcional)</label>
          <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalle del periodo..."><?= e($periodoEditarCompleto['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-lg me-1"></i><?= $periodoEditarCompleto ? 'Guardar cambios' : 'Crear periodo' ?>
          </button>
          <?php if ($periodoEditarCompleto): ?>
            <a href="/controllers/admin_periodos.php" class="btn btn-light border"><i class="bi bi-x-lg"></i></a>
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
          <i class="bi bi-calendar-range text-primary"></i> Periodos registrados
          <span class="badge bg-light text-dark border"><?= count($periodos) ?></span>
        </h6>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Ventana</th>
              <th>Estado</th>
              <th>Descripción</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($periodos)): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">Aún no hay periodos registrados.</td></tr>
            <?php else: ?>
              <?php foreach ($periodos as $periodo): ?>
                <tr>
                  <td class="fw-semibold"><?= e($periodo['nombre']) ?></td>
                  <td class="text-muted small">
                    <?= date('d/m/Y', strtotime($periodo['fecha_inicio'])) ?> — <?= date('d/m/Y', strtotime($periodo['fecha_fin'])) ?>
                    <?php if (($periodo['estado'] ?? '') === 'abierto' && isset($periodo['dias_restantes']) && (int)$periodo['dias_restantes'] >= 0): ?>
                      <span class="d-block text-ok small">Quedan <?= (int)$periodo['dias_restantes'] ?> días</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (($periodo['estado'] ?? '') === 'abierto'): ?>
                      <span class="badge badge-state bg-success bg-opacity-10 text-success border border-success-subtle"><i class="bi bi-unlock me-1"></i>Abierto</span>
                    <?php else: ?>
                      <span class="badge badge-state bg-secondary bg-opacity-10 text-secondary border"><i class="bi bi-lock me-1"></i>Cerrado</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-muted small"><?= e($periodo['descripcion'] ?: '—') ?></td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1">
                      <a class="btn btn-sm btn-light border" href="/controllers/admin_periodos.php?editar=<?= (int)$periodo['id_periodo'] ?>" title="Editar"><i class="bi bi-pencil-square"></i></a>
                      <form method="POST" action="/controllers/periodos_eliminar.php" class="d-inline"
                            data-confirm="¿Eliminar el periodo <?= e($periodo['nombre']) ?>?"
                            data-confirm-title="Confirmar eliminación"
                            data-confirm-text="Se perderá la configuración de este periodo académico.">
                        <input type="hidden" name="id" value="<?= (int)$periodo['id_periodo'] ?>">
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