<?php
// =========================================================
// VISTA: DECLARACIÓN DE MODALIDAD (views/estudiante/declaracion.php)
// ---------------------------------------------------------
// El estudiante elige su modalidad de grado del periodo activo.
// Variables del controlador (estudiante_declaracion.php):
//   $periodoActivo, $declaraciones, $modalidadesPublicadas,
//   $declaracionActual, $editable
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
?>

<div class="hero-card mb-4">
  <div class="d-flex flex-wrap justify-content-between align-items-end gap-3">
    <div>
      <h2 class="h4 fw-bold mb-1"><i class="bi bi-mortarboard-fill me-2"></i>Mi Modalidad de Grado</h2>
      <p class="mb-0 text-muted">El paso final para graduarte comienza aquí: elegí la modalidad que te acompaña.</p>
    </div>
    <div class="text-end text-muted small">
      <i class="bi bi-calendar3 me-1"></i>Periodo activo:
      <span class="fw-semibold text-dark"><?= $periodoActivo ? e($periodoActivo['nombre']) : '— sin periodo abierto' ?></span>
    </div>
  </div>
</div>

<div class="row g-4">
  <div class="col-lg-7">
    <?php if (!$periodoActivo): ?>
      <div class="card card-custom p-4 text-center py-5">
        <div class="display-6 text-muted mb-3"><i class="bi bi-calendar-x"></i></div>
        <h5 class="fw-bold text-muted">Aún no hay periodo abierto</h5>
        <p class="text-muted small mb-0">Cuando la universidad habilite el periodo de declaraciones podrás elegir tu modalidad aquí.</p>
      </div>
    <?php elseif (empty($modalidadesPublicadas)): ?>
      <div class="card card-custom p-4 text-center py-5">
        <div class="display-6 text-muted mb-3"><i class="bi bi-journal-x"></i></div>
        <h5 class="fw-bold text-muted">Catálogo sin modalidades</h5>
        <p class="text-muted small mb-0">El catálogo de modalidades aún no está publicado. Consultá más tarde.</p>
      </div>
    <?php elseif (!$declaracionActual || $editable): ?>
      <!-- Formulario de declaración: nueva / edición de borrador-rechazada-cancelada -->
      <div class="card card-custom p-4">
        <div class="d-flex align-items-center gap-2 mb-1">
          <span class="icon-stats rounded-3 bg-primary bg-opacity-10 text-primary"><i class="bi bi-journal-plus"></i></span>
          <h5 class="fw-bold mb-0">
            <?= $declaracionActual ? 'Tu declaración' : 'Nueva declaración' ?>
          </h5>
        </div>
        <?php if ($declaracionActual && $declaracionActual['estado'] === 'rechazada'): ?>
          <div class="alert alert-danger d-flex gap-2 align-items-start mt-3 mb-3 rounded-3 border-0 small">
            <i class="bi bi-x-octagon-fill fs-5 mt-0"></i>
            <div>
              <div class="fw-semibold">Tu declaración fue rechazada por el equipo MG.</div>
              <?php if (!empty($declaracionActual['observacion'])): ?>
                <div class="mt-1">Motivo: <?= e($declaracionActual['observacion']) ?></div>
              <?php endif; ?>
              Corrigé los datos y volvé a enviarla.
            </div>
          </div>
        <?php elseif ($declaracionActual && $declaracionActual['estado'] === 'cancelada'): ?>
          <div class="alert alert-secondary d-flex gap-2 align-items-center mt-3 mb-3 rounded-3 border-0 small">
            <i class="bi bi-arrow-counterclockwise"></i> Tu declaración anterior fue cancelada. Podés volver a declarar en este periodo.
          </div>
        <?php endif; ?>

        <form method="POST" action="/controllers/declaraciones_guardar.php" class="mt-3">
          <?= campoCsrf() ?>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Modalidad de grado *</label>
            <select name="id_modalidad" class="form-select" required>
              <option value="" disabled <?= !$declaracionActual ? 'selected' : '' ?>>Seleccioná tu modalidad...</option>
              <?php foreach ($modalidadesPublicadas as $mdl): ?>
                <option value="<?= (int)$mdl['id_modalidad'] ?>" <?= $declaracionActual && (int)$declaracionActual['id_modalidad'] === (int)$mdl['id_modalidad'] ? 'selected' : '' ?>>
                  <?= e($mdl['nombre']) ?> (<?= e($mdl['codigo']) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text"><i class="bi bi-info-circle me-1"></i>Solo ves las modalidades publicadas por el equipo de MG.</div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold">Título o tema del trabajo final</label>
            <input type="text" name="titulo_proyecto" class="form-control"
                   placeholder="Ej: Sistema de gestión de inventarios con IoT"
                   value="<?= e($declaracionActual['titulo_proyecto'] ?? '') ?>">
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Empresa / organización (si aplica)</label>
              <input type="text" name="empresa_org" class="form-control"
                     placeholder="Nombre de la institución"
                     value="<?= e($declaracionActual['empresa_org'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Tutor facultativo</label>
              <input type="text" name="tutor_facultativo" class="form-control"
                     placeholder="Docente que acompañará el proceso"
                     value="<?= e($declaracionActual['tutor_facultativo'] ?? '') ?>">
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2 border-top pt-3">
            <button type="submit" name="accion" value="guardar" class="btn btn-outline-primary">
              <i class="bi bi-save me-1"></i>Guardar borrador
            </button>
            <button type="submit" name="accion" value="enviar" class="btn btn-primary">
              <i class="bi bi-send-fill me-1"></i>Enviar a revisión
            </button>
            <?php if ($declaracionActual): ?>
              <button type="submit" name="accion" value="cancelar" class="btn btn-outline-danger ms-auto"
                      formnovalidate
                      data-confirm="¿Cancelar tu declaración de modalidad?"
                      data-confirm-title="Cancelar declaración"
                      data-confirm-text="Podrás volver a declarar cuando quieras."
                      data-confirm-color="#dc2626">
                <i class="bi bi-x-circle me-1"></i>Cancelar
              </button>
            <?php endif; ?>
          </div>
        </form>
      </div>
    <?php else: ?>
      <!-- Declaración en trámite o aprobada: solo lectura -->
      <?php $d = $declaracionActual; [$badgeClase, $badgeTexto] = $estadosEtiqueta[$d['estado']] ?? ['bg-secondary text-white', $d['estado']]; ?>
      <div class="card card-custom p-4">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
          <div class="d-flex align-items-center gap-2">
            <span class="icon-stats rounded-3 <?= $d['estado'] === 'aprobada' ? 'bg-success bg-opacity-10 text-success' : 'bg-primary bg-opacity-10 text-primary' ?>">
              <i class="bi <?= $d['estado'] === 'aprobada' ? 'bi-patch-check-fill' : 'bi-file-earmark-text-fill' ?>"></i>
            </span>
            <h5 class="fw-bold mb-0">Estado de mi declaración</h5>
          </div>
          <span class="badge rounded-pill px-3 py-2 <?= $badgeClase ?>"><?= $badgeTexto ?></span>
        </div>

        <!-- Tutor MG asignado al expediente -->
            <?php if (!empty($miTutorMg)): ?>
            <div class="card border-0 bg-light p-3 mt-3">
              <h6 class="fw-bold small mb-2 d-flex align-items-center gap-2"><i class="bi bi-person-check-fill text-primary"></i>Tu tutor asignado (Modalidad de Grado)</h6>
              <div class="d-flex align-items-center gap-3">
                <?= avatarHTML($miTutorMg['foto_perfil'] ?? '', iniciales($miTutorMg['tutor_nombre'] ?? '', $miTutorMg['tutor_apellido'] ?? ''), 'avatar-lg', 'width:48px;height:48px;font-size:.9rem;') ?>
                <div>
                  <div class="fw-semibold"><?= e($miTutorMg['tutor_nombre'] . ' ' . $miTutorMg['tutor_apellido']) ?></div>
                  <small class="text-muted d-block"><?= e($miTutorMg['tutor_correo'] ?? '') ?></small>
                  <?php if (!empty($miTutorMg['referencia_decanatura'])): ?>
                    <small class="text-muted d-block font-monospace">Ref. <?= e($miTutorMg['referencia_decanatura']) ?></small>
                  <?php endif; ?>
                </div>
              </div>
            </div>
            <?php endif; ?>

        <?php if ($d['estado'] === 'aprobada'): ?>
          <div class="alert alert-success d-flex gap-2 align-items-start mt-3 mb-3 rounded-3 border-0">
            <i class="bi bi-check-circle-fill fs-5 mt-0"></i>
            <div>
              <div class="fw-semibold">¡Felicidades! Tu modalidad fue aprobada.</div>
              <div class="small">Ya puedes iniciar la ejecución de tu modalidad y completar tus avales.</div>
            </div>
          </div>

          <div class="row g-3 mt-0">
            <!-- Resultado del acta (si fue firmada) -->
            <?php if ($miActa && ($miActa['estado'] ?? '') === 'firmada' && $miActa['nota_final'] !== null): ?>
              <?php
                $recto = $miActa['resultado'] === 'aprobado';
                $rectoClase = $recto ? 'bg-success bg-opacity-10 text-success border border-success-subtle' : 'bg-danger bg-opacity-10 text-danger border border-danger-subtle';
                $rectoIcono = $recto ? 'bi-star-fill' : 'bi-x-octagon-fill';
              ?>
              <div class="col-12">
                <div class="card border-0 bg-light p-3">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="fw-bold small mb-0 d-flex align-items-center gap-2"><i class="bi bi-clipboard-pulse text-primary"></i>Resultado de tu defensa</h6>
                    <span class="badge px-3 py-2 <?= $rectoClase ?>"><i class="bi <?= $rectoIcono ?> me-1"></i><?= $recto ? 'APROBADO' : 'REPROBADO' ?></span>
                  </div>
                  <div class="d-flex align-items-end gap-2 mt-2">
                    <span class="display-6 fw-bold"><?= number_format((float)$miActa['nota_final'], 2) ?></span>
                    <span class="text-muted small mb-2">sobre 100</span>
                  </div>
                  <?php if (!empty($miActa['observaciones'])): ?>
                    <p class="small text-muted mb-0 mt-1"><i class="bi bi-chat-left-text me-1"></i><?= e($miActa['observaciones']) ?></p>
                  <?php endif; ?>
                </div>
              </div>
            <?php endif; ?>

            <!-- Jurado asignado -->
            <div class="col-md-6">
              <div class="card border-0 bg-light p-3 h-100">
                <h6 class="fw-bold small mb-2 d-flex align-items-center gap-2"><i class="bi bi-people-fill text-primary"></i>Tu tribunal</h6>
                <?php if (empty($miJurado)): ?>
                  <p class="text-muted small mb-0">El equipo MG aún no asignó tu jurado evaluador.</p>
                <?php else: ?>
                  <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                    <?php foreach ($miJurado as $j): ?>
                      <li class="d-flex align-items-center gap-2">
                        <?= avatarHTML($j['foto_perfil'] ?? '', iniciales($j['nombre'] ?? '', $j['apellido'] ?? ''), 'avatar-md', 'width:32px;height:32px;font-size:.65rem;') ?>
                        <div>
                          <div class="small fw-semibold"><?= e($j['nombre'] . ' ' . $j['apellido']) ?></div>
                          <small class="text-muted text-uppercase" style="font-size:.6rem;"><?= e($j['rol_jurado']) ?></small>
                        </div>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </div>
            <!-- Checklist de avales -->
            <div class="col-md-6">
              <div class="card border-0 bg-light p-3 h-100">
                <h6 class="fw-bold small mb-2 d-flex align-items-center gap-2"><i class="bi bi-clipboard2-check-fill text-primary"></i>Mis avales</h6>
                <?php if (empty($misAvales)): ?>
                  <p class="text-muted small mb-0">El checklist de documentos aún no fue publicado.</p>
                <?php else: ?>
                  <ul class="list-unstyled d-flex flex-column gap-2 mb-0">
                    <?php foreach ($misAvales as $a): ?>
                      <li class="d-flex justify-content-between align-items-center gap-2 small">
                        <span class="d-block">
                          <?= e($a['nombre']) ?>
                          <?php if (!empty($a['archivo_nombre'])): ?>
                            <a href="<?= e($a['archivo']) ?>" target="_blank" class="d-block small text-primary text-decoration-underline">
                              <i class="bi bi-paperclip me-1"></i><?= e($a['archivo_nombre']) ?>
                            </a>
                          <?php endif; ?>
                        </span>
                        <?php
                          $avalClase = $a['estado'] === 'entregado' ? 'bg-success bg-opacity-10 text-success border border-success-subtle'
                                   : ($a['estado'] === 'observado' ? 'bg-warning bg-opacity-10 text-warning border' : 'bg-secondary bg-opacity-10 text-secondary border');
                          $avalIcono = $a['estado'] === 'entregado' ? 'bi-check2-circle' : ($a['estado'] === 'observado' ? 'bi-exclamation-circle' : 'bi-hourglass-split');
                        ?>
                        <span class="badge <?= $avalClase ?>" style="flex-shrink:0;"><i class="bi <?= $avalIcono ?> me-1"></i><?= ucfirst($a['estado']) ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>

        <dl class="row mt-3 mb-2">
          <dt class="col-sm-4 text-muted small fw-semibold">Modalidad</dt>
          <dd class="col-sm-8"><?= e($d['modalidad_nombre']) ?> <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle ms-1"><?= e($d['modalidad_codigo']) ?></span></dd>
          <dt class="col-sm-4 text-muted small fw-semibold">Título</dt>
          <dd class="col-sm-8"><?= e($d['titulo_proyecto']) ?: '<span class="text-muted">Pendiente</span>' ?></dd>
          <dt class="col-sm-4 text-muted small fw-semibold">Empresa / Organización</dt>
          <dd class="col-sm-8"><?= e($d['empresa_org']) ?: '<span class="text-muted">—</span>' ?></dd>
          <dt class="col-sm-4 text-muted small fw-semibold">Tutor facultativo</dt>
          <dd class="col-sm-8"><?= e($d['tutor_facultativo']) ?: '<span class="text-muted">—</span>' ?></dd>
          <dt class="col-sm-4 text-muted small fw-semibold">Enviada el</dt>
          <dd class="col-sm-8"><?= $d['fecha_enviada'] ? date('d/m/Y H:i', strtotime($d['fecha_enviada'])) : '—' ?></dd>
        </dl>

        <?php if ($d['estado'] === 'enviada'): ?>
          <div class="border-top pt-3 d-flex">
            <form method="POST" action="/controllers/declaraciones_guardar.php"
                  data-confirm="¿Cancelar tu declaración en trámite?"
                  data-confirm-title="Cancelar declaración"
                  data-confirm-text="Podrás volver a declarar después."
                  data-confirm-color="#dc2626">
              <?= campoCsrf() ?>
              <input type="hidden" name="accion" value="cancelar">
              <button type="submit" class="btn btn-outline-danger btn-sm"><i class="bi bi-x-circle me-1"></i>Cancelar declaración</button>
            </form>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Columna lateral: infografía de estados -->
  <div class="col-lg-5">
    <div class="card card-custom p-4 mb-4">
      <h6 class="fw-bold mb-3 d-flex align-items-center gap-2"><i class="bi bi-diagram-3 text-primary"></i> ¿Cómo sigue el proceso?</h6>
      <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
        <li class="d-flex gap-3">
          <span class="icon-stats rounded-3 bg-warning bg-opacity-10 text-warning" style="width:38px;height:38px;font-size:1rem;"><i class="bi bi-pencil-square"></i></span>
          <div><span class="fw-semibold small d-block">1. Completá tu declaración</span><span class="text-muted small">Elegí la modalidad, el tema y guardá tu borrador.</span></div>
        </li>
        <li class="d-flex gap-3">
          <span class="icon-stats rounded-3 bg-primary bg-opacity-10 text-primary" style="width:38px;height:38px;font-size:1rem;"><i class="bi bi-send-fill"></i></span>
          <div><span class="fw-semibold small d-block">2. Enviá a revisión</span><span class="text-muted small">El equipo MG recibe tu solicitud y la analiza.</span></div>
        </li>
        <li class="d-flex gap-3">
          <span class="icon-stats rounded-3 bg-indigo bg-opacity-10 text-indigo" style="width:38px;height:38px;font-size:1rem;"><i class="bi bi-search"></i></span>
          <div><span class="fw-semibold small d-block">3. Revisión del equipo MG</span><span class="text-muted small">Podés hacer seguimiento desde esta misma pantalla.</span></div>
        </li>
        <li class="d-flex gap-3">
          <span class="icon-stats rounded-3 bg-success bg-opacity-10 text-success" style="width:38px;height:38px;font-size:1rem;"><i class="bi bi-patch-check-fill"></i></span>
          <div><span class="fw-semibold small d-block">4. Aprobación</span><span class="text-muted small">Ya puedes iniciar la ejecución de tu modalidad y la asignación de jurado.</span></div>
        </li>
      </ul>
    </div>

    <!-- Historial de periodos anteriores -->
    <div class="card card-custom p-3">
      <h6 class="fw-bold mb-2 d-flex align-items-center gap-2"><i class="bi bi-clock-history text-primary"></i> Periodos anteriores</h6>
      <?php $historicas = array_filter($declaraciones, fn($d) => !$periodoActivo || (int)$d['id_periodo'] !== (int)$periodoActivo['id_periodo']); ?>
      <?php if (empty($historicas)): ?>
        <p class="text-muted small mb-0 text-center py-3"><i class="bi bi-inbox me-1"></i>Sin declaraciones previas.</p>
      <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($historicas as $h): ?>
            <?php [$badgeClase, $badgeTexto] = $estadosEtiqueta[$h['estado']] ?? ['bg-secondary text-white', $h['estado']]; ?>
            <li class="list-group-item d-flex justify-content-between align-items-center px-0 bg-transparent">
              <div>
                <div class="fw-semibold small"><?= e($h['periodo_nombre']) ?></div>
                <div class="text-muted small"><?= e($h['modalidad_nombre']) ?></div>
              </div>
              <span class="badge rounded-pill px-2 py-1 <?= $badgeClase ?>"><?= $badgeTexto ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>