<?php
// =========================================================
// VISTA: GESTIÓN DE TUTORÍAS (views/tutorias/listar.php)
// ---------------------------------------------------------
// Vista de administración de todas las tutorías. Incluye:
//   - Métricas por estado (total, pendientes, confirmadas,
//     realizadas).
//   - Botones de filtro por estado (?estado=...) que resaltan
//     el filtro activo según $filtroEstado.
//   - Tabla con fecha/horario, materia-carrera, estudiante,
//     docente, modalidad, estado y calificación (estrellas).
//   - Acciones contextuales: confirmar (pendiente), marcar
//     realizada (confirmada) y cancelar (con confirmación),
//     que apuntan a tutorias_cambiar_estado.php.
// Variables del controlador (controllers/tutorias_listar.php):
//   $tutorias, $metricas, $filtroEstado
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('administrador');
$tituloPagina = 'Gestión de Tutorías - UPDS';
include __DIR__ . '/../layouts/header.php';
?>

<div class="hero-band p-4 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-calendar-check-fill"></i>
        <span>Registro General de Tutorías</span>
      </h2>
      <p class="text-white-50 mb-0">Supervisión académica de sesiones solicitadas, agendadas y completadas.</p>
    </div>
  </div>
</div>

<!-- Métricas de Tutorías -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-2">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-2">
        <div class="stat-ico bg-primary bg-opacity-10 text-primary"><i class="bi bi-calendar-week"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $metricas['total'] ?? 0 ?></h4>
          <small class="text-muted">Total</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-2">
        <div class="stat-ico bg-warning bg-opacity-25 text-warning"><i class="bi bi-hourglass-split"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-warning"><?= $metricas['pendientes'] ?? 0 ?></h4>
          <small class="text-muted">Pendientes</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-2">
        <div class="stat-ico bg-info bg-opacity-10 text-info"><i class="bi bi-check2"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-info"><?= $metricas['confirmadas'] ?? 0 ?></h4>
          <small class="text-muted">Confirmadas</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-2">
        <div class="stat-ico bg-indigo bg-opacity-10 text-indigo"><i class="bi bi-play-circle"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-indigo"><?= $metricas['en_proceso'] ?? 0 ?></h4>
          <small class="text-muted">En Proceso</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-2">
        <div class="stat-ico bg-success bg-opacity-10 text-success"><i class="bi bi-check2-circle"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-success"><?= $metricas['realizadas'] ?? 0 ?></h4>
          <small class="text-muted">Realizadas</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-2">
        <div class="stat-ico bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-danger"><?= $metricas['canceladas'] ?? 0 ?></h4>
          <small class="text-muted">Canceladas</small>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Filtros de Estado -->
<div class="d-flex flex-wrap gap-2 mb-3">
  <a href="tutorias_listar.php" class="btn btn-sm <?= empty($filtroEstado) ? 'btn-dark' : 'btn-outline-secondary' ?> rounded-pill px-3">Todas</a>
  <a href="tutorias_listar.php?estado=pendiente" class="btn btn-sm <?= $filtroEstado === 'pendiente' ? 'btn-warning text-dark fw-bold' : 'btn-outline-warning text-dark' ?> rounded-pill px-3">
    <i class="bi bi-hourglass-split me-1"></i>Pendientes
  </a>
  <a href="tutorias_listar.php?estado=confirmada" class="btn btn-sm <?= $filtroEstado === 'confirmada' ? 'btn-info text-white fw-bold' : 'btn-outline-info' ?> rounded-pill px-3">
    <i class="bi bi-check2 me-1"></i>Confirmadas
  </a>
  <a href="tutorias_listar.php?estado=en_proceso" class="btn btn-sm <?= $filtroEstado === 'en_proceso' ? 'btn-indigo text-white fw-bold' : 'btn-outline-indigo text-indigo' ?> rounded-pill px-3">
    <i class="bi bi-play-circle me-1"></i>En Proceso
  </a>
  <a href="tutorias_listar.php?estado=realizada" class="btn btn-sm <?= $filtroEstado === 'realizada' ? 'btn-success fw-bold' : 'btn-outline-success' ?> rounded-pill px-3">
    <i class="bi bi-check-circle me-1"></i>Realizadas
  </a>
  <a href="tutorias_listar.php?estado=cancelada" class="btn btn-sm <?= $filtroEstado === 'cancelada' ? 'btn-danger fw-bold' : 'btn-outline-danger' ?> rounded-pill px-3">
    <i class="bi bi-x-circle me-1"></i>Canceladas
  </a>
