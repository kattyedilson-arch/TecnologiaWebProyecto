<?php
// =========================================================
// VISTA: PANEL DEL ESTUDIANTE (views/estudiante/panel.php)
// ---------------------------------------------------------
// Autocontenida: arma sus propios datos (conexión + modelos).
// Pasos:
//   1. Recupera la ficha del estudiante en sesión; si no
//      existe, la crea con valores por defecto.
//   2. Carga sus tutorías y calcula contadores por estado.
// La vista presenta la banda de bienvenida (carrera y
// semestre), 4 métricas y la tabla de solicitudes con fecha,
// materia, docente, modalidad y estado. Acciones disponibles:
//   - Cancelar solicitud (estado pendiente).
//   - Evaluar sesión (realizada y sin calificación), que enlaza
//     a tutorias_evaluar.php.
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/TutoriaModel.php';
require_once __DIR__ . '/../../models/EstudianteModel.php';

$tutoriaModel = new TutoriaModel($pdo);
$estudianteModel = new EstudianteModel($pdo);

$idUsuario = $_SESSION['id_usuario'] ?? 0;

// Control de acceso por rol: solo el estudiante entra a este panel
if (($_SESSION['rol'] ?? '') !== 'estudiante') {
    if (($_SESSION['rol'] ?? '') === 'administrador') {
        header('Location: ' . base_url() . '/controllers/dashboard.php');
    } elseif (($_SESSION['rol'] ?? '') === 'tutor') {
        header('Location: ' . base_url() . '/views/tutor/panel.php');
    } else {
        header('Location: ' . base_url() . '/views/login/login.php');
    }
    exit;
}

$estudiante = $estudianteModel->obtenerPorUsuario($idUsuario);

if (!$estudiante) {
    // Se crea la ficha académica con carrera y semestre por defecto.
    // Si la creación falla, se informa de forma amigable (sin error fatal).
    try {
        $pdo->prepare("INSERT INTO estudiantes (id_usuario, id_carrera, semestre) VALUES (?, 1, 1)")->execute([$idUsuario]);
        $estudiante = $estudianteModel->obtenerPorUsuario($idUsuario);
    } catch (PDOException $e) {
        $estudiante = false;
    }
}

if (!$estudiante) {
    $tituloPagina = 'Panel del Estudiante - UPDS';
    include __DIR__ . '/../layouts/header.php';
    echo '<div class="alert alert-danger d-flex align-items-center gap-2 shadow-sm rounded-3">Se produjo un error al cargar tu ficha de estudiante. Cierra sesión y vuelve a intentarlo, o contacta al administrador.</div>';
    include __DIR__ . '/../layouts/footer.php';
    exit;
}

$idEstudiante = $estudiante['id_estudiante'];
$misTutorias = $tutoriaModel->obtenerPorEstudiante($idEstudiante);

$pendientes = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'pendiente'));
$confirmadas = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'confirmada'));
$realizadas = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'realizada'));
$canceladas = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'cancelada'));

$tituloPagina = 'Panel del Estudiante - UPDS';
include __DIR__ . '/../layouts/header.php';
?>

<div class="row g-4">
  <!-- Banda de bienvenida -->
  <div class="col-12">
    <div class="hero-band p-4 p-md-4 mb-3">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h2 class="fw-bold text-white mb-1">¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>! 📚</h2>
          <p class="text-white-50 mb-0">
            <?php if (!empty($estudiante['nombre_carrera'])): ?>
              <i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($estudiante['nombre_carrera']) ?>
              &bull; <i class="bi bi-layers me-1"></i>Semestre <?= (int) $estudiante['semestre'] ?>
            <?php else: ?>
              Estudiante UPDS &bull; Toca "Mi Perfil" para completar tu ficha académica
            <?php endif; ?>
          </p>
        </div>
        <a href="<?= base_url() ?>/controllers/tutorias_solicitar.php" class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-2 shadow-sm">
          <i class="bi bi-calendar-plus-fill"></i>
          <span>Solicitar Nueva Tutoría</span>
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
      <small class="text-muted">En Espera</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-check"></i></div>
        <div><h4 class="fw-bold mb-0 text-info"><?= $confirmadas ?></h4></div>
      </div>
      <small class="text-muted">Confirmadas</small>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-success bg-opacity-10 text-success"><i class="bi bi-check2-circle"></i></div>
        <div><h4 class="fw-bold mb-0 text-success"><?= $realizadas ?></h4></div>
      </div>
      <small class="text-muted">Realizadas</small>
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

  <!-- Mis tutorías -->
  <div class="col-12">
    <div class="card card-custom shadow-sm overflow-hidden">
      <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-calendar-week text-primary"></i>
          <span>Mis Solicitudes de Tutoría</span>
        </h5>
        <span class="badge text-bg-light border px-3 py-2"><?= count($misTutorias) ?> sesión(es)</span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
            <tr>
              <th class="ps-4">Fecha y Horario</th>
              <th>Materia</th>
              <th>Docente Tutor</th>
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
                  <small class="text-muted"><?= htmlspecialchars($t['nombre_carrera'] ?? 'General') ?></small>
                </td>
                <td>
                  <div class="fw-medium text-dark">Prof. <?= htmlspecialchars($t['tut_nombre'] . ' ' . $t['tut_apellido']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($t['tut_correo']) ?></small>
                </td>
                <td>
                  <span class="badge bg-light text-dark border"><?= ucfirst($t['modalidad']) ?></span>
                  <?php if (!empty($t['lugar_o_enlace'])): ?>
                    <div class="small text-muted text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($t['lugar_o_enlace']) ?>"><?= htmlspecialchars($t['lugar_o_enlace']) ?></div>
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
                      <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1"
                              onclick="confirmarEliminacion('<?= base_url() ?>/controllers/tutorias_cambiar_estado.php?id=<?= $t['id_tutoria'] ?>&estado=cancelada&token=<?= tokenCsrfUrl() ?>', '¿Cancelar tu solicitud de tutoría?')">
                        <i class="bi bi-x-circle"></i> Cancelar Solicitud
                      </button>
                    <?php elseif ($t['estado'] === 'realizada' && empty($t['calificacion'])): ?>
                      <a href="<?= base_url() ?>/controllers/tutorias_evaluar.php?id=<?= $t['id_tutoria'] ?>" class="btn btn-sm btn-warning text-dark d-flex align-items-center gap-1">
                        <i class="bi bi-star-fill"></i> Evaluar Sesión
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($misTutorias)): ?>
              <tr>
                <td colspan="6" class="text-center py-5 text-muted">
                  <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
                  No has solicitado tutorías todavía. ¡Aprovecha el apoyo académico UPDS!
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