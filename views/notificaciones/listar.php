<?php
// =========================================================
// VISTA: LISTADO DE NOTIFICACIONES (views/notificaciones/listar.php)
// ---------------------------------------------------------
// Tabla de avisos del usuario en sesión. Cada fila no leída
// ofrece un botón POST para marcar la leída; arriba hay una
// acción masiva. La campanita del header muestra el resumen.
// Variables: $notificaciones, $noLeidas (controlador).
// =========================================================
require_once __DIR__ . '/layouts/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="h5 fw-bold mb-1">Mis Notificaciones</h2>
    <p class="text-muted small mb-0">
      <?= $noLeidas > 0
         ? 'Tienes <strong class="text-danger">' . $noLeidas . ' sin leer</strong>.'
         : 'Estás al día: no tienes notificaciones pendientes.' ?>
    </p>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <form method="POST" action="/controllers/notificaciones_estado.php">
      <input type="hidden" name="accion" value="todas">
      <?= campoCsrf() ?>
      <button type="submit" class="btn btn-outline-primary btn-sm" <?= $noLeidas === 0 ? 'disabled' : '' ?>>
        <i class="bi bi-check2-all me-1"></i>Marcar todas como leídas
      </button>
    </form>
  </div>
</div>

<div class="card-custom p-3">
  <?php if (empty($notificaciones)): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-bell-slash" style="font-size:2.5rem;"></i>
      <p class="mt-3 mb-0">Aún no tienes notificaciones.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:44px;"></th>
            <th>Notificación</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th style="width:140px;" class="text-end">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($notificaciones as $n):
            $icono = $n['tipo'] === 'tutoria'   ? 'calendar-check-fill' :
                     ($n['tipo'] === 'modalidad' ? 'file-earmark-text-fill' :
                     ($n['tipo'] === 'acta'      ? 'clipboard-check-fill' : 'info-circle-fill'));
            $esLeida = (bool)$n['leida'];
          ?>
          <tr class="<?= $esLeida ? '' : 'table-light' ?>">
            <td class="text-center">
              <i class="bi bi-<?= $icono ?> fs-5 <?= $esLeida ? 'text-muted' : 'text-primary' ?>"></i>
            </td>
            <td>
              <span class="d-block fw-semibold"><?= e($n['titulo']) ?></span>
              <span class="d-block text-muted small"><?= e($n['mensaje'] ?? '') ?></span>
            </td>
            <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($n['fecha_creacion'])) ?></td>
            <td>
              <?php if ($esLeida): ?>
                <span class="badge badge-state bg-light text-muted border"><i class="bi bi-check2-all me-1"></i>Leída</span>
              <?php else: ?>
                <span class="badge badge-state bg-primary text-white"><i class="bi bi-bell-fill me-1"></i>Nueva</span>
              <?php endif; ?>
            </td>
            <td class="text-end">
              <?php if ($n['enlace']): ?>
                <a href="<?= htmlspecialchars($n['enlace']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-up-right me-1"></i>Ver</a>
              <?php endif; ?>
              <?php if (!$esLeida): ?>
                <form method="POST" action="/controllers/notificaciones_estado.php" class="d-inline">
                  <input type="hidden" name="accion" value="una">
                  <input type="hidden" name="id" value="<?= (int)$n['id_notificacion'] ?>">
                  <?= campoCsrf() ?>
                  <button type="submit" class="btn btn-sm btn-light border">Marcar leída</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layouts/footer.php'; ?>