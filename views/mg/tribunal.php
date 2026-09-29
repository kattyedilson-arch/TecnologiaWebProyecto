<?php
// =========================================================
// VISTA: GESTIÓN DE TRIBUNAL (views/mg/tribunal.php)
// ---------------------------------------------------------
// Detalle de una declaración aprobada: formulario de jurado
// (hasta 3 docentes con rol) y checklist de avales (agregar,
// cambiar estado, eliminar). Variables del controlador:
//   $declaracion, $jurado, $avales, $conteoAvales, $docentes,
//   $puedeGuardar (mg_tribunal.php)
// =========================================================
require_once __DIR__ . '/../layouts/header.php';

$estadosAvalClase = [
    'pendiente' => ['bg-secondary bg-opacity-10 text-secondary border', 'bi-hourglass-split', 'Pendiente'],
    'entregado' => ['bg-success bg-opacity-10 text-success border border-success-subtle', 'bi-check2-circle', 'Entregado'],
    'observado' => ['bg-warning bg-opacity-10 text-warning border', 'bi-exclamation-circle', 'Observado'],
];
$estadosAvalValores = ['pendiente', 'entregado', 'observado'];
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <a href="/controllers/mg_jurados.php" class="btn btn-sm btn-light border mb-2"><i class="bi bi-arrow-left me-1"></i>Tribunales y Avales</a>
    <a href="/controllers/mg_acta.php?id=<?= (int)$declaracion['id_declaracion'] ?>" class="btn btn-sm btn-outline-primary mb-2 ms-1">
      <i class="bi bi-clipboard-check me-1"></i>Acta de calificación
    </a>
    <h2 class="h5 fw-bold mb-1">
      <?= e($declaracion['nombre'] . ' ' . $declaracion['apellido']) ?>
      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle ms-1"><?= e($declaracion['modalidad_codigo']) ?></span>
    </h2>
    <p class="text-muted small mb-0">
      <?= e($declaracion['modalidad_nombre']) ?> • <?= e($declaracion['carrera'] ?: 'Sin carrera') ?>
      <?= $declaracion['titulo_proyecto'] ? '• <i class="bi bi-eject me-1"></i>' . e($declaracion['titulo_proyecto']) : '' ?>
    </p>
  </div>
  <div class="text-end">
    <?php if ((int)$conteoAvales['total'] > 0): ?>
      <div class="d-flex align-items-center gap-2 justify-content-end">
        <div class="progress" style="width:110px;height:7px;">
          <div class="progress-bar bg-success" style="width:<?= (int)round($conteoAvales['entregado'] / $conteoAvales['total'] * 100) ?>%"></div>
        </div>
        <small class="text-muted"><?= (int)$conteoAvales['entregado'] ?>/<?= (int)$conteoAvales['total'] ?> avales entregados</small>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="row g-4">
  <!-- ============ JURADO ============ -->
  <div class="col-lg-5">
    <div class="card card-custom p-4">
      <h6 class="fw-bold mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-people-fill text-primary"></i> Jurado evaluador
      </h6>
      <p class="text-muted small mb-3">Hasta 3 docentes: presidente, titular(es) y un suplente.</p>

      <form method="POST" action="/controllers/jurados_guardar.php">
        <?= campoCsrf() ?>
        <input type="hidden" name="id_declaracion" value="<?= (int)$declaracion['id_declaracion'] ?>">

        <?php for ($i = 1; $i <= 3; $i++): ?>
          <?php
            $actual = $jurado[$i - 1] ?? null;
            $rolActual = $actual ? $actual['rol_jurado'] : '';
            $usuarioActual = $actual ? (int)$actual['id_usuario'] : 0;
          ?>
          <div class="row g-2 mb-2 align-items-end">
            <div class="col-7">
              <label class="form-label small fw-semibold"><?= $i === 1 ? 'Presidente' : 'Miembro N.º ' . $i ?></label>
              <select name="jurado_usuario_<?= $i ?>" class="form-select form-select-sm">
                <option value="0" <?= $usuarioActual === 0 ? 'selected' : '' ?>>— Sin asignar —</option>
                <?php foreach ($docentes as $doc): ?>
                  <?php $esto = (int)$doc['id_usuario'] === $usuarioActual; ?>
                  <option value="<?= (int)$doc['id_usuario'] ?>" <?= $esto ? 'selected' : '' ?>>
                    <?= e($doc['nombre'] . ' ' . $doc['apellido']) ?><?= !empty($doc['especialidad']) ? ' (' . e($doc['especialidad']) . ')' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-5">
              <?php if ($i === 1): ?>
                <input type="hidden" name="jurado_rol_1" value="presidente">
                <div class="form-control-plaintext small fw-semibold text-primary py-2 px-0"><i class="bi bi-mortarboard me-1"></i>Presidente</div>
              <?php else: ?>
                <label class="form-label small fw-semibold">Rol</label>
                <select name="jurado_rol_<?= $i ?>" class="form-select form-select-sm">
                  <option value="titular" <?= $rolActual === 'titular' ? 'selected' : '' ?>>Titular</option>
                  <option value="suplente" <?= $rolActual === 'suplente' ? 'selected' : '' ?>>Suplente</option>
                </select>
              <?php endif; ?>
            </div>
          </div>
        <?php endfor; ?>

        <div class="border-top pt-3 mt-1">
          <button type="submit" class="btn btn-primary btn-sm w-100" <?= $puedeGuardar ? '' : 'disabled' ?>>
            <i class="bi bi-check-lg me-1"></i>Guardar tribunal
          </button>
          <?php if (!$puedeGuardar): ?>
            <p class="text-muted small mb-0 mt-2 text-center">Solo auxiliares y coordinador MG asignan tribunales.</p>
          <?php endif; ?>
        </div>
      </form>

      <?php if (!empty($jurado)): ?>
        <ul class="list-group list-group-flush mt-3">
          <?php foreach ($jurado as $m): ?>
            <li class="list-group-item d-flex align-items-center gap-2 px-0 bg-transparent">
              <?= avatarHTML($m['foto_perfil'] ?? '', iniciales($m['nombre'] ?? '', $m['apellido'] ?? ''), 'avatar-md', 'width:36px;height:36px;font-size:.7rem;') ?>
              <div class="flex-grow-1">
                <div class="fw-semibold small"><?= e($m['nombre'] . ' ' . $m['apellido']) ?></div>
                <small class="text-muted text-uppercase" style="font-size:.62rem;">
                  <?= e($m['rol_jurado']) ?><?= !empty($m['especialidad']) ? ' • ' . e($m['especialidad']) : '' ?>
                </small>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- ============ AVALES ============ -->
  <div class="col-lg-7">
    <div class="card card-custom p-4">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-clipboard2-check-fill text-primary"></i> Checklist de avales
        </h6>
        <span class="badge text-bg-light border"><?= (int)$conteoAvales['total'] ?> ítems</span>
      </div>
      <p class="text-muted small mb-3">Registrá la entrega de cada documento requerido al estudiante.</p>

      <ul class="list-group list-group-flush mb-3">
        <?php if (empty($avales)): ?>
          <li class="list-group-item text-center text-muted small py-4 px-0 bg-transparent">
            <i class="bi bi-inbox me-1"></i>Sin avales. Agregá el primero abajo o aprobá la declaración para crear el estándar.
          </li>
        <?php else: ?>
          <?php foreach ($avales as $aval): ?>
            <?php [$badgeClase, $icono, $texto] = $estadosAvalClase[$aval['estado']] ?? $estadosAvalClase['pendiente']; ?>
            <li class="list-group-item px-0 bg-transparent py-2">
              <div class="d-flex align-items-start gap-2">
                <span class="badge px-2 py-1 <?= $badgeClase ?>" style="font-size:.65rem;"><i class="bi <?= $icono ?> me-1"></i><?= $texto ?></span>
                <div class="flex-grow-1">
                  <div class="fw-semibold small"><?= e($aval['nombre']) ?></div>
                  <?php if (!empty($aval['descripcion'])): ?>
                    <small class="text-muted d-block"><?= e($aval['descripcion']) ?></small>
                  <?php endif; ?>
                  <?php if (!empty($aval['observacion'])): ?>
                    <small class="text-muted d-block fst-italic"><i class="bi bi-chat-left-text me-1"></i><?= e($aval['observacion']) ?></small>
                  <?php endif; ?>
                  <?php if ($aval['fecha_entrega']): ?>
                    <small class="text-muted d-block"><i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($aval['fecha_entrega'])) ?></small>
                  <?php endif; ?>
                  <div class="d-flex align-items-center gap-2 mt-2 flex-wrap">
                    <?php if (!empty($aval['archivo_nombre'])): ?>
                      <a href="<?= e($aval['archivo']) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0">
                        <i class="bi bi-file-earmark-arrow-down me-1"></i><?= e($aval['archivo_nombre']) ?>
                      </a>
                      <?php if ($puedeGuardar): ?>
                        <form method="POST" action="/controllers/avales_guardar.php" class="d-inline"
                              data-confirm="¿Retirar el documento adjunto de este aval?"
                              data-confirm-title="Quitar archivo">
                          <?= campoCsrf() ?>
                          <input type="hidden" name="accion" value="quitar_archivo">
                          <input type="hidden" name="id_declaracion" value="<?= (int)$declaracion['id_declaracion'] ?>">
                          <input type="hidden" name="id_aval" value="<?= (int)$aval['id_aval'] ?>">
                          <button type="submit" class="btn btn-sm btn-light border text-danger py-0" title="Quitar archivo"><i class="bi bi-file-earmark-x"></i></button>
                        </form>
                      <?php endif; ?>
                    <?php elseif ($puedeGuardar): ?>
                      <form method="POST" action="/controllers/avales_guardar.php" enctype="multipart/form-data" class="d-inline-flex gap-1 align-items-center">
                        <?= campoCsrf() ?>
                        <input type="hidden" name="accion" value="adjuntar">
                        <input type="hidden" name="id_declaracion" value="<?= (int)$declaracion['id_declaracion'] ?>">
                        <input type="hidden" name="id_aval" value="<?= (int)$aval['id_aval'] ?>">
                        <input type="file" name="archivo_aval" class="form-control form-control-sm" style="max-width:220px;" accept=".pdf,.jpg,.jpeg,.png,.webp" required>
                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Adjuntar documento"><i class="bi bi-paperclip"></i></button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
                <?php if ($puedeGuardar): ?>
                  <div class="d-flex gap-1 flex-shrink-0">
                    <form method="POST" action="/controllers/avales_guardar.php" class="d-inline">
                      <?= campoCsrf() ?>
                      <input type="hidden" name="accion" value="estado">
                      <input type="hidden" name="id_declaracion" value="<?= (int)$declaracion['id_declaracion'] ?>">
                      <input type="hidden" name="id_aval" value="<?= (int)$aval['id_aval'] ?>">
                      <select name="estado" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">
                        <?php foreach ($estadosAvalValores as $v): ?>
                          <option value="<?= $v ?>" <?= $aval['estado'] === $v ? 'selected' : '' ?>><?= ucfirst($v) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </form>
                    <form method="POST" action="/controllers/avales_guardar.php" class="d-inline"
                          data-confirm="¿Retirar el aval &quot;<?= e($aval['nombre']) ?>&quot; del checklist?"
                          data-confirm-title="Eliminar aval">
                      <?= campoCsrf() ?>
                      <input type="hidden" name="accion" value="eliminar">
                      <input type="hidden" name="id_declaracion" value="<?= (int)$declaracion['id_declaracion'] ?>">
                      <input type="hidden" name="id_aval" value="<?= (int)$aval['id_aval'] ?>">
                      <button type="submit" class="btn btn-sm btn-light border text-danger"><i class="bi bi-trash"></i></button>
                    </form>
                  </div>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>

      <?php if ($puedeGuardar): ?>
        <form method="POST" action="/controllers/avales_guardar.php" class="border-top pt-3">
          <?= campoCsrf() ?>
          <input type="hidden" name="accion" value="agregar">
          <input type="hidden" name="id_declaracion" value="<?= (int)$declaracion['id_declaracion'] ?>">
          <div class="row g-2">
            <div class="col-md-5">
              <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre del aval" required>
            </div>
            <div class="col-md-5">
              <input type="text" name="descripcion" class="form-control form-control-sm" placeholder="Descripción (opcional)">
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="bi bi-plus-lg me-1"></i>Agregar</button>
            </div>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>