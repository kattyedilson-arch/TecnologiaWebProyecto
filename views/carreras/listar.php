<?php
// =========================================================
// VISTA: LISTADO DE CARRERAS (views/carreras/listar.php)
// ---------------------------------------------------------
// Requiere sesión. Muestra métricas (total carreras, materias
// asociadas y estudiantes inscritos) y la tabla de carreras con
// buscador en vivo. Cada fila tiene acciones editar/eliminar.
// Variables del controlador (controllers/carreras_listar.php):
//   $carreras (con total_materias y total_estudiantes)
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('administrador');
$tituloPagina = 'Gestión de Carreras - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';

$totalMaterias  = array_sum(array_column($carreras, 'total_materias'));
$totalEstud     = array_sum(array_column($carreras, 'total_estudiantes'));
?>

<div class="hero-band p-4 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-mortarboard"></i>
        <span>Carreras Universitarias</span>
      </h2>
      <p class="text-white-50 mb-0">Programas académicos de la Universidad Privada Domingo Savio.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="materias_listar.php" class="btn btn-outline-light d-flex align-items-center gap-2 px-3 py-2 rounded-3">
        <i class="bi bi-journal-bookmark"></i>
        <span>Ver Materias</span>
      </a>
      <a href="carreras_crear.php" class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-2 shadow-sm px-3 py-2 rounded-3">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Nueva Carrera</span>
      </a>
    </div>
  </div>
</div>

<!-- Métricas -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-primary bg-opacity-10 text-primary"><i class="bi bi-buildings"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= count($carreras) ?></h4>
          <small class="text-muted">Carreras</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-indigo text-indigo"><i class="bi bi-journal-bookmark-fill"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalMaterias ?></h4>
          <small class="text-muted">Materias asociadas</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico text-ok"><i class="bi bi-people-fill"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalEstud ?></h4>
          <small class="text-muted">Estudiantes inscritos</small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card card-custom shadow-sm overflow-hidden">
  <div class="card-header bg-white py-3 border-0 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
    <div class="input-group" style="max-width: 340px;">
      <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
      <input type="text" id="buscadorCarreras" class="form-control bg-light border-start-0" placeholder="Buscar carrera...">
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="tablaCarreras">
      <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4">ID</th>
          <th>Nombre de la Carrera</th>
          <th>Total Materias</th>
          <th>Estudiantes Inscritos</th>
          <th class="text-end pe-4">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($carreras as $c): ?>
          <tr>
            <td class="ps-4 text-muted fw-semibold">#<?= htmlspecialchars($c['id_carrera']) ?></td>
            <td>
              <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                <i class="bi bi-building text-primary opacity-75"></i>
                <?= htmlspecialchars($c['nombre_carrera']) ?>
              </div>
            </td>
            <td>
              <span class="badge bg-light text-dark border px-2 py-1">
                <i class="bi bi-journal-check me-1 text-primary"></i><?= $c['total_materias'] ?> materias
              </span>
            </td>
            <td>
              <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1">
                <i class="bi bi-people me-1"></i><?= $c['total_estudiantes'] ?> estudiante(s)
              </span>
            </td>
            <td class="text-end pe-4">
              <div class="btn-group" role="group">
                <a href="carreras_editar.php?id=<?= $c['id_carrera'] ?>" class="btn btn-outline-primary btn-sm btn-icon" title="Editar">
                  <i class="bi bi-pencil-fill"></i>
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm btn-icon"
                        onclick="confirmarEliminacion('carreras_eliminar.php?id=<?= $c['id_carrera'] ?>&token=<?= tokenCsrfUrl() ?>', 'Se eliminará la carrera <?= htmlspecialchars($c['nombre_carrera']) ?>.')"
                        title="Eliminar">
                  <i class="bi bi-trash-fill"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($carreras)): ?>
          <tr>
            <td colspan="5" class="text-center py-5 text-muted">
              <i class="bi bi-mortarboard fs-1 d-block mb-2 text-secondary"></i>
              No hay carreras registradas aún.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  document.getElementById('buscadorCarreras')?.addEventListener('keyup', function() {
    const valor = this.value.toLowerCase();
    const filas = document.querySelectorAll('#tablaCarreras tbody tr');
    filas.forEach(fila => {
      fila.style.display = fila.textContent.toLowerCase().includes(valor) ? '' : 'none';
    });
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>