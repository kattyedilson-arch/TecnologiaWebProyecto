<?php
// =========================================================
// VISTA: OFERTAS PARA EL TUTOR (views/tutor/ofertas.php)
// ---------------------------------------------------------
// Panel donde el tutor ve ofertas abiertas y puede aceptarlas
// o rechazarlas. También ve sus respuestas anteriores.
// Variables del controlador:
//   $ofertasAbiertas, $misRespuestas, $tutorActual
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('tutor');
$tituloPagina = 'Ofertas Disponibles - UPDS';
include __DIR__ . '/../layouts/header.php';
?>

<!-- Banda de cabecera -->
<div class="hero-band p-4 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge text-bg-light text-dark border px-3 py-1" style="font-size:.68rem; letter-spacing:.6px; text-transform:uppercase;">
          <i class="bi bi-megaphone me-1"></i> Tutor
        </span>
      </div>
      <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-calendar-check"></i>
        <span>Ofertas Disponibles</span>
      </h2>
      <p class="text-white-50 mb-0">Prof. <?= htmlspecialchars($tutorActual['nombre'] . ' ' . $tutorActual['apellido']) ?> &bull; Revisa y acepta las ofertas de materias del administrador.</p>
    </div>
    <a href="/views/tutor/panel.php" class="btn btn-light d-flex align-items-center gap-1">
      <i class="bi bi-arrow-left"></i> Volver
    </a>
  </div>
</div>

<div class="row g-4">
  <!-- Columna 1: Ofertas abiertas -->
  <div class="col-lg-7">
    <div class="card card-custom p-4 h-100">
      <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
        <span class="stat-ico bg-success text-success" style="width:38px; height:38px; font-size:1rem;"><i class="bi bi-lightning"></i></span>
        <div class="flex-grow-1">
          <h5 class="fw-bold mb-0">Ofertas Abiertas</h5>
          <small class="text-muted">Materias y horarios que el administrador puso a tu disposición.</small>
        </div>
        <span class="badge text-bg-light border px-3 py-2"><?= count($ofertasAbiertas) ?> disponible(s)</span>
      </div>

      <?php if (!empty($ofertasAbiertas)): ?>
        <div class="list-group list-group-flush">
          <?php foreach ($ofertasAbiertas as $o): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-3">
              <div class="d-flex align-items-center gap-3">
                <span class="stat-ico bg-primary bg-opacity-10 text-primary" style="width:42px; height:42px; font-size:1rem;">
                  <i class="bi bi-book"></i>
                </span>
                <div>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($o['nombre_materia']) ?></div>
                  <div class="small">
                    <span class="text-muted"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($o['nombre_turno']) ?> (<?= substr($o['turno_hora_inicio'], 0, 5) ?> - <?= substr($o['turno_hora_fin'], 0, 5) ?>)</span>
                  </div>
                </div>
              </div>
              <div class="d-flex gap-2">
                <form method="POST"
                      data-confirm="Se agregará <b><?= htmlspecialchars($o['nombre_materia']) ?></b> (<?= htmlspecialchars($o['nombre_turno']) ?> <?= substr($o['turno_hora_inicio'], 0, 5) ?> - <?= substr($o['turno_hora_fin'], 0, 5) ?>) a tu disponibilidad."
                      data-confirm-title="¿Aceptar esta oferta?"
                      data-confirm-icon="success"
                      data-confirm-text="Sí, aceptar"
                      data-confirm-color="#059669">
                  <?= campoCsrf() ?>
                  <input type="hidden" name="accion" value="aceptar_oferta">
                  <input type="hidden" name="id_oferta" value="<?= $o['id_oferta'] ?>">
                  <button type="submit" class="btn btn-success btn-sm" title="Aceptar oferta">
                    <i class="bi bi-check-lg me-1"></i> Aceptar
                  </button>
                </form>
                <form method="POST"
                      data-confirm="Se notificará al administrador que no tomarás la materia <b><?= htmlspecialchars($o['nombre_materia']) ?></b>."
                      data-confirm-title="¿Rechazar esta oferta?"
                      data-confirm-icon="question"
                      data-confirm-text="Sí, rechazar"
                      data-confirm-color="#dc2626">
                  <?= campoCsrf() ?>
                  <input type="hidden" name="accion" value="rechazar_oferta">
                  <input type="hidden" name="id_oferta" value="<?= $o['id_oferta'] ?>">
                  <button type="submit" class="btn btn-outline-danger btn-sm" title="Rechazar oferta">
                    <i class="bi bi-x-lg"></i>
                  </button>
                </form>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="p-4 bg-light rounded-3 text-center text-muted">
          <i class="bi bi-calendar-x d-block fs-1 mb-2"></i>
          <p class="mb-0">No hay ofertas disponibles en este momento.</p>
          <small>El administrador publicará nuevas ofertas pronto.</small>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Columna 2: Mis respuestas -->
  <div class="col-lg-5">
    <div class="card card-custom p-4">
      <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
        <span class="stat-ico bg-info bg-opacity-10 text-info" style="width:38px; height:38px; font-size:1rem;"><i class="bi bi-clock-history"></i></span>
        <div class="flex-grow-1">
          <h5 class="fw-bold mb-0">Mis Respuestas</h5>
          <small class="text-muted">Ofertas que ya aceptaste o rechazaste.</small>
        </div>
      </div>

      <?php if (!empty($misRespuestas)): ?>
        <div class="list-group list-group-flush">
          <?php foreach ($misRespuestas as $r): ?>
            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
              <div>
                <div class="fw-semibold small"><?= htmlspecialchars($r['nombre_materia']) ?></div>
                <div class="small text-muted">
                  <?= htmlspecialchars($r['nombre_turno']) ?>
                  (<?= substr($r['turno_hora_inicio'], 0, 5) ?> - <?= substr($r['turno_hora_fin'], 0, 5) ?>)
                </div>
              </div>
              <div class="d-flex align-items-center gap-2">
                <?php if ($r['estado'] === 'aceptada'): ?>
                  <span class="badge bg-success">Aceptada</span>
                  <?php if ($r['oferta_estado'] === 'asignada'): ?>
                    <form method="POST"
                        data-confirm="Se eliminará la oferta de <b><?= htmlspecialchars($r['nombre_materia']) ?></b> de tu disponibilidad."
                        data-confirm-title="¿Cancelar esta aceptación?"
                        data-confirm-icon="warning"
                        data-confirm-text="Sí, cancelar"
                        data-confirm-color="#d97706">
                      <?= campoCsrf() ?>
                      <input type="hidden" name="accion" value="cancelar_aceptacion">
                      <input type="hidden" name="id_oferta" value="<?= $r['id_oferta'] ?>">
                      <button type="submit" class="btn btn-outline-warning btn-sm" title="Cancelar aceptación">
                        <i class="bi bi-arrow-counterclockwise"></i>
                      </button>
                    </form>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="badge bg-secondary">Rechazada</span>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="p-3 bg-light rounded-3 text-center text-muted small">
          <i class="bi bi-inbox d-block fs-3 mb-1"></i>
          Aún no has respondido a ninguna oferta.
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
