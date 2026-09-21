<?php
// =========================================================
// VISTA: LISTADO DE TUTORES (views/tutores/listar.php)
// ---------------------------------------------------------
// Requiere sesión. Muestra métricas (tutores, materias
// asignadas y bloques horarios) y la tabla de docentes tutores
// con especialidad, contacto, materias y horarios. Cada fila
// enlaza a tutores_disponibilidad.php?id=... para gestionar
// sus horarios. Incluye buscador en vivo.
// Variables del controlador (controllers/tutores_listar.php):
//   $tutores (con total_materias y total_horarios)
//   $resenasPorTutor (id_tutor => reseñas con calificacion/comentario)
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('administrador');
$tituloPagina = 'Gestión de Docentes Tutores - UPDS';
include __DIR__ . '/../layouts/header.php';

$totalMaterias = array_sum(array_column($tutores, 'total_materias'));
$totalHorarios = array_sum(array_column($tutores, 'total_horarios'));
?>

<!-- Banda de cabecera -->
<div class="hero-band p-4 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge text-bg-light text-dark border px-3 py-1" style="font-size:.68rem; letter-spacing:.6px; text-transform:uppercase;">
          <i class="bi bi-mortarboard me-1"></i> Planta Docente
        </span>
      </div>
      <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-person-video3"></i>
        <span>Docentes Tutores Académicos</span>
      </h2>
      <p class="text-white-50 mb-0">Cuerpo docente capacitado para brindar asesorías y reforzamiento académico.</p>
    </div>
    <a href="usuarios_crear.php" class="btn btn-warning text-dark fw-bold d-flex align-items-center gap-2 shadow-sm px-3 py-2 rounded-3">
      <i class="bi bi-person-plus-fill"></i>
      <span>Nuevo Tutor</span>
    </a>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-indigo text-indigo"><i class="bi bi-person-video3"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= count($tutores) ?></h4>
          <small class="text-muted">Docentes tutores</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico bg-primary bg-opacity-10 text-primary"><i class="bi bi-book-fill"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalMaterias ?></h4>
          <small class="text-muted">Asignaturas a cargo</small>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-3">
    <div class="card card-custom stat-card p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="stat-ico text-ok"><i class="bi bi-clock-history"></i></div>
        <div>
          <h4 class="fw-bold mb-0 text-dark"><?= $totalHorarios ?></h4>
          <small class="text-muted">Bloques semanales</small>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="card card-custom shadow-sm overflow-hidden">
  <div class="card-header bg-white py-3 border-0 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
    <div class="input-group" style="max-width: 340px;">
      <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
      <input type="text" id="buscadorTutores" class="form-control bg-light border-start-0" placeholder="Buscar tutor, especialidad o correo...">
    </div>
    <span class="badge text-bg-light border px-3 py-2"><?= count($tutores) ?> docente(s)</span>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="tablaTutores">
      <thead class="table-light text-muted text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.5px;">
        <tr>
          <th class="ps-4">Tutor Docente</th>
          <th>Especialidad</th>
          <th>Contacto</th>
          <th>Asignaturas</th>
          <th>Disponibilidad</th>
          <th>Reseñas</th>
          <th class="text-end pe-4">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($tutores as $t): ?>
          <tr>
            <td class="ps-4">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar-md" style="background:linear-gradient(135deg,#223B87,#2f4ba7);"><?= iniciales($t['nombre'], $t['apellido']) ?></div>
                <div>
                  <div class="fw-bold text-dark">Prof. <?= htmlspecialchars($t['nombre'] . ' ' . $t['apellido']) ?></div>
                  <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($t['usuario']) ?></small>
                </div>
              </div>
            </td>
            <td>
              <span class="fw-medium text-secondary"><?= htmlspecialchars($t['especialidad'] ?? 'Docencia Universitaria') ?></span>
            </td>
            <td>
              <div><i class="bi bi-envelope me-1 text-muted"></i><?= htmlspecialchars($t['correo']) ?></div>
              <?php if (!empty($t['telefono'])): ?>
                <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($t['telefono']) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1">
                <i class="bi bi-book me-1"></i><?= $t['total_materias'] ?> materias
              </span>
            </td>
            <td>
              <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1">
                <i class="bi bi-clock me-1"></i><?= $t['total_horarios'] ?> bloques
              </span>
            </td>
            <td>
              <?php
                $resenasTutor = $resenasPorTutor[$t['id_tutor']] ?? [];
                $totResenas = count($resenasTutor);
                $promResenas = $totResenas > 0
                  ? round(array_sum(array_column($resenasTutor, 'calificacion')) / $totResenas, 1)
                  : 0;
              ?>
              <?php if ($totResenas > 0): ?>
                <div class="d-flex align-items-center gap-2">
                  <span class="fw-bold text-dark"><?= number_format($promResenas, 1, ',', '') ?></span>
                  <span class="text-warning small">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="bi bi-star<?= $i <= round($promResenas) ? '-fill' : '' ?>"></i>
                    <?php endfor; ?>
                  </span>
                </div>
                <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none"
                        data-bs-toggle="modal" data-bs-target="#modalResenas<?= $t['id_tutor'] ?>">
                  <i class="bi bi-chat-quote me-1"></i><?= $totResenas ?> reseña(s)
                </button>
              <?php else: ?>
                <span class="text-muted small"><i class="bi bi-star me-1"></i>Sin reseñas</span>
              <?php endif; ?>
            </td>
            <td class="text-end pe-4">
              <a href="tutores_disponibilidad.php?id=<?= $t['id_tutor'] ?>" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1">
                <i class="bi bi-sliders"></i>
                <span>Gestionar horarios</span>
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($tutores)): ?>
          <tr>
            <td colspan="7" class="text-center py-5 text-muted">
              <i class="bi bi-person-x fs-1 d-block mb-2 text-secondary"></i>
              No hay tutores registrados en el sistema.
            </td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modales de reseñas: una por tutor -->
