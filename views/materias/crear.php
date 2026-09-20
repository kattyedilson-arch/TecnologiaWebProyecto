<?php
// =========================================================
// VISTA: NUEVA MATERIA (views/materias/crear.php)
// ---------------------------------------------------------
// Formulario de alta de materias con nombre (min 3, max 150)
// y carrera opcional seleccionable. Usa validación HTML5 y
// necesita-validation de Bootstrap. Errores en $errores.
// Variables: $carreras (opciones del <select>)
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
$tituloPagina = 'Registrar Materia - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8 col-xl-7">
    <div class="hero-band p-4 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-plus-circle-fill"></i>
            <span>Nueva Materia</span>
          </h3>
          <p class="text-white-50 mb-0">Añade una asignatura al catálogo de tutorías.</p>
        </div>
        <a href="materias_listar.php" class="btn btn-light d-flex align-items-center gap-1">
          <i class="bi bi-arrow-left"></i> Volver al listado
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
          <label class="form-label fw-semibold text-secondary small text-uppercase">Nombre de la Materia *</label>
          <input type="text" name="nombre_materia" class="form-control rounded-3 py-2"
                 value="<?= htmlspecialchars($_POST['nombre_materia'] ?? '') ?>"
                 minlength="3" maxlength="150" placeholder="Ej: Redes de Computadoras, Algoritmos..." required autofocus>
          <div class="invalid-feedback">El nombre debe tener al menos 3 caracteres.</div>
        </div>

        <div class="mb-4">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label fw-semibold text-secondary small text-uppercase mb-0">Carrera Perteneciente *</label>
            <a href="carreras_crear.php" class="small text-decoration-none">+ Nueva carrera</a>
          </div>
          <select name="id_carrera" class="form-select rounded-3 py-2" required>
            <option value="">-- Selecciona una carrera --</option>
            <?php foreach ($carreras as $c): ?>
              <option value="<?= $c['id_carrera'] ?>" <?= (isset($_POST['id_carrera']) && $_POST['id_carrera'] == $c['id_carrera']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nombre_carrera']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="invalid-feedback">Debes seleccionar una carrera perteneciente.</div>
          <div class="form-text">Asocia la materia para que los estudiantes de esa carrera la encuentren fácilmente.</div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
          <a href="materias_listar.php" class="btn btn-light px-4 py-2 rounded-3">Cancelar</a>
          <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-save"></i>
            <span>Guardar Materia</span>
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