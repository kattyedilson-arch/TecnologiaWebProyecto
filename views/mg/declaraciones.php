<?php
// =========================================================
// VISTA: DECLARACIONES MG (views/mg/declaraciones.php)
// ---------------------------------------------------------
// Banda de revisiones. Pestañas por estado + filtro de periodo.
// El auxiliar pasa a revisión; el coordinador aprueba o
// rechaza (con modal de motivo). Variables del controlador:
//   $declaraciones, $periodos, $filtroEstado, $filtroPeriodo,
//   $conteos, $puedeAprobar, $puedeTramitar
// =========================================================
require_once __DIR__ . '/../layouts/header.php';

$estadosEtiqueta = [
    'borrador'   => ['bg-warning text-dark',  'Borrador'],
    'enviada'    => ['bg-primary text-white', 'Enviada'],
    'en_revision'=> ['bg-indigo text-white',  'En revisión'],
    'aprobada'   => ['bg-success text-white', 'Aprobada'],
    'rechazada'  => ['bg-danger text-white',  'Rechazada'],
    'cancelada'  => ['bg-secondary text-white','Cancelada'],
];
$ordenEstados = ['enviada', 'en_revision', 'borrador', 'aprobada', 'rechazada', 'cancelada'];
$urlBase = '/controllers/mg_declaraciones.php';
?>

