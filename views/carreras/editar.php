<?php
// =========================================================
// VISTA: EDICIÓN DE CARRERA (views/carreras/editar.php)
// ---------------------------------------------------------
// Formulario para renombrar una carrera. Los datos actuales
// vienen precargados en $carrera_actual y los errores de
// validación del servidor en $errores.
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('administrador');
$tituloPagina = 'Editar Carrera - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-7 col-xl-6">
    <div class="hero-band p-4 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square"></i>
            <span>Editar Carrera</span>
          </h3>
          <p class="text-white-50 mb-0">Actualiza la información del programa académico.</p>
        </div>
        <a href="carreras_listar.php" class="btn btn-light d-flex align-items-center gap-1">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>
    </div>

    <?php if (!empty($errores)): ?>
      <div class="alert alert-danger py-2 px-3 rounded-3 shadow-sm mb-4">
        <ul class="mb-0 ps-3 small">
          <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="card card-custom p-4 p-md-5">
      <form method="POST" autocomplete="off" class="needs-validation" novalidate>
      <?= campoCsrf() ?>
        <input type="hidden" name="id_carrera" value="<?= htmlspecialchars($carrera_actual['id_carrera']) ?>">

        <div class="mb-4">
          <label class="form-label fw-semibold text-secondary small text-uppercase">Nombre de la Carrera *</label>
          <input type="text" name="nombre_carrera" class="form-control rounded-3 py-2"
                 value="<?= htmlspecialchars($carrera_actual['nombre_carrera']) ?>"
                 minlength="5" maxlength="150" list="lista-carreras-globales" required>
          <datalist id="lista-carreras-globales">
            <?php foreach (obtenerCarrerasGlobales() as $carrera): ?>
              <option value="<?= htmlspecialchars($carrera) ?>"></option>
            <?php endforeach; ?>
          </datalist>
          <div class="invalid-feedback">El nombre debe tener al menos 5 caracteres.</div>
          <div class="form-text">Escribe y aparecerán sugerencias de carreras reales reconocidas.</div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
          <a href="carreras_listar.php" class="btn btn-light px-4 py-2 rounded-3">Cancelar</a>
          <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-arrow-repeat"></i>
            <span>Actualizar Carrera</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  (() => {
    const form = document.querySelector('.needs-validation');
    if (!form) return;
    form.addEventListener('submit', (e) => {
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  })();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>