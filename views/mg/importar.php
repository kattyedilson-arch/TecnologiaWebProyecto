<?php
// =========================================================
// VISTA: IMPORTAR PADRÓN MG (views/mg/importar.php)
// ---------------------------------------------------------
// HU-023. Formulario de subida del padrón CSV, tabla de
// previsualización fila a fila y registro histórico de
// importaciones con sus detalles.
// Variables: $importaciones, $previewFilas, $previewArchivo,
// $detalleImportacion, $detalleFilas (controllers/mg_importar.php)
// =========================================================
require_once __DIR__ . '/../layouts/header.php';

$clasesResultado = [
    'ok'              => 'bg-success bg-opacity-10 text-success border border-success-subtle',
    'pendiente_cuenta'=> 'bg-warning bg-opacity-10 text-warning border border-warning-subtle',
    'error'           => 'bg-danger bg-opacity-10 text-danger border border-danger-subtle',
    'omitida'         => 'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle',
];
$etiquetasResultado = [
    'ok'              => 'OK',
    'pendiente_cuenta'=> 'Pendiente de cuenta',
    'error'           => 'Error',
    'omitida'         => 'Omitida',
];
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <h2 class="h5 fw-bold mb-1">Importar padrón MG</h2>
    <p class="text-muted small mb-0">
      Sube el padrón CSV de estudiantes que cursarán Modalidades de Grado (HU-023).
    </p>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-4">
    <div class="card card-custom p-4">
      <h5 class="fw-bold mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-upload text-primary"></i> Subir padrón
      </h5>
      <p class="text-muted small mb-3">
        Archivo <code>.csv</code> (máx. 2 MB) con cabeceras:
        <code>registro_universitario</code>, <code>nombre</code>,
        <code>apellido</code>, <code>correo</code>, <code>modalidad</code>,
        <code>cohorte</code>. Delimitador <code>;</code> o <code>,</code>.
      </p>

      <form method="POST" action="/controllers/mg_importar.php" enctype="multipart/form-data">
        <?= campoCsrf() ?>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Archivo CSV</label>
          <input type="file" name="archivo" class="form-control" accept=".csv,text/csv" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">
          <i class="bi bi-eye me-1"></i> Previsualizar
        </button>
      </form>

      <hr>
      <div class="alert alert-light border small mb-0">
        <i class="bi bi-info-circle me-1 text-primary"></i>
        Si el registro universitario no tiene cuenta en el sistema, la fila
        queda como <strong>pendiente de cuenta</strong> y no se crea expediente.
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <?php if ($previewFilas !== null && $previewFilas !== []): ?>
      <?php
        $conteos = ['ok' => 0, 'error' => 0, 'pendiente_cuenta' => 0, 'omitida' => 0];
        foreach ($previewFilas as $f) { if (isset($conteos[$f['resultado']])) { $conteos[$f['resultado']]++; } }
      ?>
      <div class="card card-custom p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 px-2 py-1 mb-2">
          <div>
            <h6 class="fw-bold mb-1 d-flex align-items-center gap-2">
              <i class="bi bi-clipboard-data text-primary"></i> Previsualización
              <span class="badge bg-light text-dark border"><?= count($previewFilas) ?> fila(s)</span>
            </h6>
            <small class="text-muted"><?= e($previewArchivo) ?> — aún no se crea nada hasta confirmar.</small>
          </div>
          <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle"><?= $conteos['ok'] ?> OK</span>
          <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle"><?= $conteos['error'] ?> error</span>
          <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle"><?= $conteos['pendiente_cuenta'] ?> cuenta</span>
        </div>

        <div class="table-responsive" style="max-height:380px;">
          <table class="table table-sm align-middle">
            <thead class="sticky-top bg-white">
              <tr>
                <th>#</th>
                <th>Registro</th>
                <th>Estudiante</th>
                <th>Modalidad</th>
                <th>Cohorte</th>
                <th>Resultado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($previewFilas as $f): ?>
                <tr>
                  <td class="text-muted small"><?= (int)$f['fila'] ?></td>
                  <td class="small"><?= e($f['ru']) ?></td>
                  <td class="small"><?= e(trim($f['nombre'] . ' ' . $f['apellido'])) ?></td>
                  <td class="small"><?= e($f['modalidad'] ?: '—') ?></td>
                  <td class="small"><?= e($f['cohorte'] ?: '—') ?></td>
                  <td>
                    <span class="badge <?= $clasesResultado[$f['resultado']] ?? '' ?>">
                      <?= $etiquetasResultado[$f['resultado']] ?? e($f['resultado']) ?>
                    </span>
                    <?php if ($f['mensaje'] && $f['mensaje'] !== 'pendiente_cuenta'): ?>
                      <div class="text-muted" style="font-size:.68rem;"><?= e($f['mensaje']) ?></div>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <div class="d-flex gap-2 align-items-center mt-3">
          <form method="POST" action="/controllers/mg_importar.php" class="d-inline">
            <input type="hidden" name="accion" value="confirmar">
            <?= campoCsrf() ?>
            <button type="submit" class="btn btn-success">
              <i class="bi bi-check-lg me-1"></i> Confirmar y crear expedientes (<?= $conteos['ok'] ?>)
            </button>
          </form>
          <form method="POST" action="/controllers/mg_importar.php" class="d-inline"
                data-confirm="¿Descartar esta previsualización?">
            <input type="hidden" name="accion" value="cancelar">
            <?= campoCsrf() ?>
            <button type="submit" class="btn btn-light border text-danger"><i class="bi bi-x-lg"></i> Descartar</button>
          </form>
        </div>
      </div>
    <?php elseif ($detalleImportacion): ?>
      <div class="card card-custom p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center px-2 py-1 mb-2">
          <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-archive text-primary"></i>
            Detalle de <?= e($detalleImportacion['nombre_archivo']) ?>
            <span class="badge bg-light text-dark border"><?= (int)$detalleImportacion['total_filas'] ?> filas</span>
          </h6>
          <div class="d-flex gap-1">
            <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle"><?= (int)$detalleImportacion['expedientes_creados'] ?> creados</span>
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle"><?= (int)$detalleImportacion['filas_error'] ?> con error</span>
          </div>
        </div>
        <div class="table-responsive" style="max-height:380px;">
          <table class="table table-sm align-middle">
            <thead class="bg-white">
              <tr><th>#</th><th>Registro</th><th>Estudiante</th><th>Modalidad</th><th>Cohorte</th><th>Resultado</th><th>Detalle</th></tr>
            </thead>
            <tbody>
              <?php foreach ($detalleFilas as $df): ?>
                <tr>
                  <td class="text-muted small"><?= (int)$df['fila'] ?></td>
                  <td class="small"><?= e($df['registro_universitario']) ?></td>
                  <td class="small"><?= e(trim(($df['nombre'] ?? '') . ' ' . ($df['apellido'] ?? ''))) ?></td>
                  <td class="small"><?= e($df['modalidad'] ?: '—') ?></td>
                  <td class="small"><?= e($df['cohorte'] ?: '—') ?></td>
                  <td><span class="badge <?= $clasesResultado[$df['resultado']] ?? '' ?>"><?= $etiquetasResultado[$df['resultado']] ?? e($df['resultado']); ?></span></td>
                  <td class="small text-muted"><?= e($df['mensaje'] ?: '—') ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <div class="mt-3">
          <small class="text-muted">Realizada por <?= e(($detalleImportacion['nombre'] ?? '') . ' ' . ($detalleImportacion['apellido'] ?? '')) ?> el <?= date('d/m/Y H:i', strtotime($detalleImportacion['fecha_importacion'])) ?></small>
        </div>
      </div>
    <?php else: ?>
      <div class="card card-custom p-4 text-center text-muted">
        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
        <p class="mb-0">Aún no hay una previsualización. Sube un archivo CSV del padrón.</p>
      </div>
    <?php endif; ?>

    <?php if ($importaciones): ?>
      <div class="card card-custom p-3">
        <div class="px-2 py-1 mb-2">
          <h6 class="fw-bold mb-0 d-flex align-items-center gap-2"><i class="bi bi-clock-history text-primary"></i> Últimas importaciones</h6>
        </div>
        <div class="table-responsive">
          <table class="table align-middle">
            <thead><tr><th>Archivo</th><th>Fecha</th><th>Filas</th><th>Expedientes</th><th>Por</th><th class="text-end">Detalle</th></tr></thead>
            <tbody>
              <?php foreach ($importaciones as $imp): ?>
                <tr>
                  <td class="small fw-semibold"><?= e($imp['nombre_archivo']) ?></td>
                  <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($imp['fecha_importacion'])) ?></td>
                  <td class="small"><?= (int)$imp['total_filas'] ?></td>
                  <td class="small">
                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle"><?= (int)$imp['expedientes_creados'] ?></span>
                  </td>
                  <td class="small text-muted"><?= e(trim(($imp['nombre'] ?? '') . ' ' . ($imp['apellido'] ?? ''))) ?></td>
                  <td class="text-end">
                    <a class="btn btn-sm btn-light border" href="/controllers/mg_importar.php?ver=<?= (int)$imp['id_importacion'] ?>" title="Ver detalle"><i class="bi bi-eye"></i></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>