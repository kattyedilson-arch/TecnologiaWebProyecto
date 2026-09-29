<?php
// =========================================================
// VISTA: CALENDARIO DE HITOS MG (views/mg/calendario.php)
// ---------------------------------------------------------
// Muestra los hitos del proceso (talleres, informes, defensas,
// entregas) con filtro por cohorte y formulario de alta/edición.
// Variables: $eventos, $cohortes, $cohorteActual, $eventoEditar,
// $idCohorte (controllers/mg_calendario.php)
// =========================================================
require_once __DIR__ . '/../layouts/header.php';
$filtroUrl = $idCohorte > 0 ? '?id_cohorte=' . $idCohorte : '';
$hoy = date('Y-m-d');
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="h5 fw-bold mb-1">Calendario de Modalidades de Grado</h2>
    <p class="text-muted small mb-0">
      Hitos del proceso: talleres, informes de avance, defensas y entregas.
    </p>
  </div>
</div>

<!-- Filtro por cohorte -->
<div class="card card-custom p-3 mb-4">
  <form method="GET" action="/controllers/mg_calendario.php" class="row g-2 align-items-end">
    <div class="col-md-4">
      <label class="form-label small fw-semibold">Cohorte</label>
      <select name="id_cohorte" class="form-select" onchange="this.form.submit()">
        <option value="">— Todas las cohortes (global) —</option>
        <?php foreach ($cohortes as $cohorte): ?>
          <option value="<?= (int)$cohorte['id_cohorte'] ?>"
                  <?= $idCohorte === (int)$cohorte['id_cohorte'] ? 'selected' : '' ?>>
            <?= e($cohorte['codigo']) ?> — <?= e($cohorte['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-8 text-end">
      <?php if ($cohorteActual): ?>
        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-3 py-2">
          <i class="bi bi-people-fill me-1"></i>Calendario de <?= e($cohorteActual['nombre']) ?>
        </span>
      <?php else: ?>
        <span class="badge bg-light text-dark border px-3 py-2">Viendo todos los hitos</span>
      <?php endif; ?>
    </div>
  </form>
</div>

<div class="row g-4">
  <!-- Columna del formulario -->
  <div class="col-lg-4">
    <div class="card card-custom p-4">
      <h5 class="fw-bold mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-calendar-plus text-primary"></i>
        <?= $eventoEditar ? 'Editar Hito' : 'Nuevo Hito' ?>
      </h5>
      <p class="text-muted small mb-3">Los hitos globales valen para todo el módulo de grado.</p>

      <form method="POST" action="/controllers/calendario_guardar.php">
        <?php if ($eventoEditar): ?>
          <input type="hidden" name="id" value="<?= (int)$eventoEditar['id_evento'] ?>">
        <?php endif; ?>
        <?php if ($idCohorte > 0 && !$eventoEditar): ?>
          <input type="hidden" name="id_cohorte" value="<?= $idCohorte ?>">
        <?php endif; ?>
        <?= campoCsrf() ?>

        <div class="mb-2">
          <label class="form-label small fw-semibold">Título del hito</label>
          <input type="text" name="titulo" class="form-control" placeholder="Taller de modalidades"
                 value="<?= e($eventoEditar['titulo'] ?? '') ?>" required>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-7">
            <label class="form-label small fw-semibold">Tipo</label>
            <select name="tipo_hito" class="form-select">
              <?php
                $tiposHito = ['taller' => 'Taller', 'informe' => 'Informe de avance', 'defensa' => 'Defensa', 'entrega' => 'Entrega'];
                $tipoActual = $eventoEditar['tipo_hito'] ?? 'entrega';
              ?>
              <?php foreach ($tiposHito as $valorTipo => $textoTipo): ?>
                <option value="<?= $valorTipo ?>" <?= $tipoActual === $valorTipo ? 'selected' : '' ?>><?= $textoTipo ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-5">
            <label class="form-label small fw-semibold">Fecha</label>
            <input type="date" name="fecha" class="form-control"
                   value="<?= e($eventoEditar['fecha'] ?? $hoy) ?>" required>
          </div>
        </div>

        <?php if (!$eventoEditar): ?>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Cohorte (opcional)</label>
            <select name="id_cohorte" class="form-select" <?= $idCohorte > 0 ? 'disabled' : '' ?>>
              <option value="">— Global (todas las cohortes) —</option>
              <?php foreach ($cohortes as $cohorte): ?>
                <option value="<?= (int)$cohorte['id_cohorte'] ?>" <?= $idCohorte === (int)$cohorte['id_cohorte'] ? 'selected' : '' ?>>
                  <?= e($cohorte['codigo']) ?> — <?= e($cohorte['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php else: ?>
          <?php $cohorteEventoId = (int)($eventoEditar['id_cohorte'] ?? 0); ?>
          <input type="hidden" name="id_cohorte" value="<?= $cohorteEventoId ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Descripción (opcional)</label>
          <textarea name="descripcion" class="form-control" rows="2" placeholder="Detalle del hito..."><?= e($eventoEditar['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-lg me-1"></i><?= $eventoEditar ? 'Guardar cambios' : 'Registrar hito' ?>
          </button>
          <?php if ($eventoEditar): ?>
            <a href="/controllers/mg_calendario.php<?= $filtroUrl ?>" class="btn btn-light border"><i class="bi bi-x-lg"></i></a>
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
          <i class="bi bi-calendar3 text-primary"></i> Hitos del calendario
          <span class="badge bg-light text-dark border"><?= count($eventos) ?></span>
        </h6>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Tipo</th>
              <th>Título</th>
              <th>Cohorte</th>
              <th>Descripción</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($eventos)): ?>
              <tr><td colspan="6" class="text-center text-muted py-4">Aún no hay hitos en el calendario.</td></tr>
            <?php else: ?>
              <?php
                $tiposClase = [
                  'taller'   => 'bg-info bg-opacity-10 text-info border border-info-subtle',
                  'informe'  => 'bg-warning bg-opacity-10 text-warning border border-warning-subtle',
                  'defensa'  => 'bg-danger bg-opacity-10 text-danger border border-danger-subtle',
                  'entrega'  => 'bg-primary bg-opacity-10 text-primary border border-primary-subtle',
                ];
                $tiposIcono = ['taller' => 'easel2', 'informe' => 'file-text', 'defensa' => 'shield-check', 'entrega' => 'box-seam'];
              ?>
              <?php foreach ($eventos as $evento): ?>
                <tr>
                  <td class="fw-semibold">
                    <?= date('d/m/Y', strtotime($evento['fecha'])) ?>
                    <?php if (strtotime($evento['fecha']) < strtotime($hoy)): ?>
                      <span class="d-block text-muted" style="font-size:.62rem;">pasado</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span class="badge <?= $tiposClase[$evento['tipo_hito']] ?? '' ?>">
                      <i class="bi bi-<?= $tiposIcono[$evento['tipo_hito']] ?? 'calendar-event' ?> me-1"></i>
                      <?= e(ucfirst($evento['tipo_hito'])) ?>
                    </span>
                  </td>
                  <td class="fw-semibold"><?= e($evento['titulo']) ?></td>
                  <td class="text-muted small"><?= e($evento['cohorte_codigo'] ?: 'Global') ?></td>
                  <td class="text-muted small"><?= e($evento['descripcion'] ?: '—') ?></td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1">
                      <a class="btn btn-sm btn-light border" href="/controllers/mg_calendario.php?editar=<?= (int)$evento['id_evento'] ?><?= $idCohorte > 0 ? '&id_cohorte=' . $idCohorte : '' ?>" title="Editar"><i class="bi bi-pencil-square"></i></a>
                      <form method="POST" action="/controllers/calendario_eliminar.php" class="d-inline"
                            data-confirm="¿Eliminar el hito <?= e($evento['titulo']) ?>?"
                            data-confirm-title="Confirmar eliminación">
                        <input type="hidden" name="id" value="<?= (int)$evento['id_evento'] ?>">
                        <input type="hidden" name="id_cohorte" value="<?= $idCohorte ?>">
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