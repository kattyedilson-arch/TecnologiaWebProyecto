<?php require_once __DIR__ . '/../layouts/header.php'; ?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="h5 fw-bold mb-1">Parámetros de Modalidades de Grado</h2>
    <p class="text-muted small mb-0">
      Cifras y umbrales del flujo MG. Los editables los ajusta el coordinador; los técnicos son de solo lectura.
    </p>
  </div>
</div>

<?php if (!$puedeEditar): ?>
  <div class="alert alert-info d-flex align-items-center gap-2 py-2 px-3 rounded-3">
    <i class="bi bi-info-circle-fill"></i>
    <span class="small">Solo lectura: para modificar estos valores se requiere el rol de coordinador de MG o administrador.</span>
  </div>
<?php endif; ?>

<form method="POST" action="/controllers/parametros_guardar.php">
  <?= campoCsrf() ?>
  <div class="card card-custom p-0">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>Clave</th>
            <th>Valor</th>
            <th>Descripción</th>
            <th class="text-center">Editable</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($parametros)): ?>
            <tr><td colspan="4" class="text-center text-muted py-4">Aún no hay parámetros registrados.</td></tr>
          <?php else: ?>
            <?php foreach ($parametros as $parametro): ?>
              <?php $editable = (bool)(int)$parametro['es_editable']; ?>
              <tr>
                <td>
                  <span class="fw-semibold font-monospace text-primary"><?= e($parametro['clave']) ?></span>
                </td>
                <td style="width:160px;">
                  <?php if ($editable && $puedeEditar): ?>
                    <input type="number" name="valor[<?= (int)$parametro['id_parametro'] ?>]"
                           class="form-control form-control-sm"
                           min="0" step="0.01"
                           value="<?= e($parametro['valor']) ?>" required>
                  <?php else: ?>
                    <span class="fs-6 fw-bold"><?= e($parametro['valor']) ?></span>
                  <?php endif; ?>
                </td>
                <td class="text-muted small"><?= e($parametro['descripcion']) ?></td>
                <td class="text-center">
                  <?php if ($editable): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle"><i class="bi bi-pencil me-1"></i>Editable</span>
                  <?php else: ?>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border"><i class="bi bi-lock me-1"></i>Fijo</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <?php if ($puedeEditar): ?>
      <div class="card-footer bg-transparent border-top d-flex justify-content-end">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i>Guardar cambios
        </button>
      </div>
    <?php endif; ?>
  </div>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>