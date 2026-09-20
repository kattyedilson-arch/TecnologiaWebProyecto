<?php
// =========================================================
// VISTA: NUEVA CARRERA (views/carreras/crear.php)
// ---------------------------------------------------------
// Formulario de alta de carreras con validación HTML5
// (minlength=4, maxlength=150) y la clase needs-validation
// de Bootstrap. Los errores del servidor llegan en $errores.
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
$tituloPagina = 'Nueva Carrera - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-7 col-xl-6">
    <div class="hero-band p-4 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Nueva Carrera</span>
          </h3>
          <p class="text-white-50 mb-0">Registra un nuevo programa académico.</p>
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
        <div class="mb-4">
          <label class="form-label fw-semibold text-secondary small text-uppercase">Nombre de la Carrera *</label>
          <input type="text" name="nombre_carrera" class="form-control rounded-3 py-2"
                 value="<?= htmlspecialchars($_POST['nombre_carrera'] ?? '') ?>"
                 minlength="4" maxlength="150" placeholder="Ej: Ingeniería de Sistemas, Derecho..." required autofocus>
          <div class="invalid-feedback">El nombre debe tener al menos 4 caracteres.</div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
          <a href="carreras_listar.php" class="btn btn-light px-4 py-2 rounded-3">Cancelar</a>
          <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-save"></i>
            <span>Guardar Carrera</span>
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