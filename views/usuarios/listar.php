<?php
// =========================================================
// VISTA: LISTADO DE USUARIOS (views/usuarios/listar.php)
// ---------------------------------------------------------
// Requiere sesión. Muestra métricas rápidas (total, activos,
// tutores y estudiantes) y una tabla de usuarios con buscador
// en vivo (filtra por nombre, usuario, correo o rol).
// Cada fila ofrece acciones de editar y eliminar; el botón
// eliminar está deshabilitado para el propio usuario en sesión
// y usa confirmarEliminacion() de SweetAlert2 del footer.
// Variables del controlador (controllers/usuarios_listar.php):
//   $usuarios, $totalUsuarios, $totalActivos,
//   $totalAdmins, $totalTutores, $totalEstud
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
$tituloPagina = 'Gestión de Usuarios - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';
?>

<!-- Banda de cabecera -->
<div class="hero-band p-4 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-people-fill"></i>
        <span>Usuarios del Sistema</span>
      </h2>
      <p class="text-white-50 mb-0">Administra las cuentas de administradores, tutores y estudiantes registrados.</p>
    </div>
    <a href="usuarios_crear.php" class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-2 shadow-sm px-3 py-2 rounded-3">
      <i class="bi bi-person-plus-fill"></i>
      <span>Nuevo Usuario</span>
    </a>
  </div>
</div>

<!-- Métricas rápidas -->
<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalUsuarios ?></h4>
          <small class="text-muted">Total usuarios</small>
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
        <div class="stat-ico bg-indigo text-indigo"><i class="bi bi-person-video3"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalTutores ?></h4>
          <small class="text-muted">Tutores</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico text-ok"><i class="bi bi-mortarboard"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalEstud ?></h4>
          <small class="text-muted">Estudiantes</small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card card-custom shadow-sm overflow-hidden">
  <div class="card-header bg-white py-3 border-0 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
    <div class="input-group" style="max-width: 340px;">
      <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
      <input type="text" id="buscadorUsuarios" class="form-control bg-light border-start-0" placeholder="Buscar por nombre, usuario, correo o rol...">
    </div>
    <span class="badge text-bg-light border px-3 py-2"><?= count($usuarios) ?> registro(s)</span>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="tablaUsuarios">
      <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4">Usuario</th>
          <th>Correo Electrónico</th>
          <th>Teléfono</th>
          <th>Rol Asignado</th>
          <th>Estado</th>
          <th>Fecha Registro</th>
          <th class="text-end pe-4">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <?php
            $badgeRol = 'badge-admin';
            if ($u['nombre_rol'] === 'tutor') $badgeRol = 'badge-tutor';
            if ($u['nombre_rol'] === 'estudiante') $badgeRol = 'badge-estudiante';
          ?>
          <tr>
            <td class="ps-4">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-md"><?= iniciales($u['nombre'], $u['apellido']) ?></div>
                <div>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></div>
                  <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($u['usuario']) ?></small>
                </div>
              </div>
            </td>
            <td>
              <span class="text-secondary"><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($u['correo']) ?></span>
            </td>
            <td>
              <?php if (!empty($u['telefono'])): ?>
                <span class="text-muted small"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($u['telefono']) ?></span>
              <?php else: ?>
                <span class="text-muted small">—</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge rounded-pill px-3 py-1 text-capitalize <?= $badgeRol ?>"><?= htmlspecialchars($u['nombre_rol']) ?></span>
            </td>
            <td>
              <?php if ($u['estado'] === 'activo'): ?>
                <span class="badge badge-state bg-success bg-opacity-10 text-success border border-success-subtle">
                  <i class="bi bi-check-circle me-1"></i>Activo
                </span>
              <?php else: ?>
                <span class="badge badge-state bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle">
                  <i class="bi bi-dash-circle me-1"></i>Inactivo
                </span>
              <?php endif; ?>
            </td>
            <td class="text-muted small"><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
            <td class="text-end pe-4">
              <div class="btn-group" role="group">
                <a href="usuarios_editar.php?id=<?= $u['id_usuario'] ?>" class="btn btn-outline-primary btn-sm btn-icon" title="Editar">
                  <i class="bi bi-pencil-fill"></i>
                </a>
                <button type="button" class="btn btn-outline-danger btn-sm btn-icon"
                        onclick="confirmarEliminacion('usuarios_eliminar.php?id=<?= $u['id_usuario'] ?>&token=<?= tokenCsrfUrl() ?>', 'Se eliminará al usuario <?= htmlspecialchars($u['usuario']) ?> y sus accesos.')"
                        title="Eliminar" <?= ($u['id_usuario'] == $_SESSION['id_usuario']) ? 'disabled' : '' ?>>
                  <i class="bi bi-trash-fill"></i>
                </button>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($usuarios)): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
              No hay usuarios registrados en el sistema.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  document.getElementById('buscadorUsuarios')?.addEventListener('keyup', function() {
    const valor = this.value.toLowerCase();
    const filas = document.querySelectorAll('#tablaUsuarios tbody tr');
    filas.forEach(fila => {
      const texto = fila.textContent.toLowerCase();
      fila.style.display = texto.includes(valor) ? '' : 'none';
    });
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>