<?php foreach ($tutores as $t): ?>
  <?php $resenasTutor = $resenasPorTutor[$t['id_tutor']] ?? []; ?>
  <div class="modal fade" id="modalResenas<?= $t['id_tutor'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content" style="border:none; border-radius:18px;">
        <div class="modal-header border-0 pb-0">
          <div>
            <div class="d-flex align-items-center gap-3">
              <div class="avatar-md" style="background:linear-gradient(135deg,#223B87,#2f4ba7);"><?= iniciales($t['nombre'], $t['apellido']) ?></div>
              <div>
                <h5 class="fw-bold mb-1 text-dark">Prof. <?= htmlspecialchars($t['nombre'] . ' ' . $t['apellido']) ?></h5>
                <div class="text-muted small"><?= $totResenas = count($resenasTutor) ?> reseña(s) recibida(s)</div>
              </div>
            </div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body p-4">
          <?php if ($totResenas === 0): ?>
            <div class="text-center text-muted py-4">
              <i class="bi bi-chat-quote fs-1 d-block mb-2 text-secondary"></i>
              Este tutor aún no tiene reseñas de estudiantes.
            </div>
          <?php else: ?>
            <div class="d-flex flex-column gap-3">
              <?php foreach ($resenasTutor as $r): ?>
                <div class="p-3 rounded-3 border bg-light">
                  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                    <div class="fw-semibold text-dark small">
                      <?= htmlspecialchars($r['nombre_materia']) ?>
                      <span class="text-muted fw-normal">— <?= htmlspecialchars($r['est_nombre'] . ' ' . $r['est_apellido']) ?></span>
                    </div>
                    <span class="text-warning small" style="font-size:.9rem;">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="bi bi-star<?= $i <= $r['calificacion'] ? '-fill' : '' ?>"></i>
                      <?php endfor; ?>
                    </span>
                  </div>
                  <?php if (!empty($r['comentario'])): ?>
                    <p class="small text-secondary mb-1"><i class="bi bi-quote me-1 text-muted"></i><?= htmlspecialchars($r['comentario']) ?></p>
                  <?php else: ?>
                    <p class="small text-muted fst-italic mb-1">Sin comentario adicional.</p>
                  <?php endif; ?>
                  <small class="text-muted">
                    <i class="bi bi-calendar3 me-1"></i><?= date('d/m/Y', strtotime($r['fecha_evaluacion'])) ?>
                  </small>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<script>
  document.getElementById('buscadorTutores')?.addEventListener('keyup', function() {
    const valor = this.value.toLowerCase();
    const filas = document.querySelectorAll('#tablaTutores tbody tr');
    filas.forEach(fila => {
      fila.style.display = fila.textContent.toLowerCase().includes(valor) ? '' : 'none';
    });
  });
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>