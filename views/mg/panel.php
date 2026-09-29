<?php
// =========================================================
// VISTA: PANEL DEL EQUIPO MG (views/mg/panel.php)
// ---------------------------------------------------------
// Resumen del proceso de Modalidades de Grado para
// coordinador_mg / auxiliar_mg. Variables del controlador:
//   $periodoActivo, $modalidadesPublicadas, $resumen
// (controllers/panel_mg.php)
// =========================================================
require_once __DIR__ . '/../layouts/header.php';

$rolInicial = ucwords(str_replace('_', ' ', $_SESSION['rol'] ?? 'equipo MG'));
?>

<div class="hero-card mb-4">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
      <h2 class="h4 fw-bold mb-1">
        <i class="bi bi-mortarboard-fill me-2"></i>Modalidades de Grado
      </h2>
      <p class="mb-0">Panel del <strong><?= e($rolInicial) ?></strong> — seguimiento del proceso de grado.</p>
    </div>
    <div class="text-end text-muted small">
      <i class="bi bi-calendar3 me-1"></i>
      Periodo activo:
      <span class="fw-semibold text-dark"><?= $periodoActivo ? e($periodoActivo['nombre']) : '— sin periodo abierto' ?></span>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="card card-custom p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon-stats rounded-3"><i class="bi bi-journal-bookmark-fill"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= (int)$modalidadesPublicadas ?></div>
          <div class="text-muted small">Modalidades publicadas</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card card-custom p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon-stats rounded-3 bg-warning bg-opacity-10 text-warning"><i class="bi bi-file-earmark-text"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= (int)$resumen['total'] ?></div>
          <div class="text-muted small">Declaraciones del periodo</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card card-custom p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon-stats rounded-3 bg-primary bg-opacity-10 text-primary"><i class="bi bi-hourglass-split"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= (int)$resumen['en_revision'] ?></div>
          <div class="text-muted small">En revisión</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="card card-custom p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="icon-stats rounded-3 bg-success bg-opacity-10 text-success"><i class="bi bi-patch-check-fill"></i></div>
        <div>
          <div class="fs-4 fw-bold"><?= (int)$resumen['aprobadas'] ?></div>
          <div class="text-muted small">Aprobadas</div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card card-custom p-4 text-center">
      <div class="display-5 mb-2">📚</div>
      <h6 class="fw-bold mb-1">Catálogo de modalidades</h6>
      <p class="text-muted small mb-3">Definí qué modalidades ven los estudiantes y cuáles están publicadas.</p>
      <a href="/controllers/admin_modalidades.php" class="btn btn-primary btn-sm w-100">
        <i class="bi bi-journal-bookmark-fill me-1"></i>Administrar catálogo
      </a>
    </div>
  </div>
  <?php if (tienePermiso('gestionar_periodos')): ?>
  <div class="col-lg-4">
    <div class="card card-custom p-4 text-center">
      <div class="display-5 mb-2">🗓️</div>
      <h6 class="fw-bold mb-1">Periodos académicos</h6>
      <p class="text-muted small mb-3">Abrí y cerrá los periodos en que los estudiantes declaran su modalidad.</p>
      <a href="/controllers/admin_periodos.php" class="btn btn-primary btn-sm w-100">
        <i class="bi bi-calendar-range me-1"></i>Administrar periodos
      </a>
    </div>
  </div>
  <?php endif; ?>
  <div class="col-lg-4">
    <div class="card card-custom p-4 text-center">
      <div class="display-5 mb-2">📨</div>
      <h6 class="fw-bold mb-1">Notificaciones</h6>
      <p class="text-muted small mb-3">Revisá avisos del sistema, como nuevas declaraciones o cambios de estado.</p>
      <a href="/controllers/notificaciones_listar.php" class="btn btn-primary btn-sm w-100">
        <i class="bi bi-bell me-1"></i>Ver notificaciones
      </a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>