<div class="hero-card mb-4">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
      <h2 class="h4 fw-bold mb-1"><i class="bi bi-file-earmark-text-fill me-2"></i>Declaraciones de Modalidad</h2>
      <p class="mb-0 text-muted">Revisá, marcá y resolvé las solicitudes de los estudiantes.</p>
    </div>
    <!-- Filtro de periodo -->
    <form method="GET" action="<?= $urlBase ?>" class="d-flex align-items-center gap-2">
      <select name="periodo" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
        <option value="0">Todos los periodos</option>
        <?php foreach ($periodos as $p): ?>
          <option value="<?= (int)$p['id_periodo'] ?>" <?= $filtroPeriodo === (int)$p['id_periodo'] ? 'selected' : '' ?>><?= e($p['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if ($filtroPeriodo > 0): ?>
        <a href="<?= $urlBase ?>" class="btn btn-sm btn-light border"><i class="bi bi-x-lg"></i></a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Pestañas de estado -->
<div class="d-flex flex-wrap gap-2 mb-3">
  <a href="<?= $urlBase ?><?= $filtroPeriodo > 0 ? '?periodo=' . $filtroPeriodo : '' ?>"
     class="btn btn-sm rounded-pill <?= $filtroEstado === '' ? 'btn-primary' : 'btn-light border' ?>">
    Todas <span class="badge <?= $filtroEstado === '' ? 'text-bg-light' : 'text-bg-secondary' ?> ms-1"><?= (int)$conteos['total'] ?></span>
  </a>
  <?php foreach ($ordenEstados as $est): ?>
    <?php [$clase, $texto] = $estadosEtiqueta[$est]; ?>
    <a href="<?= $urlBase ?>?estado=<?= $est ?><?= $filtroPeriodo > 0 ? '&periodo=' . $filtroPeriodo : '' ?>"
       class="btn btn-sm rounded-pill <?= $filtroEstado === $est ? $clase : 'btn-light border' ?>">
      <?= $texto ?> <span class="badge <?= $filtroEstado === $est ? 'text-bg-light' : 'text-bg-secondary' ?> ms-1"><?= (int)$conteos[$est] ?></span>
    </a>
  <?php endforeach; ?>
</div>

<div class="card card-custom p-3">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light text-muted text-uppercase" style="font-size:.72rem; letter-spacing:.5px;">
        <tr>
          <th>Estudiante</th>
          <th>Modalidad</th>
          <th>Título</th>
          <th>Periodo</th>
          <th>Estado</th>
          <th class="text-end pe-2">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($declaraciones)): ?>
          <tr>
            <td colspan="6" class="text-center py-5 text-muted">
              <i class="bi bi-inbox fs-1 d-block mb-2 text-secondary"></i>
              No hay declaraciones<?= $filtroEstado ? ' con estado "' . $estadosEtiqueta[$filtroEstado][1] . '"' : '' ?>.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($declaraciones as $d): ?>
            <?php [$badgeClase, $badgeTexto] = $estadosEtiqueta[$d['estado']] ?? ['bg-secondary text-white', $d['estado']]; ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= e($d['nombre'] . ' ' . $d['apellido']) ?></div>
                <small class="text-muted"><?= e($d['carrera'] ?: '') ?></small>
                <div class="small text-muted"><?= e($d['correo']) ?></div>
              </td>
              <td>
                <span class="fw-semibold d-block"><?= e($d['modalidad_nombre']) ?></span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle"><?= e($d['modalidad_codigo']) ?></span>
              </td>
              <td>
                <span class="small d-block text-truncate" style="max-width:220px;" title="<?= e($d['titulo_proyecto'] ?? '') ?>">
                  <?= e($d['titulo_proyecto'] ?: '<i class="text-muted">Sin título aún</i>') ?>
                </span>
                <?php if (!empty($d['empresa_org']) || !empty($d['tutor_facultativo'])): ?>
                  <small class="text-muted d-block"><?= e($d['empresa_org']) ?><?= $d['empresa_org'] && $d['tutor_facultativo'] ? ' • ' : '' ?><?= e($d['tutor_facultativo']) ?></small>
                <?php endif; ?>
                <?php if (!empty($d['observacion'])): ?>
                  <small class="text-danger d-block" title="<?= e($d['observacion']) ?>"><i class="bi bi-exclamation-circle me-1"></i><?= e(mb_strimwidth($d['observacion'], 0, 60, '…')) ?></small>
                <?php endif; ?>
              </td>
              <td><span class="small"><?= e($d['periodo_nombre']) ?></span></td>
              <td><span class="badge rounded-pill px-3 py-1 <?= $badgeClase ?>"><?= $badgeTexto ?></span></td>
              <td class="text-end pe-2">
                <div class="d-inline-flex gap-1">
                  <?php if ($d['estado'] === 'enviada' && $puedeTramitar): ?>
                    <form method="POST" action="/controllers/declaraciones_revisar.php"
                          data-confirm="¿Marcar la declaración como en revisión?"
                          data-confirm-title="Iniciar revisión"
                          data-confirm-text="El estudiante verá el nuevo estado.">
                      <?= campoCsrf() ?>
                      <input type="hidden" name="id" value="<?= (int)$d['id_declaracion'] ?>">
                      <input type="hidden" name="accion" value="en_revision">
                      <button type="submit" class="btn btn-sm btn-indigo d-flex align-items-center gap-1"><i class="bi bi-eye"></i> Revisar</button>
                    </form>
                  <?php endif; ?>

                  <?php if (in_array($d['estado'], ['enviada', 'en_revision'], true) && $puedeAprobar): ?>
                    <form method="POST" action="/controllers/declaraciones_revisar.php"
                          data-confirm="¿Aprobar la declaración de <?= e($d['nombre'] . ' ' . $d['apellido']) ?>?"
                          data-confirm-title="Aprobar declaración"
                          data-confirm-text="El estudiante recibirá la notificación de aprobación."
                          data-confirm-color="#16a34a">
                      <?= campoCsrf() ?>
                      <input type="hidden" name="id" value="<?= (int)$d['id_declaracion'] ?>">
                      <input type="hidden" name="accion" value="aprobar">
                      <button type="submit" class="btn btn-sm btn-success d-flex align-items-center gap-1"><i class="bi bi-check-lg"></i> Aprobar</button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-danger d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#rechazarModal<?= (int)$d['id_declaracion'] ?>">
                      <i class="bi bi-x-lg"></i> Rechazar
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>

            <!-- Modal de rechazo con motivo -->
            <?php if (in_array($d['estado'], ['enviada', 'en_revision'], true) && $puedeAprobar): ?>
              <div class="modal fade" id="rechazarModal<?= (int)$d['id_declaracion'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                  <form method="POST" action="/controllers/declaraciones_revisar.php" class="modal-content">
                    <div class="modal-header">
                      <h5 class="modal-title fw-bold"><i class="bi bi-x-octagon-fill text-danger me-1"></i> Rechazar declaración</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                      <p class="small text-muted mb-3">
                        Vas a rechazar la declaración de <strong><?= e($d['nombre'] . ' ' . $d['apellido']) ?></strong>.
                        El estudiante recibirá el motivo y podrá corregir y reenviar.
                      </p>
                      <label class="form-label small fw-semibold">Motivo del rechazo *</label>
                      <textarea name="observacion" class="form-control" rows="3" required placeholder="Ej: Falta el título definitivo del proyecto."></textarea>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                      <?= campoCsrf() ?>
                      <input type="hidden" name="id" value="<?= (int)$d['id_declaracion'] ?>">
                      <input type="hidden" name="accion" value="rechazar">
                      <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg me-1"></i>Rechazar declaración</button>
                    </div>
                  </form>
                </div>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>