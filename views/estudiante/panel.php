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
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/TutoriaModel.php';
require_once __DIR__ . '/../../models/EstudianteModel.php';
require_once __DIR__ . '/../../models/SolicitudInteresModel.php';

$tutoriaModel = new TutoriaModel($pdo);
$estudianteModel = new EstudianteModel($pdo);

$idUsuario = $_SESSION['id_usuario'] ?? 0;

// Control de acceso por rol: solo el estudiante entra a este panel
if (($_SESSION['rol'] ?? '') !== 'estudiante') {
    redirigirAlPanel();
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

// Materias OFRECIDAS que todavía no tienen docente: el estudiante pidió
// el horario y espera a que un tutor acepte la oferta. No son tutorías
// (no hay quién imparta la sesión), por eso viven en su propia sección.
$misIntereses = (new SolicitudInteresModel($pdo))->obtenerPorEstudiante($idEstudiante);
$interesesPendientes = count(array_filter($misIntereses, function ($i) { return $i['estado'] === 'pendiente'; }));
$interesesAtendidos = count(array_filter($misIntereses, function ($i) { return $i['estado'] === 'atendida'; }));

$pendientes = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'pendiente'));
$confirmadas = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'confirmada'));
$enProceso = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'en_proceso'));
$realizadas = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'realizada'));
$canceladas = count(array_filter($misTutorias, fn($t) => $t['estado'] === 'cancelada'));

$tituloPagina = 'Panel del Estudiante - UPDS';
include __DIR__ . '/../layouts/header.php';
?>

