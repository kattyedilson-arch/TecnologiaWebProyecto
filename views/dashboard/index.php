<?php
// =========================================================
// VISTA: DASHBOARD DEL ADMINISTRADOR (views/dashboard/index.php)
// ---------------------------------------------------------
// Página principal tras el login del administrador. Presenta:
//   - Banda de bienvenida (hero-band) con accesos rápidos
//     a Tutorías y a Agendar Sesión.
//   - Métricas principales (usuarios, tutores, estudiantes,
//     promedio de calificaciones).
//   - Estado del ciclo de tutorías (total, pendientes,
//     confirmadas, realizadas, canceladas).
//   - Últimas tutorías solicitadas en tabla.
//   - Top de tutores mejor calificados.
//   - Materias más tutoradas (barras de progreso).
// Variables esperadas del controlador (controllers/dashboard.php):
//   $resumen, $ultimasTutorias, $topTutores, $materiasTop
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
require_once __DIR__ . '/../../includes/funciones.php';

// Solo el administrador puede ver el panel central
if (($_SESSION['rol'] ?? '') !== 'administrador') {
    if (($_SESSION['rol'] ?? '') === 'tutor') {
        header('Location: ' . base_url() . '/views/tutor/panel.php');
    } elseif (($_SESSION['rol'] ?? '') === 'estudiante') {
        header('Location: ' . base_url() . '/views/estudiante/panel.php');
    } else {
        header('Location: ' . base_url() . '/views/login/login.php');
    }
    exit;
}

// Si se abre la vista directo (sin pasar por controllers/dashboard.php),
// las variables de datos no existen: se envía al controlador.
if (!isset($resumen) || !isset($ultimasTutorias) || !isset($topTutores) || !isset($materiasTop)) {
    header('Location: ' . base_url() . '/controllers/dashboard.php');
    exit;
}

$tituloPagina = 'Dashboard Administrativo - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';
?>

<!-- Banda de bienvenida -->
<div class="hero-band p-4 p-md-5 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <span class="badge rounded-pill px-3 py-1 mb-2" style="background: rgba(255,255,255,0.15); color:#fff;">Panel de Control</span>
      <h2 class="fw-bold text-white mb-1">¡Bienvenido de nuevo, <?= htmlspecialchars($_SESSION['nombre']) ?>! 👋</h2>
      <p class="text-white-50 mb-0">Visión general del estado académico y operativo del sistema de tutorías.</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <a href="<?= base_url() ?>/controllers/tutorias_listar.php" class="btn btn-light fw-semibold d-flex align-items-center gap-2 shadow-sm">
        <i class="bi bi-calendar-week"></i> Ver Tutorías
      </a>
      <a href="<?= base_url() ?>/controllers/usuarios_listar.php" class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-2 shadow-sm">
        <i class="bi bi-people-fill"></i> Gestionar Usuarios
      </a>
    </div>
  </div>
</div>

<!-- Métricas principales -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card card-custom stat-card p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $resumen['total_usuarios'] ?? 0 ?></h4>
          <small class="text-muted">Usuarios registrados</small>
        </div>
      </div>
      <div class="stat-sub mt-2"><i class="bi bi-person-check me-1"></i><?= $resumen['usuarios_activos'] ?? 0 ?> activos</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card card-custom stat-card p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-indigo text-indigo"><i class="bi bi-person-video3"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $resumen['total_tutores'] ?? 0 ?></h4>
          <small class="text-muted">Docentes tutores</small>
        </div>
      </div>
      <div class="stat-sub mt-2"><i class="bi bi-book me-1"></i><?= $resumen['total_materias'] ?? 0 ?> materias</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card card-custom stat-card p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico text-ok"><i class="bi bi-mortarboard"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $resumen['total_estudiantes'] ?? 0 ?></h4>
          <small class="text-muted">Estudiantes activos</small>
        </div>
      </div>
      <div class="stat-sub mt-2"><i class="bi bi-buildings me-1"></i><?= $resumen['total_carreras'] ?? 0 ?> carreras</div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card card-custom stat-card p-3 h-100">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-warning bg-opacity-10 text-warning"><i class="bi bi-award"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $resumen['promedio_evaluaciones'] ?></h4>
          <small class="text-muted">Prom. calificaciones</small>
        </div>
      </div>
      <div class="stat-sub mt-2"><i class="bi bi-chat-square-text me-1"></i><?= $resumen['total_evaluaciones'] ?? 0 ?> evaluaciones</div>
    </div>
  </div>
</div>

