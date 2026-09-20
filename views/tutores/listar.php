<?php
// =========================================================
// VISTA: LISTADO DE TUTORES (views/tutores/listar.php)
// ---------------------------------------------------------
// Requiere sesión. Muestra métricas (tutores, materias
// asignadas y bloques horarios) y la tabla de docentes tutores
// con especialidad, contacto, materias y horarios. Cada fila
// enlaza a tutores_disponibilidad.php?id=... para gestionar
// sus horarios. Incluye buscador en vivo.
// Variables del controlador (controllers/tutores_listar.php):
//   $tutores (con total_materias y total_horarios)
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('administrador');
$tituloPagina = 'Gestión de Docentes Tutores - UPDS';
include __DIR__ . '/../layouts/header.php';

$totalMaterias = array_sum(array_column($tutores, 'total_materias'));
$totalHorarios = array_sum(array_column($tutores, 'total_horarios'));
?>

<div class="hero-band p-4 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-person-video3"></i>
        <span>Docentes Tutores Académicos</span>
      </h2>
      <p class="text-white-50 mb-0">Cuerpo docente capacitado para brindar asesorías y reforzamiento académico.</p>
    </div>
    <a href="usuarios_crear.php" class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-2 shadow-sm px-3 py-2 rounded-3">
      <i class="bi bi-person-plus-fill"></i>
      <span>Nuevo Tutor</span>
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-indigo text-indigo"><i class="bi bi-person-video3"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= count($tutores) ?></h4>
          <small class="text-muted">Docentes tutores</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-primary bg-opacity-10 text-primary"><i class="bi bi-book-fill"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalMaterias ?></h4>
          <small class="text-muted">Materias asignadas</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico text-ok"><i class="bi bi-clock-history"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalHorarios ?></h4>
          <small class="text-muted">Bloques horarios</small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card card-custom shadow-sm overflow-hidden">
  <div class="card-header bg-white py-3 border-0">
    <div class="input-group" style="max-width: 340px;">
      <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
      <input type="text" id="buscadorTutores" class="form-control bg-light border-start-0" placeholder="Buscar tutor, especialidad o correo...">
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="tablaTutores">
      <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4">Tutor Docente</th>
          <th>Especialidad</th>
          <th>Contacto</th>
          <th>Materias</th>
          <th>Horarios</th>
          <th class="text-end pe-4">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tutores as $t): ?>
          <tr>
            <td class="ps-4">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-md" style="background:linear-gradient(135deg,#4338ca,#6d28d9);"><?= iniciales($t['nombre'], $t['apellido']) ?></div>
                <div>
                  <div class="fw-bold text-dark">Prof. <?= htmlspecialchars($t['nombre'] . ' ' . $t['apellido']) ?></div>
                  <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($t['usuario']) ?></small>
                </div>
              </div>
            </td>
            <td>
              <span class="fw-medium text-secondary"><?= htmlspecialchars($t['especialidad'] ?? 'Docencia Universitaria') ?></span>
            </td>
            <td>
              <div><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($t['correo']) ?></div>
              <?php if (!empty($t['telefono'])): ?>
                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($t['telefono']) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1">
                <i class="bi bi-book me-1"></i><?= $t['total_materias'] ?> materias
              </span>
            </td>
            <td>
              <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1">
                <i class="bi bi-clock me-1"></i><?= $t['total_horarios'] ?> bloques
              </span>
            </td>
            <td class="text-end pe-4">
              <a href="tutores_disponibilidad.php?id=<?= $t['id_tutor'] ?>" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-sliders"></i>
                <span>Gestionar Horarios</span>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($tutores)): ?>
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
              No hay tutores registrados en el sistema.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  document.getElementById('buscadorTutores')?.addEventListener('keyup', function() {
    const valor = this.value.toLowerCase();
    const filas = document.querySelectorAll('#tablaTutores tbody tr');
    filas.forEach(fila => {
      fila.style.display = fila.textContent.toLowerCase().includes(valor) ? '' : 'none';
    });
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>