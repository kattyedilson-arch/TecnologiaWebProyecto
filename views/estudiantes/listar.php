<?php
// =========================================================
// VISTA: LISTADO DE ESTUDIANTES (views/estudiantes/listar.php)
// ---------------------------------------------------------
// Requiere sesión. Muestra métricas (estudiantes, cuentas
// activas y tutorías solicitadas) y la tabla con la ficha
// académica: R.U., carrera, semestre, contacto y número de
// sesiones solicitadas. Incluye buscador en vivo.
// Variables del controlador (controllers/estudiantes_listar.php):
//   $estudiantes (con registro_universitario, nombre_carrera,
//   semestre, correo, telefono y total_tutorias)
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('administrador');
$tituloPagina = 'Gestión de Estudiantes - UPDS';
include __DIR__ . '/../layouts/header.php';

$totalTutorias = array_sum(array_column($estudiantes, 'total_tutorias'));
$totalActivos = count(array_filter($estudiantes, fn($e) => $e['estado'] === 'activo'));
?>

<div class="hero-band p-4 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-mortarboard"></i>
        <span>Estudiantes Registrados</span>
      </h2>
      <p class="text-white-50 mb-0">Listado de alumnos habilitados para solicitar tutorías académicas.</p>
    </div>
    <a href="usuarios_crear.php" class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-2 shadow-sm px-3 py-2 rounded-3">
      <i class="bi bi-person-plus-fill"></i>
      <span>Nuevo Estudiante</span>
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico text-ok"><i class="bi bi-mortarboard"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= count($estudiantes) ?></h4>
          <small class="text-muted">Estudiantes</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-success bg-opacity-10 text-success"><i class="bi bi-person-check-fill"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalActivos ?></h4>
          <small class="text-muted">Cuentas activas</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-primary bg-opacity-10 text-primary"><i class="bi bi-calendar-event"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalTutorias ?></h4>
          <small class="text-muted">Tutorías solicitadas</small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card card-custom shadow-sm overflow-hidden">
  <div class="card-header bg-white py-3 border-0">
    <div class="input-group" style="max-width: 340px;">
      <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
      <input type="text" id="buscadorEstudiantes" class="form-control bg-light border-start-0" placeholder="Buscar por nombre, carrera, R.U. o correo...">
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="tablaEstudiantes">
      <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4">Estudiante</th>
          <th>Reg. Universitario</th>
          <th>Carrera</th>
          <th>Semestre</th>
          <th>Contacto</th>
          <th>Tutorías Solicitadas</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($estudiantes as $e): ?>
          <tr>
            <td class="ps-4">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-md" style="background:linear-gradient(135deg,#047857,#059669);"><?= iniciales($e['nombre'], $e['apellido']) ?></div>
                <div>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) ?></div>
                  <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($e['usuario']) ?></small>
                </div>
              </div>
            </td>
            <td>
              <span class="badge bg-light text-dark border px-2 py-1 font-monospace"><?= htmlspecialchars($e['registro_universitario'] ?? 'S/R') ?></span>
            </td>
            <td>
              <span class="fw-medium text-dark"><?= htmlspecialchars($e['nombre_carrera']) ?></span>
            </td>
            <td>
              <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2 py-1">
                Semestre <?= $e['semestre'] ?>
              </span>
            </td>
            <td>
              <div><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($e['correo']) ?></div>
              <?php if (!empty($e['telefono'])): ?>
                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($e['telefono']) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1">
                <i class="bi bi-calendar-event me-1"></i><?= $e['total_tutorias'] ?> sesiones
              </span>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($estudiantes)): ?>
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
              No hay estudiantes registrados con ficha académica aún.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  document.getElementById('buscadorEstudiantes')?.addEventListener('keyup', function() {
    const valor = this.value.toLowerCase();
    const filas = document.querySelectorAll('#tablaEstudiantes tbody tr');
    filas.forEach(fila => {
      fila.style.display = fila.textContent.toLowerCase().includes(valor) ? '' : 'none';
    });
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>