</div>

<div class="card card-custom shadow-sm overflow-hidden">
  <div class="card-header bg-white py-3 border-0">
    <div class="input-group" style="max-width: 340px;">
      <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
      <input type="text" id="buscadorTutorias" class="form-control bg-light border-start-0" placeholder="Buscar por alumno, docente o materia...">
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="tablaTutorias">
      <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4">Fecha y Horario</th>
          <th>Materia y Carrera</th>
          <th>Estudiante</th>
          <th>Docente Tutor</th>
          <th>Modalidad</th>
          <th>Tipo</th>
          <th>Estado</th>
          <th class="text-end pe-4">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tutorias as $t): ?>
          <?php
            $badgeEstado = 'bg-warning text-dark border-warning';
            if ($t['estado'] === 'confirmada') $badgeEstado = 'bg-info bg-opacity-10 text-info-emphasis border-info-subtle';
            if ($t['estado'] === 'en_proceso') $badgeEstado = 'bg-indigo bg-opacity-10 text-indigo border-indigo-subtle';
            if ($t['estado'] === 'realizada') $badgeEstado = 'bg-success bg-opacity-10 text-success border-success-subtle';
            if ($t['estado'] === 'cancelada') $badgeEstado = 'bg-danger bg-opacity-10 text-danger border-danger-subtle';
          ?>
          <tr>
            <td class="ps-4">
              <div class="fw-bold text-dark"><?= date('d/m/Y', strtotime($t['fecha'])) ?></div>
              <small class="text-muted"><i class="bi bi-clock me-1"></i><?= substr($t['hora_inicio'], 0, 5) ?> - <?= substr($t['hora_fin'], 0, 5) ?></small>
            </td>
            <td>
              <div class="fw-semibold text-primary"><?= htmlspecialchars($t['nombre_materia']) ?></div>
              <small class="text-muted"><?= htmlspecialchars($t['nombre_carrera'] ?? 'General') ?></small>
            </td>
            <td>
              <div class="fw-medium text-dark"><?= htmlspecialchars($t['est_nombre'] . ' ' . $t['est_apellido']) ?></div>
              <small class="text-muted"><?= htmlspecialchars($t['est_correo']) ?></small>
            </td>
            <td>
              <div class="fw-medium text-dark">Prof. <?= htmlspecialchars($t['tut_nombre'] . ' ' . $t['tut_apellido']) ?></div>
              <small class="text-muted"><?= htmlspecialchars($t['tut_correo']) ?></small>
            </td>
            <td>
              <?php if ($t['modalidad'] === 'virtual'): ?>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1">
                  <i class="bi bi-camera-video me-1"></i>Virtual
                </span>
              <?php else: ?>
                <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2 py-1">
                  <i class="bi bi-geo-alt me-1"></i>Presencial
                </span>
              <?php endif; ?>
              <?php if (!empty($t['lugar_o_enlace'])): ?>
                <div class="small text-muted text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars($t['lugar_o_enlace']) ?>">
                  <?= htmlspecialchars($t['lugar_o_enlace']) ?>
                </div>
              <?php endif; ?>
            </td>
            <td>
              <?php
                $badgeNivel = 'bg-primary text-white';
                $iconNivel = 'bi-mortarboard';
                $textoNivel = 'Pregrado';
                if (($t['nivel_academico'] ?? 'pregrado') === 'posgrado') {
                  $badgeNivel = 'bg-indigo text-indigo border border-indigo-subtle';
                  $iconNivel = 'bi-award';
                  $textoNivel = 'Posgrado';
                } elseif (($t['nivel_academico'] ?? 'pregrado') === 'invierno') {
                  $badgeNivel = 'bg-info text-white';
                  $iconNivel = 'bi-snow';
                  $textoNivel = 'Invierno';
                } elseif (($t['nivel_academico'] ?? 'pregrado') === 'verano') {
                  $badgeNivel = 'bg-warning text-dark';
                  $iconNivel = 'bi-sun';
                  $textoNivel = 'Verano';
                } elseif (!in_array(($t['nivel_academico'] ?? 'pregrado'), ['pregrado', 'posgrado', 'invierno', 'verano'], true)) {
                  $badgeNivel = 'bg-secondary text-white';
                  $iconNivel = 'bi-book';
                  $textoNivel = htmlspecialchars($t['nivel_academico']);
                }
              ?>
              <span class="badge <?= $badgeNivel ?> px-2 py-1">
                <i class="bi <?= $iconNivel ?> me-1"></i><?= $textoNivel ?>
              </span>
            </td>
            <td>
              <span class="badge rounded-pill border px-3 py-1 text-capitalize <?= $badgeEstado ?>"><?= htmlspecialchars($t['estado']) ?></span>
              <?php if (!empty($t['calificacion'])): ?>
                <div class="text-warning small mt-1">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?= $i <= $t['calificacion'] ? '-fill' : '' ?>"></i>
                  <?php endfor; ?>
                </div>
              <?php endif; ?>
            </td>
            <td class="text-end pe-4">
              <div class="btn-group" role="group">
                <?php if ($t['estado'] === 'pendiente'): ?>
                  <a href="tutorias_cambiar_estado.php?id=<?= $t['id_tutoria'] ?>&estado=confirmada&token=<?= tokenCsrfUrl() ?>" class="btn btn-outline-success btn-sm btn-icon" title="Confirmar sesión">
                    <i class="bi bi-check-lg"></i>
                  </a>
                <?php endif; ?>
                <?php if ($t['estado'] === 'confirmada'): ?>
                  <a href="tutorias_cambiar_estado.php?id=<?= $t['id_tutoria'] ?>&estado=en_proceso&token=<?= tokenCsrfUrl() ?>" class="btn btn-outline-indigo btn-sm btn-icon" title="Iniciar sesión (En Proceso)">
                    <i class="bi bi-play-circle"></i>
                  </a>
                <?php endif; ?>
                <?php if ($t['estado'] === 'en_proceso'): ?>
                  <a href="tutorias_cambiar_estado.php?id=<?= $t['id_tutoria'] ?>&estado=realizada&token=<?= tokenCsrfUrl() ?>" class="btn btn-outline-primary btn-sm btn-icon" title="Marcar como realizada">
                    <i class="bi bi-check2-all"></i>
                  </a>
                <?php endif; ?>
                <?php if ($t['estado'] === 'pendiente' || $t['estado'] === 'confirmada' || $t['estado'] === 'en_proceso'): ?>
                  <button type="button" class="btn btn-outline-warning btn-sm btn-icon" title="Cancelar sesión"
                          onclick="confirmarEliminacion('tutorias_cambiar_estado.php?id=<?= $t['id_tutoria'] ?>&estado=cancelada&token=<?= tokenCsrfUrl() ?>', '¿Deseas cancelar esta tutoría?')">
                    <i class="bi bi-slash-circle"></i>
                  </button>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($tutorias)): ?>
          <tr>
            <td colspan="8" class="text-center py-5 text-muted">
              <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
              No hay tutorías registradas con el criterio seleccionado.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  document.getElementById('buscadorTutorias')?.addEventListener('keyup', function() {
    const valor = this.value.toLowerCase();
    const filas = document.querySelectorAll('#tablaTutorias tbody tr');
    filas.forEach(fila => {
      fila.style.display = fila.textContent.toLowerCase().includes(valor) ? '' : 'none';
    });
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>