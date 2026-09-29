<?php
// =========================================================
// VISTA: CATÁLOGO DE MODALIDADES (views/admin/modalidades.php)
// ---------------------------------------------------------
// Formulario de alta/edición + tabla del catálogo con estado
// de publicación. Variables esperadas del controlador:
//   $modalidades, $modalidadEditar (controllers/admin_modalidades.php)
// =========================================================
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="h5 fw-bold mb-1">Catálogo de Modalidades de Grado</h2>
    <p class="text-muted small mb-0">
      Definí qué modalidades pueden declarar los estudiantes en este periodo académico.
    </p>
  </div>
</div>

<div class="row g-4">
  <!-- Columna del formulario -->
  <div class="col-lg-4">
    <div class="card card-custom p-4">
      <h5 class="fw-bold mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-journal-plus text-primary"></i>
        <?= $modalidadEditar ? 'Editar Modalidad' : 'Nueva Modalidad' ?>
      </h5>
      <p class="text-muted small mb-3">Solo las publicadas son visibles para los estudiantes.</p>

      <form method="POST" action="/controllers/modalidades_guardar.php">
        <?php if ($modalidadEditar): ?>
          <input type="hidden" name="id" value="<?= (int)$modalidadEditar['id_modalidad'] ?>">
        <?php endif; ?>
        <?= campoCsrf() ?>

        <div class="row g-2 mb-2">
          <div class="col-4">
            <label class="form-label small fw-semibold">Código</label>
            <input type="text" name="codigo" class="form-control" placeholder="MOD-PG"
                   value="<?= e($modalidadEditar['codigo'] ?? '') ?>" required>
          </div>
          <div class="col-8">
            <label class="form-label small fw-semibold">Nombre</label>
            <input type="text" name="nombre" class="form-control" placeholder="Proyecto de Grado"
                   value="<?= e($modalidadEditar['nombre'] ?? '') ?>" required>
          </div>
        </div>

        <div class="row g-2 mb-2">
          <div class="col-6">
            <label class="form-label small fw-semibold">Tipo</label>
            <select name="tipo" class="form-select">
              <?php $tipoActual = $modalidadEditar['tipo'] ?? 'documental'; ?>
              <option value="investigacion" <?= $tipoActual === 'investigacion' ? 'selected' : '' ?>>Investigación</option>
              <option value="practica"      <?= $tipoActual === 'practica' ? 'selected' : '' ?>>Práctica</option>
              <option value="documental"    <?= $tipoActual === 'documental' ? 'selected' : '' ?>>Documental</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Estado</label>
            <select name="estado" class="form-select">
              <option value="publicada"   <?= ($modalidadEditar['estado'] ?? '') === 'publicada' ? 'selected' : '' ?>>Publicada</option>
              <option value="no_publicada" <?= ($modalidadEditar['estado'] ?? '') === 'no_publicada' ? 'selected' : '' ?>>No publicada</option>
            </select>
          </div>
        </div>

        <div class="mb-2">
          <label class="form-label small fw-semibold">Descripción</label>
          <textarea name="descripcion" class="form-control" rows="2" placeholder="De qué trata esta modalidad..."><?= e($modalidadEditar['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label small fw-semibold">Requisitos</label>
          <textarea name="requisitos" class="form-control" rows="2" placeholder="Requisitos para declarar..."><?= e($modalidadEditar['requisitos'] ?? '') ?></textarea>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary w-100">
            <i class="bi bi-check-lg me-1"></i><?= $modalidadEditar ? 'Guardar cambios' : 'Registrar modalidad' ?>
          </button>
          <?php if ($modalidadEditar): ?>
            <a href="/controllers/admin_modalidades.php" class="btn btn-light border"><i class="bi bi-x-lg"></i></a>
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
          <i class="bi bi-journal-bookmark-fill text-primary"></i> Modalidades registradas
          <span class="badge bg-light text-dark border"><?= count($modalidades) ?></span>
        </h6>
      </div>

      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>Código</th>
              <th>Nombre</th>
              <th>Tipo</th>
              <th>Estado</th>
              <th class="text-end">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($modalidades)): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">Aún no hay modalidades en el catálogo.</td></tr>
            <?php else: ?>
              <?php foreach ($modalidades as $modalidad): ?>
                <tr>
                  <td><span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"><?= e($modalidad['codigo']) ?></span></td>
                  <td>
                    <span class="fw-semibold d-block"><?= e($modalidad['nombre']) ?></span>
                    <span class="text-muted small d-block"><?= e(mb_strimwidth($modalidad['descripcion'] ?? '', 0, 70, '…')) ?></span>
                  </td>
                  <td>
                    <?php
                      $tiposTexto = ['documental' => 'Documental', 'investigacion' => 'Investigación', 'practica' => 'Práctica'];
                      $tiposClase = ['documental' => 'bg-secondary', 'investigacion' => 'bg-primary', 'practica' => 'bg-success'];
                    ?>
                    <span class="badge <?= $tiposClase[$modalidad['tipo']] ?? 'bg-secondary' ?> bg-opacity-10" style="color:#0f172a;border:1px solid #e2e8f0;"><?= $tiposTexto[$modalidad['tipo']] ?? $modalidad['tipo'] ?></span>
                  </td>
                  <td>
                    <?php if (($modalidad['estado'] ?? '') === 'publicada'): ?>
                      <span class="badge badge-state bg-success bg-opacity-10 text-success border border-success-subtle"><i class="bi bi-eye me-1"></i>Publicada</span>
                    <?php else: ?>
                      <span class="badge badge-state bg-secondary bg-opacity-10 text-secondary border"><i class="bi bi-eye-slash me-1"></i>No publicada</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <div class="d-inline-flex gap-1">
                      <a class="btn btn-sm btn-light border" href="/controllers/admin_modalidades.php?editar=<?= (int)$modalidad['id_modalidad'] ?>" title="Editar"><i class="bi bi-pencil-square"></i></a>
                      <form method="POST" action="/controllers/modalidades_eliminar.php" class="d-inline"
                            data-confirm="¿Retirar la modalidad <?= e($modalidad['nombre']) ?> del catálogo?"
                            data-confirm-title="Confirmar retiro"
                            data-confirm-text="Si ya hay declaraciones asociadas, no se podrá eliminar.">
                        <input type="hidden" name="id" value="<?= (int)$modalidad['id_modalidad'] ?>">
                        <?= campoCsrf() ?>
                        <button type="submit" class="btn btn-sm btn-light border text-danger" title="Retirar"><i class="bi bi-trash"></i></button>
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