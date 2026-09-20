<?php
// =========================================================
// VISTA: PANEL DEL DOCENTE TUTOR (views/tutor/panel.php)
// ---------------------------------------------------------
// Autocontenida: arma sus propios datos (conexión + modelos)
// sin pasar por un controlador. Pasos:
//   1. Recupera el perfil del tutor en sesión; si no existe,
//      lo crea con especialidad por defecto.
//   2. Carga sus tutorías, materias y horarios.
//   3. Calcula contadores por estado.
// La vista muestra la banda de bienvenida (especialidad,
// materias y bloques), 4 métricas y la tabla de sesiones con
// acciones: Aceptar/Rechazar (pendiente) y Marcar Realizada
// (confirmada), que apuntan a tutorias_cambiar_estado.php.
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/TutorModel.php';
require_once __DIR__ . '/../../models/TutoriaModel.php';

$tutorModel = new TutorModel($pdo);
$tutoriaModel = new TutoriaModel($pdo);

$idUsuario = $_SESSION['id_usuario'] ?? 0;

// Control de acceso por rol: solo el tutor entra a este panel
if (($_SESSION['rol'] ?? '') !== 'tutor') {
    if (($_SESSION['rol'] ?? '') === 'administrador') {
        header('Location: ' . base_url() . '/controllers/dashboard.php');
    } elseif (($_SESSION['rol'] ?? '') === 'estudiante') {
        header('Location: ' . base_url() . '/views/estudiante/panel.php');
    } else {
        header('Location: ' . base_url() . '/views/login/login.php');
    }
    exit;
}

$tutor = $tutorModel->obtenerPorUsuario($idUsuario);

if (!$tutor) {
    // Si no tiene registro en tutores, lo creamos automáticamente
    $pdo->prepare("INSERT INTO tutores (id_usuario, especialidad) VALUES (?, 'Docente UPDS')")->execute([$idUsuario]);
    $tutor = $tutorModel->obtenerPorUsuario($idUsuario);
}

$idTutor = $tutor['id_tutor'];
$misTutorias = $tutoriaModel->obtenerPorTutor($idTutor);
$misMaterias = $tutorModel->obtenerMaterias($idTutor);
$misHorarios = $tutorModel->obtenerDisponibilidad($idTutor);

$pendientes = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'pendiente'));
$confirmadas = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'confirmada'));
$realizadas = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'realizada'));
$canceladas  = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'cancelada'));

$tituloPagina = 'Panel del Docente Tutor - UPDS';
include __DIR__ . '/../layouts/header.php';
?>

