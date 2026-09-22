<?php
// =========================================================
// VISTA: MIS ESTUDIANTES CONFIRMADOS (views/tutor/mis_estudiantes.php)
// ---------------------------------------------------------
// Autocontenida (mismo patrón que views/tutor/panel.php):
//   1. Recupera el perfil del tutor en sesión; si no existe,
//      lo crea con especialidad por defecto.
//   2. Carga sus tutorías y conserva solo las CONFIRMADAS cuya
//      fecha es hoy o futura (las próximas sesiones).
//   3. Agrupa por MATERIA y, dentro de cada materia, por DÍA DE
//      LA SEMANA (Lunes a Sábado) para ver fácilmente la agenda
//      de estudiantes confirmados.
// La vista solo consulta; no modifica datos.
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/TutorModel.php';
require_once __DIR__ . '/../../models/TutoriaModel.php';

$tutorModel = new TutorModel($pdo);
$tutoriaModel = new TutoriaModel($pdo);

$idUsuario = $_SESSION['id_usuario'] ?? 0;

// Control de acceso por rol: solo el tutor entra a esta vista
if (($_SESSION['rol'] ?? '') !== 'tutor') {
    if (($_SESSION['rol'] ?? '') === 'administrador') {
        header('Location: /controllers/dashboard.php');
    } elseif (($_SESSION['rol'] ?? '') === 'estudiante') {
        header('Location: /views/estudiante/panel.php');
    } else {
        header('Location: /views/login/login.php');
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

// Solo sesiones confirmadas próximas (hoy o en el futuro)
$proximas = array_values(array_filter($misTutorias, fn($t) =>
    $t['estado'] === 'confirmada' && $t['fecha'] >= date('Y-m-d')
));

// Orden cronológico global
usort($proximas, fn($a, $b) => ($a['fecha'] . ' ' . $a['hora_inicio']) <=> ($b['fecha'] . ' ' . $b['hora_inicio']));

// Nombre de día de semana (mismo criterio que disponibilidadCubreHorario)
$diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

// Agrupación: materia -> [nombre, sesiones con dia_semana/orden_dia]
$porMateria = [];
foreach ($proximas as $t) {
    $dow = (int)date('w', strtotime($t['fecha']));
    $porMateria[$t['id_materia']]['nombre'] = $t['nombre_materia'];
    $porMateria[$t['id_materia']]['sesiones'][] = $t + ['dia_semana' => $diasSemana[$dow], 'orden_dia' => $dow];
}
foreach ($porMateria as &$m) {
    usort($m['sesiones'], fn($a, $b) => $a['orden_dia'] <=> $b['orden_dia'] ?: ($a['hora_inicio'] <=> $b['hora_inicio']));
}
unset($m);
uasort($porMateria, fn($a, $b) => strcmp($a['nombre'], $b['nombre']));

// Resumen para encabezado y métricas
$totalProximas = count($proximas);
$totalMaterias = count($porMateria);
$totalEstudiantes = count(array_unique(array_column($proximas, 'id_estudiante')));

$tituloPagina = 'Mis Estudiantes Confirmados - UPDS';
include __DIR__ . '/../layouts/header.php';
?>

<div class="row g-4">
  <!-- Banda de cabecera -->
  <div class="col-12">
    <div class="hero-band p-4 p-md-4 mb-3">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div class="d-flex align-items-center gap-3">
          <?= avatarHTML($_SESSION['foto'] ?? '', strtoupper(mb_substr($_SESSION['nombre'] ?? 'T', 0, 1)), 'avatar-lg d-none d-sm-flex') ?>
          <div>
            <div class="d-flex align-items-center gap-2 mb-1">
              <span class="badge text-bg-light text-dark border px-3 py-1" style="font-size:.68rem; letter-spacing:.6px; text-transform:uppercase;">
                <i class="bi bi-people-fill me-1"></i> Agenda de Estudiantes
              </span>
            </div>
            <h2 class="fw-bold text-white mb-1">Mis Estudiantes Confirmados</h2>
            <p class="text-white-50 mb-0 d-flex flex-wrap gap-2 align-items-center">
              <span><i class="bi bi-book me-1"></i><?= $totalMaterias ?> materia(s) con sesiones confirmadas</span>
              <span class="d-none d-md-inline">•</span>
              <span><i class="bi bi-calendar-heart me-1"></i><?= $totalProximas ?> sesión(es) próximas</span>
              <span class="d-none d-md-inline">•</span>
              <span><i class="bi bi-mortarboard me-1"></i><?= $totalEstudiantes ?> estudiante(s) distinto(s)</span>
            </p>
          </div>
        </div>
        <a href="/views/tutor/panel.php" class="btn btn-light fw-bold d-flex align-items-center gap-2 shadow-sm">
          <i class="bi bi-arrow-left-circle"></i>
          <span>Volver al Panel</span>
        </a>
      </div>
    </div>
  </div>

  <?php if ($totalProximas === 0): ?>
    <div class="col-12">
      <div class="alert alert-light border text-center text-muted rounded-3 py-5">
        <i class="bi bi-people d-block fs-3 mb-2 text-secondary"></i>
        Aún no tienes sesiones confirmadas próximas. Cuando aceptes solicitudes de tus estudiantes, aparecerán aquí agrupadas por materia y día de la semana.
      </div>
    </div>
  <?php else: ?>
    <!-- Tarjeta por cada materia -->
    <?php foreach ($porMateria as $m): ?>
      <div class="col-12">
        <div class="card card-custom shadow-sm overflow-hidden">
          <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 d-flex align-items-center gap-2">
              <i class="bi bi-journal-bookmark-fill text-primary"></i>
              <span><?= htmlspecialchars($m['nombre']) ?></span>
            </h5>
            <span class="badge text-bg-light border px-3 py-2"><?= count($m['sesiones']) ?> sesión(es)</span>
          </div>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light text-muted text-uppercase" style="font-size:.72rem; letter-spacing:.5px;">
                <tr>
                  <th class="ps-4">Fecha</th>
                  <th>Hora</th>
                  <th>Estudiante</th>
                  <th>Modalidad</th>
                  <th class="pe-4">Lugar / Enlace</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $diaActual = null;
                foreach ($m['sesiones'] as $s):
                ?>
                  <?php if ($s['orden_dia'] !== $diaActual): $diaActual = $s['orden_dia']; ?>
                    <tr>
                      <td colspan="5" class="bg-light text-uppercase">
                        <span class="fw-bold text-dark small"><i class="bi bi-calendar-week me-1"></i><?= htmlspecialchars($s['dia_semana']) ?></span>
                        <span class="text-muted small ms-2"><?= count(array_filter($m['sesiones'], fn($x) => $x['orden_dia'] === $diaActual)) ?> sesión(es)</span>
                      </td>
                    </tr>
                  <?php endif; ?>
                  <tr>
                    <td class="ps-4 text-nowrap"><?= date('d/m/Y', strtotime($s['fecha'])) ?></td>
                    <td class="text-nowrap">
                      <i class="bi bi-clock me-1 text-muted"></i><?= substr($s['hora_inicio'], 0, 5) ?> - <?= substr($s['hora_fin'], 0, 5) ?>
                    </td>
                    <td>
                      <div class="d-flex align-items-center gap-2">
                        <?= avatarHTML($s['est_foto'] ?? '', iniciales($s['est_nombre'], $s['est_apellido']), 'avatar-md', 'width:34px; height:34px; font-size:.72rem;') ?>
                        <div class="lh-sm">
                          <div class="fw-semibold text-dark small"><?= htmlspecialchars($s['est_nombre'] . ' ' . $s['est_apellido']) ?></div>
                          <div class="text-muted" style="font-size:.72rem;"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($s['est_correo'] ?? '') ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <?php if ($s['modalidad'] === 'virtual'): ?>
                        <span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info-subtle rounded-pill px-3 py-1"><i class="bi bi-camera-video me-1"></i>Virtual</span>
                      <?php else: ?>
                        <span class="badge bg-light text-dark border rounded-pill px-3 py-1"><i class="bi bi-building me-1"></i>Presencial</span>
                      <?php endif; ?>
                    </td>
                    <td class="pe-4">
                      <?php if (!empty($s['lugar_o_enlace'])): ?>
                        <?php if ($s['modalidad'] === 'virtual'): ?>
                          <a href="<?= htmlspecialchars($s['lugar_o_enlace']) ?>" target="_blank" rel="noopener" class="small text-break"><i class="bi bi-link-45deg me-1"></i><?= htmlspecialchars($s['lugar_o_enlace']) ?></a>
                        <?php else: ?>
                          <span class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($s['lugar_o_enlace']) ?></span>
                        <?php endif; ?>
                      <?php else: ?>
                        <span class="text-muted small fst-italic">—</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>