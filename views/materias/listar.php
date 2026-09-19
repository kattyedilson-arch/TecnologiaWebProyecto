<?php
// =========================================================
// VISTA: LISTADO DE MATERIAS (views/materias/listar.php)
// ---------------------------------------------------------
// Requiere sesión. Muestra métricas (total materias,
// asignaciones a tutores y materias con tutor) y la tabla de
// materias con su carrera y el conteo de tutores asignados.
// Incluye buscador en vivo y acciones editar/eliminar.
// Variables del controlador (controllers/materias_listar.php):
//   $materias (con total_tutores y nombre_carrera)
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
$tituloPagina = 'Gestión de Materias - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';

$totalTutoresAsig = array_sum(array_column($materias, 'total_tutores'));
$totalImpartidas = count(array_filter($materias, fn($m) => $m['total_tutores'] > 0));
?>

<div class="hero-band p-4 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-journal-bookmark-fill"></i>
        <span>Materias Académicas</span>
      </h2>
      <p class="text-white-50 mb-0">Catálogo de asignaturas disponibles para tutorías académicas.</p>
    </div>
    <div class="d-flex gap-2">
      <a href="carreras_listar.php" class="btn btn-outline-light d-flex align-items-center gap-2 px-3 py-2 rounded-3">
        <i class="bi bi-mortarboard"></i>
        <span>Ver Carreras</span>
      </a>
      <a href="materias_crear.php" class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-2 shadow-sm px-3 py-2 rounded-3">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Nueva Materia</span>
      </a>
    </div>
  </div>
</div>

<!-- Métricas -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-primary bg-opacity-10 text-primary"><i class="bi bi-journal-bookmark-fill"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= count($materias) ?></h4>
          <small class="text-muted">Materias</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-indigo text-indigo"><i class="bi bi-person-video3"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalTutoresAsig ?></h4>
          <small class="text-muted">Asignaciones a tutores</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico text-ok"><i class="bi bi-check2-circle"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalImpartidas ?></h4>
          <small class="text-muted">Materias con tutor</small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card card-custom shadow-sm overflow-hidden">
  <div class="card-header bg-white py-3 border-0 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
    <div class="input-group" style="max-width: 340px;">
      <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
      <input type="text" id="buscadorMaterias" class="form-control bg-light border-start-0" placeholder="Buscar materia o carrera...">
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="tablaMaterias">
      <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4">ID</th>
          <th>Nombre de la Materia</th>
          <th>Carrera Universitaria</th>
          <th>Tutores Asignados</th>
          <th class="text-end pe-4">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($materias as $m): ?>
          <tr>
            <td class="ps-4 text-muted fw-semibold">#<?= htmlspecialchars($m['id_materia']) ?></td>
            <td>
              <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                <i class="bi bi-book text-primary opacity-75"></i>
                <?= htmlspecialchars($m['nombre_materia']) ?>
              </div>
            </td>
            <td>
              <?php if (!empty($m['nombre_carrera'])): ?>
                <span class="badge bg-light text-dark border px-3 py-1">
                  <i class="bi bi-mortarboard me-1 text-primary"></i><?= htmlspecialchars($m['nombre_carrera']) ?>
                </span>
              <?php else: ?>
                <span class="text-muted small">Sin carrera asignada</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info-subtle px-2 py-1">
                <i class="bi bi-person-video3 me-1"></i><?= $m['total_tutores'] ?> tutor(es)
              </span>
            </td>
            <td class="text-end pe-4">
              <div class="btn-group" role="group">
                <a href="materias_editar.php?id=<?= $m['id_materia'] ?>" class="btn btn-outline-primary btn-sm btn-icon" title="Editar">
                  <i class="bi bi-pencil-fill"></i>
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm btn-icon"
                        onclick="confirmarEliminacion('materias_eliminar.php?id=<?= $m['id_materia'] ?>&token=<?= tokenCsrfUrl() ?>', 'Se eliminará la materia <?= htmlspecialchars($m['nombre_materia']) ?> del catálogo.')"
                        title="Eliminar">
                  <i class="bi bi-trash-fill"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($materias)): ?>
          <tr>
            <td colspan="5" class="text-center py-5 text-muted">
              <i class="bi bi-journal-x fs-1 d-block mb-2 text-secondary"></i>
              No hay materias registradas aún.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  document.getElementById('buscadorMaterias')?.addEventListener('keyup', function() {
    const valor = this.value.toLowerCase();
    const filas = document.querySelectorAll('#tablaMaterias tbody tr');
    filas.forEach(fila => {
      fila.style.display = fila.textContent.toLowerCase().includes(valor) ? '' : 'none';
    });
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>