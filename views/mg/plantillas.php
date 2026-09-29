<?php
// =========================================================
// VISTA: PLANTILLAS DE DOCUMENTOS MG (views/mg/plantillas.php)
// ---------------------------------------------------------
// HU-027. Listado de plantillas y editor de la plantilla
// seleccionada (HTML con {{variables}}). Editar desde pantalla
// sin tocar código.
// Variables: $plantillas, $plantillaEditar
// (controllers/mg_plantillas.php)
// =========================================================
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="h5 fw-bold mb-1">Plantillas de documentos MG</h2>
    <p class="text-muted small mb-0">
      Edita el contenido de cartas y citaciones sin tocar código (HU-027).
    </p>
  </div>
</div>

<div class="row g-4">
  <!-- Listado -->
  <div class="col-lg-4">
    <div class="card card-custom p-3">
      <h6 class="fw-bold mb-3"><i class="bi bi-journal-text text-primary me-1"></i> Plantillas</h6>
      <?php if (empty($plantillas)): ?>
        <p class="text-muted small mb-0">No hay plantillas registradas.</p>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($plantillas as $pl): ?>
            <a href="/controllers/mg_plantillas.php?editar=<?= (int)$pl['id_plantilla'] ?>"
               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $plantillaEditar && (int)$plantillaEditar['id_plantilla'] === (int)$pl['id_plantilla'] ? 'active' : '' ?>">
              <span>
                <span class="d-block small fw-semibold"><?= e($pl['nombre']) ?></span>
                <span class="d-block small opacity-75"><?= e($pl['codigo']) ?> · v<?= (int)$pl['version'] ?></span>
              </span>
              <i class="bi bi-pencil-square"></i>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Editor -->
  <div class="col-lg-8">
    <?php if ($plantillaEditar): ?>
      <?php $variablesPlt = array_values((new DocumentoModel($pdo))->listarVariables($plantillaEditar['cuerpo_html'])); ?>
      <div class="card card-custom p-4">
        <div class="d-flex justify-content-between align-items-start mb-3">
          <div>
            <h5 class="fw-bold mb-1"><?= e($plantillaEditar['nombre']) ?></h5>
            <span class="badge bg-light text-dark border"><?= e($plantillaEditar['codigo']) ?></span>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle">v<?= (int)$plantillaEditar['version'] ?></span>
          </div>
          <a class="btn btn-sm btn-light border" href="/controllers/mg_documento_ver.php?id=<?= (int)($plantillaEditar['id_plantilla']) ?>" disabled style="display:none"></a>
        </div>

        <?php if ($variablesPlt): ?>
          <div class="alert alert-light border small mb-3">
            <strong>Variables disponibles:</strong>
            <?php foreach ($variablesPlt as $v): ?>
              <code>{{<?= e($v) ?>}}</code>
            <?php endforeach; ?>
            <span class="d-block text-muted mt-1">Se reemplazan escapadas al generar el documento.</span>
          </div>
        <?php endif; ?>

        <form method="POST" action="/controllers/mg_plantilla_guardar.php">
          <input type="hidden" name="id_plantilla" value="<?= (int)$plantillaEditar['id_plantilla'] ?>">
          <?= campoCsrf() ?>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nombre</label>
            <input type="text" name="nombre" class="form-control" value="<?= e($plantillaEditar['nombre']) ?>" required>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Contenido HTML</label>
            <textarea name="cuerpo_html" class="form-control font-monospace" rows="16" spellcheck="false" required><?= e($plantillaEditar['cuerpo_html']) ?></textarea>
          </div>
          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar plantilla</button>
            <?php if ($plantillaEditar['actualizado_nombre']): ?>
              <span class="text-muted small align-self-center ms-auto">Editada por <?= e($plantillaEditar['actualizado_nombre']) ?> · <?= date('d/m/Y H:i', strtotime($plantillaEditar['fecha_actualizacion'])) ?></span>
            <?php endif; ?>
          </div>
        </form>
      </div>
    <?php else: ?>
      <div class="card card-custom p-4 text-center text-muted">
        <i class="bi bi-journal-text fs-1 d-block mb-2"></i>
        <p class="mb-0">Selecciona una plantilla del listado para editarla.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>