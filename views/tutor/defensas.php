<?php
// =========================================================
// VISTA: MIS DEFENSAS DEL DOCENTE (views/tutor/defensas.php)
// ---------------------------------------------------------
// Bandeja del docente nombrado como jurado en el módulo de
// Modalidades de Grado. Autocontenida (conexión + modelos).
// Muestra dos bloques: defensas pendientes de calificar y
// defensas ya firmadas, con el detalle del tribunal (rol),
// avances de avales y, si procede, nota y resultado final.
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
require_once __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/../../models/JuradoModel.php';

// Control de acceso por rol: solo el tutor/docente entra aquí
if (($_SESSION['rol'] ?? '') !== 'tutor') {
    redirigirAlPanel();
    exit;
}

$idUsuario = (int)($_SESSION['id_usuario'] ?? 0);
$juradoModel = new JuradoModel($pdo);
$misDefensas = $juradoModel->obtenerPorDocente($idUsuario);

// Se separan las defensas por avance: pendientes (sin acta firmada)
// y firmadas (con nota y resultado ya definidos).
$pendientes = array_values(array_filter(
    $misDefensas,
    fn($d) => ($d['acta_estado'] ?? '') !== 'firmada'
));
$firmadas = array_values(array_filter(
    $misDefensas,
    fn($d) => ($d['acta_estado'] ?? '') === 'firmada'
));

$etiquetasRol = [
    'presidente' => 'Presidente',
    'titular'    => 'Titular',
    'suplente'   => 'Suplente',
];

function defensaEstadoActa($estado)
{
    return match ($estado) {
        'abierta' => '<span class="badge text-bg-warning border"><i class="bi bi-hourglass-split me-1"></i>Acta abierta</span>',
        'firmada' => '<span class="badge text-bg-success border"><i class="bi bi-file-earmark-check me-1"></i>Acta firmada</span>',
        default   => '<span class="badge text-bg-secondary border"><i class="bi bi-file-earmark me-1"></i>Sin acta</span>',
    };
}

$tituloPagina = 'Mis Defensas - UPDS';
include __DIR__ . '/../layouts/header.php';
?>

<div class="d-flex align-items-center gap-2 mb-3">
  <span class="text-secondary"><i class="bi bi-people-fill fs-5"></i></span>
  <div>
    <h1 class="h4 fw-bold mb-0">Mis Defensas (Modalidad de Grado)</h1>
    <p class="text-muted small mb-0">Tribunales a los que perteneces como jurado.</p>
  </div>
</div>

<!-- ============ DEFENSAS PENDIENTES ============ -->
<div class="card mb-4">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <span class="fw-bold">
      <i class="bi bi-hourglass-split me-1 text-warning"></i> Defensas pendientes
    </span>
    <span class="badge text-bg-secondary"><?= count($pendientes) ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Estudiante</th>
          <th>Modalidad</th>
          <th>Proyecto</th>
          <th>Tu rol</th>
          <th>Avales</th>
          <th>Acta</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($pendientes as $d): ?>
          <tr>
            <td>
              <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold"><?= htmlspecialchars(trim($d['nombre'] . ' ' . $d['apellido'])) ?></span>
                <span class="text-muted small d-none d-md-inline">• <?= htmlspecialchars($d['nombre_carrera'] ?? '—') ?></span>
              </div>
              <div class="text-muted small"><i class="bi bi-calendar3 me-1"></i><?= htmlspecialchars($d['periodo_nombre']) ?></div>
            </td>
            <td>
              <span class="badge text-bg-light border"><?= htmlspecialchars($d['modalidad_codigo']) ?></span>
              <span class="d-block text-muted small"><?= htmlspecialchars($d['modalidad_nombre']) ?></span>
            </td>
            <td class="text-muted small"><?= htmlspecialchars(mb_strimwidth($d['titulo_proyecto'] ?? '—', 0, 48, '…')) ?></td>
            <td><span class="badge bg-indigo text-indigo border"><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($etiquetasRol[$d['rol_jurado']] ?? $d['rol_jurado']) ?></span></td>
            <td style="min-width:130px">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small"><?= (int)$d['avales_entregados'] ?> / <?= (int)$d['avales_total'] ?> entregados</span>
              </div>
              <div class="progress" style="height:6px">
                <div class="progress-bar bg-success" style="width:<?= $d['avales_total'] > 0 ? round(100 * $d['avales_entregados'] / $d['avales_total']) : 0 ?>%"></div>
              </div>
            </td>
            <td><?= defensaEstadoActa($d['acta_estado'] ?? '') ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($pendientes)): ?>
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="bi bi-check2-circle fs-1 d-block mb-2 text-secondary"></i>
              No tienes defensas pendientes.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ============ DEFENSAS FIRMADAS ============ -->
<div class="card">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <span class="fw-bold">
      <i class="bi bi-file-earmark-check me-1 text-success"></i> Defensas calificadas
    </span>
    <span class="badge text-bg-secondary"><?= count($firmadas) ?></span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Estudiante</th>
          <th>Modalidad</th>
          <th>Tu rol</th>
          <th>Fecha defensa</th>
          <th>Nota final</th>
          <th>Resultado</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($firmadas as $d): ?>
          <tr>
            <td>
              <span class="fw-semibold"><?= htmlspecialchars(trim($d['nombre'] . ' ' . $d['apellido'])) ?></span>
              <span class="text-muted small d-block"><?= htmlspecialchars($d['nombre_carrera'] ?? '—') ?></span>
            </td>
            <td>
              <span class="badge text-bg-light border"><?= htmlspecialchars($d['modalidad_codigo']) ?></span>
            </td>
            <td><span class="badge bg-indigo text-indigo border"><?= htmlspecialchars($etiquetasRol[$d['rol_jurado']] ?? $d['rol_jurado']) ?></span></td>
            <td class="text-muted small">
              <?= $d['fecha_defensa'] ? date('d/m/Y H:i', strtotime($d['fecha_defensa'])) : '—' ?>
              <span class="d-block"><?= htmlspecialchars($d['lugar'] ?? '') ?></span>
            </td>
            <td class="fw-bold fs-6"><?= number_format((float)$d['nota_final'], 2) ?></td>
            <td>
              <?php if (($d['resultado'] ?? '') === 'aprobado'): ?>
                <span class="badge text-bg-success"><i class="bi bi-check-circle me-1"></i>Aprobado</span>
              <?php else: ?>
                <span class="badge text-bg-danger"><i class="bi bi-x-circle me-1"></i>Reprobado</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($firmadas)): ?>
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="bi bi-file-earmark fs-1 d-block mb-2 text-secondary"></i>
              Aún no hay defensas calificadas.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="text-muted small mt-3">
  <i class="bi bi-info-circle me-1"></i> Cuando el coordinador firme el acta de una defensa, esta se moverá al bloque "Defensas calificadas".
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>