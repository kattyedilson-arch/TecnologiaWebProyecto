<?php
// =========================================================
// VISTA: EDICIÓN DE MATERIA (views/materias/editar.php)
// ---------------------------------------------------------
// Formulario para modificar nombre o carrera de una materia.
// $materia_actual trae los datos precargados y $carreras las
// opciones del select. Errores del servidor en $errores.
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('administrador');
$tituloPagina = 'Editar Materia - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8 col-xl-7">
    <div class="hero-band p-4 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square"></i>
            <span>Editar Materia</span>
          </h3>
          <p class="text-white-50 mb-0">Actualiza la información de la asignatura.</p>
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
        <input type="hidden" name="id_materia" value="<?= htmlspecialchars($materia_actual['id_materia']) ?>">

        <div class="mb-4">
          <label class="form-label fw-semibold text-secondary small text-uppercase">Nombre de la Materia *</label>
          <input type="text" name="nombre_materia" class="form-control rounded-3 py-2"
                 value="<?= htmlspecialchars($materia_actual['nombre_materia']) ?>"
                 minlength="3" maxlength="150" required>
          <div class="invalid-feedback">El nombre debe tener al menos 3 caracteres.</div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold text-secondary small text-uppercase">Carrera Perteneciente *</label>
          <select name="id_carrera" class="form-select rounded-3 py-2" required>
            <option value="">-- Selecciona la carrera (Obligatoria) --</option>
            <?php foreach ($carreras as $c): ?>
              <option value="<?= $c['id_carrera'] ?>" <?= ($c['id_carrera'] == $materia_actual['id_carrera']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['nombre_carrera']) ?>
              </option>
            <?php endforeach; ?>
          </select>
          <div class="invalid-feedback">Debes seleccionar la carrera a la que pertenece la materia.</div>
          <div class="form-text">No se admite la misma materia repetida dentro de una misma carrera, pero sí puede existir en varias carreras (y "Cálculo I" y "Cálculo II" son materias distintas).</div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
          <a href="materias_listar.php" class="btn btn-light px-4 py-2 rounded-3">Cancelar</a>
          <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-arrow-repeat"></i>
            <span>Actualizar Materia</span>
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