<!-- Estado de tutorías -->
<div class="card card-custom shadow-sm p-4 mb-4">
  <h6 class="fw-bold mb-3 text-uppercase small text-secondary d-flex align-items-center gap-2">
    <i class="bi bi-graph-up text-primary"></i> Estado del Ciclo de Tutorías
  </h6>
  <div class="row g-3">
    <div class="col-6 col-md-2">
      <div class="text-center p-3 rounded-3 bg-primary bg-opacity-10">
        <h4 class="fw-bold text-primary mb-0"><?= $resumen['total_tutorias'] ?? 0 ?></h4>
        <small class="text-muted">Totales</small>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="text-center p-3 rounded-3 bg-warning bg-opacity-25">
        <h4 class="fw-bold text-warning mb-0"><?= $resumen['pendientes'] ?? 0 ?></h4>
        <small class="text-muted">Pendientes</small>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="text-center p-3 rounded-3 bg-info bg-opacity-10">
        <h4 class="fw-bold text-info mb-0"><?= $resumen['confirmadas'] ?? 0 ?></h4>
        <small class="text-muted">Confirmadas</small>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="text-center p-3 rounded-3 bg-success bg-opacity-10">
        <h4 class="fw-bold text-success mb-0"><?= $resumen['realizadas'] ?? 0 ?></h4>
        <small class="text-muted">Realizadas</small>
      </div>
    </div>
    <div class="col-6 col-md-2">
      <div class="text-center p-3 rounded-3 bg-danger bg-opacity-10">
        <h4 class="fw-bold text-danger mb-0"><?= $resumen['canceladas'] ?? 0 ?></h4>
        <small class="text-muted">Canceladas</small>
      </div>
    </div>
  </div>
</div>

<!-- Últimas tutorías y top tutores -->
<div class="row g-4">
  <div class="col-lg-7">
    <div class="card card-custom shadow-sm overflow-hidden h-100">
      <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-clock-history text-primary"></i> Últimas Tutorías Solicitadas
        </h6>
        <a href="<?= base_url() ?>/controllers/tutorias_listar.php" class="btn btn-sm btn-outline-primary">Ver todas</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
            <tr>
              <th class="ps-4">Estudiante</th>
              <th>Materia</th>
              <th>Docente</th>
              <th>Fecha</th>
              <th class="text-end pe-4">Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ultimasTutorias as $tut): ?>
              <?php
                $bE = 'bg-warning bg-opacity-25 text-warning-emphasis';
                if ($tut['estado'] === 'confirmada') $bE = 'bg-info bg-opacity-25 text-info-emphasis';
                if ($tut['estado'] === 'realizada') $bE = 'bg-success bg-opacity-25 text-success-emphasis';
                if ($tut['estado'] === 'cancelada') $bE = 'bg-danger bg-opacity-25 text-danger-emphasis';
              ?>
              <tr>
                <td class="ps-4 fw-medium text-dark"><?= htmlspecialchars($tut['est_nombre'] . ' ' . $tut['est_apellido']) ?></td>
                <td class="text-primary fw-semibold small"><?= htmlspecialchars($tut['nombre_materia']) ?></td>
                <td class="small">Prof. <?= htmlspecialchars($tut['tut_nombre'] . ' ' . $tut['tut_apellido']) ?></td>
                <td class="small"><?= date('d/m/Y', strtotime($tut['fecha'])) ?> <span class="text-muted">(<?= substr($tut['hora_inicio'],0,5) ?>)</span></td>
                <td class="text-end pe-4">
                  <span class="badge rounded-pill px-3 py-1 text-capitalize <?= $bE ?>"><?= htmlspecialchars($tut['estado']) ?></span>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($ultimasTutorias)): ?>
              <tr>
                <td colspan="5" class="text-center py-5 text-muted">
                  <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                  Aún no hay tutorías registradas.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card card-custom shadow-sm p-4 mb-4">
      <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-trophy text-warning"></i> Tutores Mejor Calificados
      </h6>
      <?php foreach ($topTutores as $tp): ?>
        <div class="d-flex align-items-center justify-content-between py-2 border-bottom">
          <div>
            <div class="fw-bold text-dark small">Prof. <?= htmlspecialchars($tp['nombre'] . ' ' . $tp['apellido']) ?></div>
            <div class="text-muted" style="font-size: 0.75rem;">
              <i class="bi bi-book me-1"></i><?= htmlspecialchars($tp['especialidad'] ?? 'Docencia') ?>
              <span class="ms-1">• <?= $tp['total_tutorias'] ?> sesiones</span>
            </div>
          </div>
          <div class="text-end">
            <div class="fw-bold text-warning"><?= number_format($tp['promedio'], 2) ?><i class="bi bi-star-fill ms-1 small"></i></div>
            <small class="text-muted"><?= $tp['total_evaluaciones'] ?> eval.</small>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($topTutores)): ?>
        <p class="text-muted small text-center py-3 mb-0">Aún no hay evaluaciones registradas.</p>
      <?php endif; ?>
    </div>

    <div class="card card-custom shadow-sm p-4">
      <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
        <i class="bi bi-journal-bookmark-fill text-primary"></i> Materias Más Tutoradas
      </h6>
      <?php $maxTutorias = max(array_column($materiasTop, 'total_tutorias'), [1])[0]; ?>
      <?php foreach ($materiasTop as $mat): ?>
        <?php $porcentaje = ($maxTutorias > 0) ? round(($mat['total_tutorias'] / $maxTutorias) * 100) : 0; ?>
        <div class="mb-3">
          <div class="d-flex justify-content-between small mb-1">
            <span class="fw-semibold text-dark"><?= htmlspecialchars($mat['nombre_materia']) ?></span>
            <span class="text-muted"><?= $mat['total_tutorias'] ?> tutorías</span>
          </div>
          <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-gradient-primary rounded-pill" style="width: <?= $porcentaje ?>%; background: linear-gradient(90deg,#3730a3,#4f46e5);"></div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($materiasTop)): ?>
        <p class="text-muted small text-center py-3 mb-0">No hay sesiones registradas aún.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>