<div class="row g-4">
  <!-- Banda de bienvenida del estudiante -->
  <div class="col-12">
    <div class="hero-band p-4 p-md-4 mb-3">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <?= avatarHTML($_SESSION['foto'] ?? '', strtoupper(mb_substr($_SESSION['nombre'] ?? 'E', 0, 1)), 'avatar-lg d-none d-sm-flex') ?>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge text-bg-light text-dark border px-3 py-1" style="font-size:.68rem; letter-spacing:.6px; text-transform:uppercase;">
                <i class="bi bi-mortarboard me-1"></i> Portal del Estudiante
              </span>
            </div>
            <h2 class="fw-bold text-white mb-1">¡Hola, <?= htmlspecialchars($_SESSION['nombre']) ?>!</h2>
            <p class="text-white-50 mb-0 d-flex flex-wrap gap-2 align-items-center">
              <?php if (!empty($estudiante['nombre_carrera'])): ?>
                <span><i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($estudiante['nombre_carrera']) ?></span>
                <span class="d-none d-md-inline">•</span>
                <span><i class="bi bi-layers me-1"></i>Semestre <?= (int) $estudiante['semestre'] ?></span>
              <?php else: ?>
                <span><i class="bi bi-info-circle me-1"></i>Estudiante UPDS &bull; Toca "Mi Perfil" para completar tu ficha académica</span>
              <?php endif; ?>
            </p>
          </div>
        </div>
        <a href="/controllers/tutorias_solicitar.php" class="btn btn-primary fw-bold d-flex align-items-center gap-2 shadow-sm" style="border:1px solid rgba(255,255,255,.5);">
          <i class="bi bi-calendar-plus-fill"></i>
          <span>Solicitar Nueva Tutoría</span>
        </a>
      </div>
    </div>
  </div>

  <!-- Métricas en vivo -->
  <div class="col-6 col-md">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-warning bg-opacity-25 text-warning"><i class="bi bi-hourglass-split"></i></div>
        <div><h4 class="fw-bold mb-0 text-warning"><?= $pendientes ?></h4></div>
      </div>
      <small class="text-muted">Pendientes</small>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-info bg-opacity-10 text-info"><i class="bi bi-calendar-check"></i></div>
        <div><h4 class="fw-bold mb-0 text-info"><?= $confirmadas ?></h4></div>
      </div>
      <small class="text-muted">Confirmadas</small>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-indigo bg-opacity-10 text-indigo"><i class="bi bi-play-circle"></i></div>
        <div><h4 class="fw-bold mb-0 text-indigo"><?= $enProceso ?></h4></div>
      </div>
      <small class="text-muted">En Proceso</small>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-success bg-opacity-10 text-success"><i class="bi bi-check2-circle"></i></div>
        <div><h4 class="fw-bold mb-0 text-success"><?= $realizadas ?></h4></div>
      </div>
      <small class="text-muted">Realizadas</small>
    </div>
  </div>
  <div class="col-6 col-md">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-danger bg-opacity-10 text-danger"><i class="bi bi-x-circle"></i></div>
        <div><h4 class="fw-bold mb-0 text-danger"><?= $canceladas ?></h4></div>
      </div>
      <small class="text-muted">Canceladas</small>
    </div>
  </div>

  <div class="col-6 col-md">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-secondary bg-opacity-10 text-secondary"><i class="bi bi-hourglass-top"></i></div>
        <div><h4 class="fw-bold mb-0 text-secondary"><?= $interesesPendientes ?></h4></div>
      </div>
      <small class="text-muted">Esperando docente</small>
    </div>
  </div>

  <!-- Materias pedidas que aún no tienen docente -->
  <?php if (!empty($misIntereses)): ?>
  <div class="col-12">
    <div class="card card-custom shadow-sm overflow-hidden">
      <div class="card-header bg-white py-3 border-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-hourglass-split text-secondary"></i>
          <span>Materias que Pediste sin Docente</span>
        </h5>
        <div class="d-flex flex-wrap gap-2">
          <span class="badge text-bg-light border"><?= $interesesPendientes ?> esperando</span>
          <span class="badge text-bg-success bg-opacity-10 text-success border"><?= $interesesAtendidos ?> ya con docente</span>
        </div>
      </div>

      <?php if ($interesesAtendidos > 0): ?>
        <div class="alert alert-success d-flex align-items-start gap-2 rounded-0 border-0 border-bottom mb-0 py-2 px-3 small">
          <i class="bi bi-check-circle-fill mt-1"></i>
          <div>
            <?= $interesesAtendidos ?> de tus materias ya tienen docente asignado.
            <a href="/controllers/tutorias_solicitar.php" class="alert-link fw-semibold">Reserva tu tutoría ahora</a>.
          </div>
        </div>
      <?php endif; ?>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
            <tr>
              <th class="ps-4">Materia</th>
              <th>Horario Pedido</th>
              <th>Modalidad</th>
              <th>Solicitado</th>
              <th>Estado</th>
              <th class="text-end pe-4">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($misIntereses as $i): ?>
              <?php
                $esperaDocente = ($i['estado'] === 'pendiente');
                $hayDocente    = !empty($i['tut_nombre']);
              ?>
              <tr>
                <td class="ps-4">
                  <div class="fw-semibold text-primary"><?= htmlspecialchars($i['nombre_materia']) ?></div>
                  <small class="text-muted"><?= htmlspecialchars($i['nombre_carrera'] ?? 'General') ?></small>
                </td>
                <td>
                  <div class="fw-medium text-dark"><?= htmlspecialchars($i['nombre_turno']) ?></div>
                  <small class="text-muted"><?= substr($i['hora_inicio'], 0, 5) ?> - <?= substr($i['hora_fin'], 0, 5) ?></small>
                </td>
                <td>
                  <span class="badge bg-light text-dark border"><?= ucfirst($i['modalidad'] ?? 'presencial') ?></span>
                  <?php if (!empty($i['lugar_o_enlace'])): ?>
                    <div class="small text-muted text-truncate" style="max-width: 140px;" title="<?= htmlspecialchars($i['lugar_o_enlace']) ?>">
                      <?= htmlspecialchars($i['lugar_o_enlace']) ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="text-dark"><?= date('d/m/Y', strtotime($i['fecha_creacion'])) ?></div>
                  <small class="text-muted"><?= date('H:i', strtotime($i['fecha_creacion'])) ?></small>
                </td>
                <td>
                  <?php if ($hayDocente): ?>
                    <span class="badge rounded-pill px-3 py-1 bg-success bg-opacity-10 text-success border">
                      <i class="bi bi-person-check me-1"></i>Con docente
                    </span>
                    <div class="small text-muted mt-1 text-truncate" style="max-width: 160px;">
                      Prof. <?= htmlspecialchars(trim($i['tut_nombre'] . ' ' . $i['tut_apellido'])) ?>
                    </div>
                  <?php else: ?>
                    <span class="badge rounded-pill px-3 py-1 bg-warning text-dark">
                      <i class="bi bi-hourglass-split me-1"></i>Esperando docente
                    </span>
                  <?php endif; ?>
                  <?php if (!empty($i['mensaje'])): ?>
                    <div class="small text-muted fst-italic mt-1 text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($i['mensaje']) ?>">
                      &laquo;<?= htmlspecialchars($i['mensaje']) ?>&raquo;
                    </div>
                  <?php endif; ?>
                </td>
                <td class="text-end pe-4">
                  <div class="btn-group" role="group">
                    <?php if ($hayDocente): ?>
                      <a href="/controllers/tutorias_solicitar.php" class="btn btn-sm btn-success d-flex align-items-center gap-1">
                        <i class="bi bi-calendar-plus"></i> Reservar
                      </a>
                    <?php elseif ($esperaDocente): ?>
                      <a href="/controllers/tutorias_solicitar.php" class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1">
                        <i class="bi bi-eye"></i> Ver
                      </a>
                    <?php endif; ?>
                    <form method="post" action="/controllers/interes_cancelar.php" class="d-inline"
                          data-confirm="¿Cancelar tu pedido de <?= htmlspecialchars($i['nombre_materia']) ?>?"
                          data-confirm-title="Cancelar pedido"
                          data-confirm-text="Sí, cancelar"
                          data-confirm-color="#d97706">
                      <?= campoCsrf() ?>
                      <input type="hidden" name="id_solicitud" value="<?= (int)$i['id_solicitud'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
                        <i class="bi bi-x-circle"></i> Cancelar
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <?php endif; ?>

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
              <th>Tipo</th>
              <th>Estado</th>
              <th class="text-end pe-4">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($misTutorias as $t): ?>
              <?php
                $badgeEstado = 'bg-warning text-dark';
                if ($t['estado'] === 'confirmada') $badgeEstado = 'bg-info text-white';
                if ($t['estado'] === 'en_proceso') $badgeEstado = 'bg-indigo text-white';
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
                  <div class="d-flex align-items-center gap-2">
                    <?= avatarHTML($t['tut_foto'] ?? '', iniciales($t['tut_nombre'] ?? '', $t['tut_apellido'] ?? ''), 'avatar-md', 'width:34px; height:34px; font-size:.72rem;') ?>
                    <div>
                      <div class="fw-medium text-dark">Prof. <?= htmlspecialchars($t['tut_nombre'] . ' ' . $t['tut_apellido']) ?></div>
                      <small class="text-muted"><?= htmlspecialchars($t['tut_correo']) ?></small>
                    </div>
                  </div>
                </td>
                <td>
                  <span class="badge bg-light text-dark border"><?= ucfirst($t['modalidad']) ?></span>
                  <?php if (!empty($t['lugar_o_enlace'])): ?>
                    <div class="small text-muted text-truncate" style="max-width: 150px;" title="<?= htmlspecialchars($t['lugar_o_enlace']) ?>"><?= htmlspecialchars($t['lugar_o_enlace']) ?></div>
                  <?php endif; ?>
                </td>
                <td>
                  <?php
                    $badgeNivel = 'bg-primary text-white';
                    $textoNivel = 'Pregrado';
                    if (($t['nivel_academico'] ?? 'pregrado') === 'posgrado') {
                      $badgeNivel = 'bg-indigo text-indigo';
                      $textoNivel = 'Posgrado';
                    } elseif (($t['nivel_academico'] ?? 'pregrado') === 'invierno') {
                      $badgeNivel = 'bg-info text-white';
                      $textoNivel = 'Invierno';
                    } elseif (($t['nivel_academico'] ?? 'pregrado') === 'verano') {
                      $badgeNivel = 'bg-warning text-dark';
                      $textoNivel = 'Verano';
                    } elseif (!in_array(($t['nivel_academico'] ?? 'pregrado'), ['pregrado', 'posgrado', 'invierno', 'verano'], true)) {
                      $badgeNivel = 'bg-secondary text-white';
                      $textoNivel = htmlspecialchars($t['nivel_academico']);
                    }
                  ?>
                  <span class="badge <?= $badgeNivel ?>"><?= $textoNivel ?></span>
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
                    <?php if ($t['estado'] === 'pendiente' || $t['estado'] === 'confirmada'): ?>
                      <form method="post" action="/controllers/tutorias_cambiar_estado.php" class="d-inline"
                            data-confirm="¿Cancelar tu solicitud de tutoría?"
                            data-confirm-title="Cancelar solicitud"
                            data-confirm-text="Sí, cancelar"
                            data-confirm-color="#d97706">
                        <?= campoCsrf() ?>
                        <input type="hidden" name="id" value="<?= (int)$t['id_tutoria'] ?>">
                        <input type="hidden" name="estado" value="cancelada">
                        <button type="submit" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1">
                          <i class="bi bi-x-circle"></i> Cancelar Solicitud
                        </button>
                      </form>
                    <?php elseif ($t['estado'] === 'realizada' && empty($t['calificacion'])): ?>
                      <a href="/controllers/tutorias_evaluar.php?id=<?= $t['id_tutoria'] ?>" class="btn btn-sm btn-primary d-flex align-items-center gap-1">
                        <i class="bi bi-star-fill"></i> Evaluar Sesión
                      </a>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php if (empty($misTutorias)): ?>
              <tr>
                <td colspan="7" class="text-center py-5 text-muted">
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