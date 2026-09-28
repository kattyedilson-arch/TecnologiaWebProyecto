<?php
// =========================================================
// VISTA: GESTIÓN DE TUTOR (views/tutores/disponibilidad.php)
// ---------------------------------------------------------
// Panel de configuración del docente tutor, con tres bloques:
//   1) Turnos: lista los turnos asignados (turno + materia).
//      Solo el admin puede agregar/eliminar turnos.
//   2) Materias: checkboxes de todas las materias, marcando las
//      ya asignadas. Formulario accion=guardar_materias.
//   3) Perfil profesional: especialidad y biografía.
//      Formulario accion=actualizar_perfil.
// Variables del controlador:
//   $tutor, $idTutor, $disponibilidades, $materiasAsignadas,
//   $todasMaterias, $idsMateriasAsignadas, $turnos, $errores
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('administrador', 'tutor');
$tituloPagina = 'Gestión de Tutor y Horarios - UPDS';
include __DIR__ . '/../layouts/header.php';
$rolAux = $_SESSION['rol'] ?? 'administrador';
$esAdmin = ($rolAux === 'administrador');
$volverUrl = $esAdmin ? 'tutores_listar.php' : '../views/tutor/panel.php';
?>

<!-- Banda de cabecera -->
<div class="hero-band p-4 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge text-bg-light text-dark border px-3 py-1" style="font-size:.68rem; letter-spacing:.6px; text-transform:uppercase;">
          <i class="bi bi-mortarboard me-1"></i> Configuración Docente
        </span>
      </div>
      <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-calendar-range"></i>
        <span>Horarios y Especialidades</span>
      </h2>
      <p class="text-white-50 mb-0">Prof. <?= htmlspecialchars($tutor['nombre'] . ' ' . $tutor['apellido']) ?> &bull; <?= $esAdmin ? 'Gestiona la disponibilidad del tutor por turnos.' : 'Consulta tu disponibilidad y las materias que dominas.' ?></p>
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
  <!-- Columna 1: Turnos Asignados -->
  <div class="col-lg-6">
    <div class="card card-custom p-4 h-100">
      <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
        <span class="stat-ico bg-indigo text-indigo" style="width:38px; height:38px; font-size:1rem;"><i class="bi bi-clock-history"></i></span>
        <div class="flex-grow-1">
          <h5 class="fw-bold mb-0">Turnos Asignados</h5>
          <small class="text-muted"><?= $esAdmin ? 'Materias y turnos asignados al tutor.' : 'Tus materias y turnos asignados por el administrador.' ?></small>
        </div>
        <span class="badge text-bg-light border px-3 py-2"><?= count($disponibilidades) ?> turno(s)</span>
      </div>

      <!-- Lista de turnos actuales -->
      <div class="<?= $esAdmin ? 'mb-4' : '' ?>">
        <?php if (!empty($disponibilidades)): ?>
          <div class="list-group list-group-flush">
            <?php foreach ($disponibilidades as $d): ?>
              <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                <div class="d-flex align-items-center gap-2">
                  <div>
                    <span class="fw-semibold text-dark">
                      <i class="bi bi-clock me-1 text-muted"></i>
                      <?= htmlspecialchars($d['nombre_turno']) ?>
                      <small class="text-muted">(<?= substr($d['turno_hora_inicio'], 0, 5) ?> - <?= substr($d['turno_hora_fin'], 0, 5) ?>)</small>
                    </span>
                    <div class="small text-muted"><i class="bi bi-book me-1"></i><?= htmlspecialchars($d['nombre_materia'] ?? 'Materia no asignada') ?></div>
                  </div>
                </div>
                <?php if ($esAdmin): ?>
                  <a href="tutores_disponibilidad.php?id=<?= $idTutor ?>&eliminar_horario=<?= $d['id_disponibilidad'] ?>&token=<?= tokenCsrfUrl() ?>"
                     class="btn btn-outline-danger btn-sm btn-icon" title="Eliminar">
                    <i class="bi bi-trash"></i>
                  </a>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="p-3 bg-light rounded-3 text-center text-muted small">
            <i class="bi bi-calendar-x d-block fs-3 mb-1"></i>
            <?= $esAdmin ? 'No hay turnos asignados. Agrega uno a continuación.' : 'No tienes turnos asignados aún.' ?>
          </div>
        <?php endif; ?>
      </div>

      <?php if ($esAdmin): ?>
      <!-- Formulario para agregar turno (SOLO ADMIN) -->
      <div class="p-3 bg-light rounded-3 border">
        <h6 class="fw-bold mb-2 small text-uppercase text-secondary"><i class="bi bi-plus-circle me-1"></i>Asignar Nuevo Turno</h6>
        <form method="POST" class="row g-2 needs-validation" novalidate>
          <?= campoCsrf() ?>
          <input type="hidden" name="accion" value="agregar_horario">
          <div class="col-12">
            <label class="small text-muted">Materia que impartirá</label>
            <select name="id_materia" class="form-select form-select-sm" required <?= empty($materiasAsignadas) ? 'disabled' : '' ?>>
              <option value="" disabled selected>Selecciona la materia...</option>
              <?php foreach ($materiasAsignadas as $mat): ?>
                <option value="<?= $mat['id_materia'] ?>"><?= htmlspecialchars($mat['nombre_materia']) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($materiasAsignadas)): ?>
              <div class="form-text text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Primero asigna las materias que imparte (panel de la derecha).</div>
            <?php endif; ?>
          </div>
          <div class="col-12">
            <label class="small text-muted">Turno</label>
            <select name="id_turno" class="form-select form-select-sm" required>
              <option value="" disabled selected>Selecciona el turno...</option>
              <?php foreach ($turnos as $turno): ?>
                <option value="<?= $turno['id_turno'] ?>"><?= htmlspecialchars($turno['nombre_turno']) ?> (<?= substr($turno['hora_inicio'], 0, 5) ?> - <?= substr($turno['hora_fin'], 0, 5) ?>)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 mt-2">
            <button type="submit" class="btn btn-primary btn-sm w-100" <?= empty($materiasAsignadas) ? 'disabled' : '' ?>>
              <i class="bi bi-plus-lg me-1"></i> Asignar Turno
            </button>
          </div>
        </form>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Columna 2: Materias que domina y Perfil -->
  <div class="col-lg-6">
    <div class="card card-custom p-4 mb-4">
      <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
        <span class="stat-ico bg-primary bg-opacity-10 text-primary" style="width:38px; height:38px; font-size:1rem;"><i class="bi bi-journal-check"></i></span>
        <div class="flex-grow-1">
          <h5 class="fw-bold mb-0">Materias que Imparte</h5>
          <small class="text-muted"><?= $esAdmin ? 'Selecciona las asignaturas que dominas para tutoría.' : 'Materias asignadas para tutoría.' ?></small>
        </div>
        <span class="badge text-bg-light border px-3 py-2"><?= count($idsMateriasAsignadas) ?> seleccionada(s)</span>
      </div>
      <form method="POST">
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="guardar_materias">
        <div class="mb-3" style="max-height: 220px; overflow-y: auto;">
          <?php foreach ($todasMaterias as $mat): ?>
            <div class="form-check py-1">
              <input class="form-check-input" type="checkbox" name="materias[]" value="<?= $mat['id_materia'] ?>" id="mat_<?= $mat['id_materia'] ?>"
                <?= in_array($mat['id_materia'], $idsMateriasAsignadas) ? 'checked' : '' ?> <?= !$esAdmin ? 'disabled' : '' ?>>
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
        <?php if ($esAdmin): ?>
          <button type="submit" class="btn btn-outline-primary btn-sm w-100">
            <i class="bi bi-save me-1"></i> Guardar Materias Asignadas
          </button>
        <?php endif; ?>
      </form>
    </div>

    <!-- Perfil docente -->
    <div class="card card-custom p-4">
      <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
        <span class="stat-ico bg-success bg-opacity-10 text-success" style="width:38px; height:38px; font-size:1rem;"><i class="bi bi-person-lines-fill"></i></span>
        <div class="flex-grow-1">
          <h5 class="fw-bold mb-0">Perfil Profesional</h5>
          <small class="text-muted">Especialidad y presentación ante los estudiantes.</small>
        </div>
      </div>
      <form method="POST" class="needs-validation" novalidate>
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="actualizar_perfil">
        <div class="mb-3">
          <label class="form-label small text-muted fw-semibold">Especialidad</label>
          <input type="text" name="especialidad" class="form-control form-control-sm" maxlength="150"
                 value="<?= htmlspecialchars($tutor['especialidad'] ?? '') ?>" placeholder="Ej: Bases de Datos, Redes..." <?= !$esAdmin ? 'disabled' : '' ?>>
        </div>
        <div class="mb-3">
          <label class="form-label small text-muted fw-semibold">Biografía / Presentación</label>
          <textarea name="biografia" class="form-control form-control-sm" rows="2" maxlength="1000" <?= !$esAdmin ? 'disabled' : '' ?>><?= htmlspecialchars($tutor['biografia'] ?? '') ?></textarea>
        </div>
        <?php if ($esAdmin): ?>
          <button type="submit" class="btn btn-light btn-sm w-100 border">
            <i class="bi bi-check2 me-1"></i> Actualizar Perfil
          </button>
        <?php endif; ?>
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
