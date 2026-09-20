<?php
// =========================================================
// VISTA: GESTIÓN DE TUTOR (views/tutores/disponibilidad.php)
// ---------------------------------------------------------
// Panel de configuración del docente tutor, con tres bloques:
//   1) Horarios: lista los bloques semanales existentes (con
//      opción de eliminarlos) y formulario para agregar uno
//      nuevo (día + hora inicio/fin). Formulario accion=agregar_horario.
//   2) Materias: checkboxes de todas las materias, marcando las
//      ya asignadas. Formulario accion=guardar_materias.
//   3) Perfil profesional: especialidad y biografía.
//      Formulario accion=actualizar_perfil.
// Variables del controlador (controllers/tutores_disponibilidad.php):
//   $tutor, $idTutor, $disponibilidades, $todasMaterias,
//   $idsMateriasAsignadas, $errores
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
$tituloPagina = 'Gestión de Tutor y Horarios - UPDS';
include __DIR__ . '/../layouts/header.php';
$rolAux = $_SESSION['rol'] ?? 'administrador';
$volverUrl = ($rolAux === 'tutor') ? '../views/tutor/panel.php' : 'tutores_listar.php';
?>

<div class="hero-band p-4 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-calendar-range"></i>
        <span>Horarios y Especialidades</span>
      </h2>
      <p class="text-white-50 mb-0">Prof. <?= htmlspecialchars($tutor['nombre'] . ' ' . $tutor['apellido']) ?> &bull; Configura tu disponibilidad semanal y las materias que dominas.</p>
    </div>
    <a href="<?= $volverUrl ?>" class="btn btn-light d-flex align-items-center gap-1">
      <i class="bi bi-arrow-left"></i> Volver
    </a>
  </div>
</div>

<?php if (!empty($errores)): ?>
  <div class="alert alert-danger py-2 px-3 rounded-3 shadow-sm mb-4">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Corrige los siguientes errores:</div>
    <ul class="mb-0 ps-3 small">
      <?php foreach ($errores as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div class="row g-4">
  <!-- Columna 1: Horarios de Disponibilidad -->
  <div class="col-lg-6">
    <div class="card card-custom p-4 h-100">
      <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-clock-history text-primary"></i>
        <span>Bloques de Horarios Semanales</span>
        <span class="badge text-bg-light border ms-auto"><?= count($disponibilidades) ?> bloque(s)</span>
      </h5>

      <!-- Lista de horarios actuales -->
      <div class="mb-4">
        <?php if (!empty($disponibilidades)): ?>
          <div class="list-group list-group-flush">
            <?php foreach ($disponibilidades as $d): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-indigo text-indigo px-2 py-1"><?= htmlspecialchars($d['dia_semana']) ?></span>
                  <span class="fw-semibold text-dark"><i class="bi bi-clock me-1 text-muted"></i><?= substr($d['hora_inicio'], 0, 5) ?> - <?= substr($d['hora_fin'], 0, 5) ?></span>
                </div>
                <a href="tutores_disponibilidad.php?id=<?= $idTutor ?>&eliminar_horario=<?= $d['id_disponibilidad'] ?>&token=<?= tokenCsrfUrl() ?>"
                   class="btn btn-outline-danger btn-sm btn-icon" title="Eliminar">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="p-3 bg-light rounded-3 text-center text-muted small">
            <i class="bi bi-calendar-x d-block fs-3 mb-1"></i>
            No hay horarios registrados. Agrega uno a continuación.
          </div>
        <?php endif; ?>
      </div>

      <!-- Formulario para agregar horario -->
      <div class="p-3 bg-light rounded-3 border">
        <h6 class="fw-bold mb-2 small text-uppercase text-secondary"><i class="bi bi-plus-circle me-1"></i>Agregar Nuevo Bloque</h6>
        <form method="POST" class="row g-2 needs-validation" novalidate>
          <?= campoCsrf() ?>
          <input type="hidden" name="accion" value="agregar_horario">
          <div class="col-12">
            <select name="dia_semana" class="form-select form-select-sm" required>
              <option value="" disabled selected>Selecciona día de la semana...</option>
              <?php foreach (['Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'] as $dia): ?>
                <option value="<?= $dia ?>"><?= $dia ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <label class="small text-muted">Hora Inicio</label>
            <input type="time" name="hora_inicio" class="form-control form-control-sm" required>
          </div>
          <div class="col-6">
            <label class="small text-muted">Hora Fin</label>
            <input type="time" name="hora_fin" class="form-control form-control-sm" required>
          </div>
          <div class="col-12 mt-2">
            <button type="submit" class="btn btn-primary btn-sm w-100">
              <i class="bi bi-plus-lg me-1"></i> Agregar Horario
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Columna 2: Materias que domina y Perfil -->
  <div class="col-lg-6">
    <div class="card card-custom p-4 mb-4">
      <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-journal-check text-primary"></i>
        <span>Materias que Imparte</span>
        <span class="badge text-bg-light border ms-auto"><?= count($idsMateriasAsignadas) ?> seleccionada(s)</span>
      </h5>
      <form method="POST">
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="guardar_materias">
        <div class="mb-3" style="max-height: 220px; overflow-y: auto;">
          <?php foreach ($todasMaterias as $mat): ?>
            <div class="form-check py-1">
              <input class="form-check-input" type="checkbox" name="materias[]" value="<?= $mat['id_materia'] ?>" id="mat_<?= $mat['id_materia'] ?>"
                <?= in_array($mat['id_materia'], $idsMateriasAsignadas) ? 'checked' : '' ?>>
              <label class="form-check-label small fw-medium" for="mat_<?= $mat['id_materia'] ?>">
                <?= htmlspecialchars($mat['nombre_materia']) ?>
                <span class="text-muted">(<?= htmlspecialchars($mat['nombre_carrera'] ?? 'General') ?>)</span>
              </label>
            </div>
          <?php endforeach; ?>
          <?php if (empty($todasMaterias)): ?>
            <p class="text-muted small text-center py-2 mb-0">No hay materias registradas en el sistema.</p>
          <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-outline-primary btn-sm w-100">
          <i class="bi bi-save me-1"></i> Guardar Materias Asignadas
        </button>
      </form>
    </div>

    <!-- Perfil docente -->
    <div class="card card-custom p-4">
      <h5 class="fw-bold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-person-lines-fill text-primary"></i>
        <span>Perfil Profesional</span>
      </h5>
      <form method="POST" class="needs-validation" novalidate>
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="actualizar_perfil">
        <div class="mb-3">
          <label class="form-label small text-muted fw-semibold">Especialidad</label>
          <input type="text" name="especialidad" class="form-control form-control-sm" maxlength="150"
                 value="<?= htmlspecialchars($tutor['especialidad'] ?? '') ?>" placeholder="Ej: Bases de Datos, Redes...">
        </div>
        <div class="mb-3">
          <label class="form-label small text-muted fw-semibold">Biografía / Presentación</label>
          <textarea name="biografia" class="form-control form-control-sm" rows="2" maxlength="1000"><?= htmlspecialchars($tutor['biografia'] ?? '') ?></textarea>
        </div>
        <button type="submit" class="btn btn-light btn-sm w-100 border">
          <i class="bi bi-check2 me-1"></i> Actualizar Perfil
        </button>
      </form>
    </div>
  </div>
</div>

<script>
  (() => {
    document.querySelectorAll('.needs-validation').forEach(form => {
      form.addEventListener('submit', (e) => {
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add('was-validated');
      }, false);
    });
  })();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>