<div class="row g-4">
  <!-- Banda de bienvenida -->
  <div class="col-12">
    <div class="hero-band p-4 p-md-4 mb-3">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h2 class="fw-bold text-white mb-1">¡Bienvenido(a), Prof. <?= htmlspecialchars($_SESSION['nombre']) ?>! 👋</h2>
          <p class="text-white-50 mb-0">
            <i class="bi bi-award me-1"></i><?= htmlspecialchars($tutor['especialidad'] ?? 'Docencia UPDS') ?>
            &bull; <i class="bi bi-book me-1"></i><?= count($misMaterias) ?> materia(s) &bull; <i class="bi bi-clock me-1"></i><?= count($misHorarios) ?> bloque(s) de horario
          </p>
        </div>
        <a href="<?= base_url() ?>/controllers/tutores_disponibilidad.php?id=<?= $idTutor ?>" class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-2 shadow-sm">
          <i class="bi bi-clock-history"></i>
          <span>Mis Horarios y Materias</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Métricas en vivo -->
  <div class="col-6 col-md-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-warning bg-opacity-25 text-warning"><i class="bi bi-hourglass-split"></i></div>
        <div><h4 class="fw-bold mb-0 text-warning"><?= $pendientes ?></h4></div>
      </div>
      <small class="text-muted">Solicitudes Pendientes</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-check"></i></div>
        <div><h4 class="fw-bold mb-0 text-info"><?= $confirmadas ?></h4></div>
      </div>
      <small class="text-muted">Sesiones Confirmadas</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-success bg-opacity-10 text-success"><i class="bi bi-check2-circle"></i></div>
        <div><h4 class="fw-bold mb-0 text-success"><?= $realizadas ?></h4></div>
      </div>
      <small class="text-muted">Tutorías Realizadas</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle"></i></div>
        <div><h4 class="fw-bold mb-0 text-danger"><?= $canceladas ?></h4></div>
      </div>
      <small class="text-muted">Canceladas</small>
    </div>
  </div>

  <!-- Listado de tutorías asignadas -->
  <div class="col-12">
    <div class="card card-custom shadow-sm overflow-hidden">
      <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-calendar-week text-primary"></i>
          <span>Mis Sesiones de Tutoría</span>
        </h5>
        <span class="badge text-bg-light border px-3 py-2"><?= count($misTutorias) ?> sesión(es)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
            <tr>
              <th class="ps-4">Fecha y Horario</th>
              <th>Materia</th>
              <th>Estudiante</th>
              <th>Modalidad</th>
              <th>Estado</th>
              <th class="text-end pe-4">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($misTutorias as $t): ?>
              <?php
                $badgeEstado = 'bg-warning text-dark';
                if ($t['estado'] === 'confirmada') $badgeEstado = 'bg-info text-white';
                if ($t['estado'] === 'realizada') $badgeEstado = 'bg-success text-white';
                if ($t['estado'] === 'cancelada') $badgeEstado = 'bg-danger text-white';
              ?>
              <tr>
                <td class="ps-4">
                  <div class="fw-bold text-dark"><?= date('d/m/Y', strtotime($t['fecha'])) ?></div>
                  <small class="text-muted"><?= substr($t['hora_inicio'], 0, 5) ?> - <?= substr($t['hora_fin'], 0, 5) ?></small>
                </td>
                <td>
                  <div class="fw-semibold text-primary"><?= htmlspecialchars($t['nombre_materia']) ?></div>
                  <?php if (!empty($t['observaciones'])): ?>
                    <small class="text-muted text-truncate d-block" style="max-width: 200px;" title="<?= htmlspecialchars($t['observaciones']) ?>">
                      Obs: <?= htmlspecialchars($t['observaciones']) ?>
                    </small>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="fw-medium text-dark"><?= htmlspecialchars($t['est_nombre'] . ' ' . $t['est_apellido']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($t['est_correo']) ?></small>
                </td>
                <td>
                  <span class="badge bg-light text-dark border"><?= ucfirst($t['modalidad']) ?></span>
                  <?php if (!empty($t['lugar_o_enlace'])): ?>
                    <div class="small text-muted text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars($t['lugar_o_enlace']) ?>"><?= htmlspecialchars($t['lugar_o_enlace']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <span class="badge rounded-pill px-3 py-1 <?= $badgeEstado ?>"><?= ucfirst($t['estado']) ?></span>
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
                      <a href="<?= base_url() ?>/controllers/tutorias_cambiar_estado.php?id=<?= $t['id_tutoria'] ?>&estado=confirmada&token=<?= tokenCsrfUrl() ?>"
                         class="btn btn-sm btn-success d-flex align-items-center gap-1" title="Aceptar y confirmar">
                        <i class="bi bi-check-circle"></i> Aceptar
                      </a>
                      <button type="button" class="btn btn-sm btn-outline-danger" title="Rechazar"
                              onclick="confirmarEliminacion('<?= base_url() ?>/controllers/tutorias_cambiar_estado.php?id=<?= $t['id_tutoria'] ?>&estado=cancelada&token=<?= tokenCsrfUrl() ?>', '¿Rechazar esta solicitud de tutoría?')">
                        <i class="bi bi-x-circle"></i>
                      </button>
                    <?php elseif ($t['estado'] === 'confirmada'): ?>
                      <a href="<?= base_url() ?>/controllers/tutorias_cambiar_estado.php?id=<?= $t['id_tutoria'] ?>&estado=realizada&token=<?= tokenCsrfUrl() ?>"
                         class="btn btn-sm btn-primary d-flex align-items-center gap-1" title="Marcar como realizada">
                        <i class="bi bi-check2-all"></i> Marcar Realizada
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($misTutorias)): ?>
              <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-calendar-check fs-1 d-block mb-2 text-secondary"></i>
                  Aún no tienes solicitudes de tutorías asignadas.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>