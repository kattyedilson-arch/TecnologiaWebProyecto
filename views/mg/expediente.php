<?php
// =========================================================
// VISTA: FICHA DE EXPEDIENTE MG (views/mg/expediente.php)
// ---------------------------------------------------------
// HU-024..027. Detalle del expediente con pestañas:
// Datos / Etapas / Tutor / Documentos. Incluye los formularios
// de transición de etapa (MG1/MG2/finalizado/abandono/reprobado),
// asignación y cambio de tutor y generación de la carta.
// Variables: $expediente, $historialEtapas, $historialTutor,
// $asignacionVigente, $documentos, *$puede* (mg_expediente.php)
// =========================================================
require_once __DIR__ . '/../layouts/header.php';

$id = (int)$expediente['id_declaracion'];
$tabs = ['datos', 'etapas', 'tutor', 'documentos'];
$tabActivo = in_array($_GET['tab'] ?? 'datos', $tabs, true) ? $_GET['tab'] : 'datos';

$etiquetasEtapa = ['previa' => 'Previa', 'mg1' => 'MG1', 'mg2' => 'MG2', 'finalizado' => 'Finalizado'];
$etiquetasEstado = [
    'borrador' => 'Borrador', 'enviada' => 'Enviada', 'en_revision' => 'En revisión',
    'aprobada' => 'Aprobada', 'rechazada' => 'Rechazada', 'cancelada' => 'Cancelada',
    'reprobado' => 'Reprobado', 'abandono' => 'Abandono',
];
$clasesEstado = [
    'borrador' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle',
    'enviada' => 'bg-primary bg-opacity-10 text-primary border border-primary-subtle',
    'en_revision' => 'bg-warning bg-opacity-10 text-warning border border-warning-subtle',
    'aprobada' => 'bg-success bg-opacity-10 text-success border border-success-subtle',
    'rechazada' => 'bg-danger bg-opacity-10 text-danger border border-danger-subtle',
    'cancelada' => 'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle',
    'reprobado' => 'bg-danger bg-opacity-10 text-danger border border-danger-subtle',
    'abandono' => 'bg-dark bg-opacity-10 text-dark border border-dark-subtle',
];
$etapaActual = $expediente['etapa_actual'] ?? 'previa';
$estado = $expediente['estado'] ?? '';
$esAbierto = in_array($estado, ['borrador', 'enviada', 'en_revision', 'aprobada'], true);
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
  <div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <h2 class="h5 fw-bold mb-0">Expediente #<?= $id ?></h2>
      <span class="badge <?= $clasesEstado[$estado] ?? '' ?>"><?= $etiquetasEstado[$estado] ?? e($estado) ?></span>
      <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle">
        <i class="bi bi-layers me-1"></i><?= $etiquetasEtapa[$etapaActual] ?? e($etapaActual) ?>
      </span>
    </div>
    <p class="text-muted small mb-0 mt-1">
      <?= e($expediente['modalidad_codigo']) ?> — <?= e($expediente['modalidad_nombre']) ?>
      <?= $expediente['cohorte_codigo'] ? ' · ' . e($expediente['cohorte_codigo']) : '' ?>
    </p>
  </div>
  <a class="btn btn-light border" href="/controllers/mg_expedientes.php"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>

<!-- Datos del estudiante -->
<div class="card card-custom p-3 mb-4">
  <div class="row g-3 align-items-center">
    <div class="col-auto">
      <?= avatarHTML('', e($expediente['estudiante_nombre'] . ' ' . $expediente['estudiante_apellido']), 'avatar-md') ?>
    </div>
    <div class="col-md-6">
      <div class="fw-bold"><?= e(trim($expediente['estudiante_nombre'] . ' ' . $expediente['estudiante_apellido'])) ?></div>
      <div class="text-muted small">
        <?= e($expediente['registro_universitario']) ?> · <?= e($expediente['nombre_carrera'] ?: '—') ?>
        <?php if ($expediente['estudiante_correo']): ?> · <?= e($expediente['estudiante_correo']) ?><?php endif; ?>
      </div>
    </div>
    <div class="col-md-3">
      <div class="text-muted small">Periodo</div>
      <div class="fw-semibold"><?= e($expediente['periodo_nombre'] ?: '—') ?></div>
    </div>
    <div class="col-md-3">
      <div class="text-muted small">Cohorte</div>
      <div class="fw-semibold"><?= e($expediente['cohorte_codigo'] ?: '—') ?></div>
    </div>
  </div>
</div>

<!-- Pestañas -->
<ul class="nav nav-tabs mb-3">
  <?php foreach ([
    'datos'     => ['bi-person-vcard', 'Datos'],
    'etapas'    => ['bi-layers', 'Etapas'],
    'tutor'     => ['bi-person-check', 'Tutor'],
    'documentos'=> ['bi-file-earmark-text', 'Documentos'],
  ] as $k => [$icono, $texto]): ?>
    <li class="nav-item">
      <a class="nav-link <?= $tabActivo === $k ? 'active' : '' ?>" href="?id=<?= $id ?>&tab=<?= $k ?>">
        <i class="bi <?= $icono ?> me-1"></i><?= $texto ?>
      </a>
    </li>
  <?php endforeach; ?>
</ul>

<?php if ($tabActivo === 'datos'): ?>
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card card-custom p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-person-vcard text-primary me-1"></i> Datos de la modalidad</h6>
        <dl class="row mb-0 small">
          <dt class="col-sm-4 text-muted fw-normal">Modalidad</dt>
          <dd class="col-sm-8 fw-semibold"><?= e($expediente['modalidad_nombre']) ?></dd>
          <dt class="col-sm-4 text-muted fw-normal">Tema / Título</dt>
          <dd class="col-sm-8"><?= e($expediente['titulo_proyecto'] ?: '—') ?></dd>
          <dt class="col-sm-4 text-muted fw-normal">Empresa</dt>
          <dd class="col-sm-8"><?= e($expediente['empresa_org'] ?: '—') ?></dd>
          <dt class="col-sm-4 text-muted fw-normal">Tutor facultativo</dt>
          <dd class="col-sm-8"><?= e($expediente['tutor_facultativo'] ?: '—') ?></dd>
          <dt class="col-sm-4 text-muted fw-normal">Requiere tutor (RN-MG-01)</dt>
          <dd class="col-sm-8">
            <?= $expediente['requiere_tutor'] ? '<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">Sí</span>' : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle">No</span>' ?>
          </dd>
        </dl>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card card-custom p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-calendar3 text-primary me-1"></i> Fechas clave</h6>
        <dl class="row mb-0 small">
          <dt class="col-sm-5 text-muted fw-normal">Creación</dt>
          <dd class="col-sm-7"><?= date('d/m/Y H:i', strtotime($expediente['fecha_creacion'])) ?></dd>
          <dt class="col-sm-5 text-muted fw-normal">Enviada</dt>
          <dd class="col-sm-7"><?= $expediente['fecha_enviada'] ? date('d/m/Y H:i', strtotime($expediente['fecha_enviada'])) : '—' ?></dd>
          <dt class="col-sm-5 text-muted fw-normal">Revisión</dt>
          <dd class="col-sm-7"><?= $expediente['fecha_revision'] ? date('d/m/Y H:i', strtotime($expediente['fecha_revision'])) : '—' ?></dd>
          <dt class="col-sm-5 text-muted fw-normal">Observación</dt>
          <dd class="col-sm-7"><?= e($expediente['observacion'] ?: '—') ?></dd>
        </dl>
      </div>
    </div>
  </div>

<?php elseif ($tabActivo === 'etapas'): ?>
  <div class="row g-4">
    <div class="col-lg-5">
      <?php if ($esAbierto && $puedeEtapas): ?>
        <div class="card card-custom p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-arrow-right-circle text-primary me-1"></i> Registrar transición</h6>
          <form method="POST" action="/controllers/mg_expediente_guardar.php">
            <input type="hidden" name="id_declaracion" value="<?= $id ?>">
            <input type="hidden" name="accion" value="etapa">
            <?= campoCsrf() ?>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Nueva etapa</label>
              <select name="etapa_destino" class="form-select">
                <?php
                  $orden = ['previa' => 0, 'mg1' => 1, 'mg2' => 2, 'finalizado' => 3];
                  foreach ($etiquetasEtapa as $k => $v):
                    if ($k === $etapaActual) continue;
                    if (($orden[$k] ?? 99) <= ($orden[$etapaActual] ?? 0) && !in_array($k, ['finalizado'], true)) continue;
                ?>
                  <option value="<?= $k ?>"><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Observación (opcional)</label>
              <textarea name="observacion" class="form-control" rows="2" placeholder="Motivo del avance..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Registrar avance de etapa</button>
          </form>

          <hr>

          <h6 class="fw-bold mb-3 text-danger"><i class="bi bi-x-circle me-1"></i> Estados terminales</h6>
          <p class="text-muted small mb-3">El abandono o reprobado exige nota/motivo y lo registra Coordinación (RN-MG-22).</p>
          <form method="POST" action="/controllers/mg_expediente_guardar.php" class="mb-2">
            <input type="hidden" name="id_declaracion" value="<?= $id ?>">
            <input type="hidden" name="accion" value="cerrar">
            <input type="hidden" name="tipo_cierre" value="abandono">
            <?= campoCsrf() ?>
            <div class="input-group mb-2">
              <span class="input-group-text text-muted"><i class="bi bi-sign-stop"></i></span>
              <input type="text" name="motivo" class="form-control" placeholder="Motivo del abandono" required>
            </div>
            <button type="submit" class="btn btn-outline-danger w-100" data-confirm="¿Registrar ABANDONO del expediente?">Registrar abandono</button>
          </form>
          <form method="POST" action="/controllers/mg_expediente_guardar.php">
            <input type="hidden" name="id_declaracion" value="<?= $id ?>">
            <input type="hidden" name="accion" value="cerrar">
            <input type="hidden" name="tipo_cierre" value="reprobado">
            <?= campoCsrf() ?>
            <div class="input-group mb-2">
              <span class="input-group-text text-muted"><i class="bi bi-x-octagon"></i></span>
              <input type="text" name="motivo" class="form-control" placeholder="Nota / motivo de reprobación" required>
            </div>
            <button type="submit" class="btn btn-outline-danger w-100" data-confirm="¿Registrar el expediente como REPROBADO?">Registrar reprobado</button>
          </form>
        </div>
      <?php else: ?>
        <div class="card card-custom p-4 text-center text-muted">
          <i class="bi bi-lock fs-2 d-block mb-2"></i>
          <p class="mb-0"><?= $esAbierto ? 'Sin permiso para registrar transiciones.' : 'El expediente está en estado terminal (' . e($etiquetasEstado[$estado] ?? $estado) . ').' ?></p>
        </div>
      <?php endif; ?>
    </div>
    <div class="col-lg-7">
      <div class="card card-custom p-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-timeline text-primary me-1"></i> Línea de tiempo de etapas</h6>
        <?php if (empty($historialEtapas)): ?>
          <p class="text-muted small mb-0">Sin etapas registradas.</p>
        <?php else: ?>
          <div class="timeline">
            <?php foreach ($historialEtapas as $et): ?>
              <div class="d-flex gap-3 mb-3">
                <div class="text-center" style="width:110px;">
                  <span class="badge bg-info bg-opacity-10 text-info border border-info-subtle"><?= $etiquetasEtapa[$et['etapa']] ?? e($et['etapa']) ?></span>
                </div>
                <div class="flex-grow-1">
                  <div class="small">
                    <strong><?= date('d/m/Y H:i', strtotime($et['fecha_inicio'])) ?></strong>
                    <?php if ($et['fecha_fin']): ?>
                      → <span class="text-muted"><?= date('d/m/Y H:i', strtotime($et['fecha_fin'])) ?></span>
                    <?php else: ?>
                      <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle ms-1">en curso</span>
                    <?php endif; ?>
                  </div>
                  <?php if ($et['resultado'] && $et['resultado'] !== 'activo'): ?>
                    <div><span class="badge bg-light text-dark border"><?= e(ucfirst($et['resultado'])) ?></span></div>
                  <?php endif; ?>
                  <?php if ($et['observacion']): ?><div class="text-muted small"><?= e($et['observacion']) ?></div><?php endif; ?>
                  <?php if ($et['registrado_nombre']): ?><div class="text-muted" style="font-size:.68rem;">por <?= e($et['registrado_nombre'] . ' ' . ($et['registrado_apellido'] ?? '')) ?></div><?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php elseif ($tabActivo === 'tutor'): ?>
  <div class="row g-4">
    <div class="col-lg-5">
      <?php if ($asignacionVigente): ?>
        <div class="card card-custom p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-person-check text-primary me-1"></i> Tutor vigente</h6>
          <div class="d-flex align-items-center gap-3 mb-3">
            <?= avatarHTML('', e($asignacionVigente['tutor_nombre'] . ' ' . $asignacionVigente['tutor_apellido']), 'avatar-md') ?>
            <div>
              <div class="fw-semibold"><?= e(trim($asignacionVigente['tutor_nombre'] . ' ' . $asignacionVigente['tutor_apellido'])) ?></div>
              <div class="text-muted small"><?= e($asignacionVigente['especialidad'] ?: '—') ?></div>
            </div>
          </div>
          <dl class="row small mb-3">
            <dt class="col-sm-6 text-muted fw-normal">Asignado el</dt>
            <dd class="col-sm-6"><?= date('d/m/Y', strtotime($asignacionVigente['fecha_asignacion'])) ?></dd>
            <dt class="col-sm-6 text-muted fw-normal">Disponibilidad consultada</dt>
            <dd class="col-sm-6"><?= $asignacionVigente['disponibilidad_consultada'] ? '<i class="bi bi-check-circle-fill text-success"></i> Sí' : '<i class="bi bi-x-circle-fill text-muted"></i> No' ?></dd>
            <dt class="col-sm-6 text-muted fw-normal">Referencia Decanatura</dt>
            <dd class="col-sm-6"><?= e($asignacionVigente['referencia_decanatura'] ?: '—') ?></dd>
            <dt class="col-sm-6 text-muted fw-normal">Carta</dt>
            <dd class="col-sm-6"><?= e($asignacionVigente['numero_carta'] ?: 'No emitida') ?></dd>
          </dl>

          <?php if ($puedeCambiarTutor && $esAbierto): ?>
            <form method="POST" action="/controllers/mg_tutor_guardar.php">
              <input type="hidden" name="id_declaracion" value="<?= $id ?>">
              <input type="hidden" name="accion" value="cambiar">
              <?= campoCsrf() ?>
              <h6 class="fw-bold mb-2 mt-3 text-danger"><i class="bi bi-arrow-repeat me-1"></i> Cambio o renuncia (HU-026)</h6>
              <div class="mb-2">
                <label class="form-label small fw-semibold">Motivo (renuncia/cambio)</label>
                <input type="text" name="motivo_fin" class="form-control" placeholder="Renuncia del docente, incompatibilidad..." required>
              </div>
              <div class="mb-2">
                <label class="form-label small fw-semibold">Nuevo tutor</label>
                <select name="id_tutor" class="form-select" required>
                  <option value="">— Seleccionar docente —</option>
                  <?php foreach ($tutoresDisponibles as $t): ?>
                    <option value="<?= (int)$t['id_tutor'] ?>">
                      <?= e(trim($t['nombre'] . ' ' . $t['apellido'])) ?> (carga: <?= (int)$t['carga_actual'] ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="disponibilidad_consultada" id="dispCambio" value="1" checked>
                <label class="form-check-label small" for="dispCambio">Disponibilidad consultada previamente</label>
              </div>
              <div class="mb-2">
                <label class="form-label small fw-semibold">Referencia Decanatura</label>
                <input type="text" name="referencia_decanatura" class="form-control" placeholder="Nota/resolución N°...">
              </div>
              <button type="submit" class="btn btn-outline-danger w-100" data-confirm="¿Confirmar el cambio/renuncia de tutor?">Ejecutar cambio de tutor</button>
            </form>
          <?php endif; ?>
        </div>
      <?php elseif ($puedeAsignar): ?>
        <div class="card card-custom p-4">
          <h6 class="fw-bold mb-3"><i class="bi bi-person-plus text-primary me-1"></i> Asignar tutor (HU-025)</h6>
          <?php if (!$expediente['requiere_tutor']): ?>
            <div class="alert alert-warning border small mb-3">
              <i class="bi bi-exclamation-triangle me-1"></i>
              Esta modalidad no requiere tutor según RN-MG-01, pero puedes asignar uno de forma opcional.
            </div>
          <?php endif; ?>
          <form method="POST" action="/controllers/mg_tutor_guardar.php">
            <input type="hidden" name="id_declaracion" value="<?= $id ?>">
            <input type="hidden" name="accion" value="asignar">
            <?= campoCsrf() ?>
            <div class="mb-2">
              <label class="form-label small fw-semibold">Docente tutor</label>
              <select name="id_tutor" class="form-select" required>
                <option value="">— Seleccionar docente —</option>
                <?php foreach ($tutoresDisponibles as $t): ?>
                  <option value="<?= (int)$t['id_tutor'] ?>">
                    <?= e(trim($t['nombre'] . ' ' . $t['apellido'])) ?> — <?= e($t['especialidad'] ?: '—') ?> (carga: <?= (int)$t['carga_actual'] ?>/<?= (int)$tutorCargaRecomendada ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" name="disponibilidad_consultada" id="dispAsigna" value="1" checked>
              <label class="form-check-label small" for="dispAsigna">Disponibilidad consultada previamente (RN-MG-04)</label>
            </div>
            <div class="mb-2">
              <label class="form-label small fw-semibold">Referencia Decanatura</label>
              <input type="text" name="referencia_decanatura" class="form-control" placeholder="Nota/resolución N°...">
            </div>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Observaciones (opcional)</label>
              <textarea name="observaciones" class="form-control" rows="2" placeholder="Observaciones de la asignación..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100"><i class="bi bi-person-check me-1"></i>Asignar tutor</button>
          </form>
        </div>
      <?php else: ?>
        <div class="card card-custom p-4 text-center text-muted">
          <i class="bi bi-person-x fs-2 d-block mb-2"></i>
          <p class="mb-0">Sin tutor asignado. No tienes permiso para asignar.</p>
        </div>
      <?php endif; ?>
    </div>
    <div class="col-lg-7">
      <div class="card card-custom p-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-people text-primary me-1"></i> Historial de asignaciones</h6>
        <?php if (empty($historialTutor)): ?>
          <p class="text-muted small mb-0">Aún no se ha asignado tutor a este expediente.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead><tr><th>Tutor</th><th>Asignado</th><th>Fin</th><th>Estado</th><th>Motivo</th></tr></thead>
              <tbody>
                <?php foreach ($historialTutor as $as):
                  $claseAsig = $as['estado'] === 'vigente' ? 'bg-success bg-opacity-10 text-success border border-success-subtle' : ($as['estado'] === 'reemplazada' ? 'bg-warning bg-opacity-10 text-warning border border-warning-subtle' : 'bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle');
                ?>
                  <tr>
                    <td class="small fw-semibold"><?= e(trim($as['tutor_nombre'] . ' ' . $as['tutor_apellido'])) ?></td>
                    <td class="small"><?= date('d/m/Y', strtotime($as['fecha_asignacion'])) ?></td>
                    <td class="small text-muted"><?= $as['fecha_fin'] ? date('d/m/Y', strtotime($as['fecha_fin'])) : '—' ?></td>
                    <td><span class="badge <?= $claseAsig ?>"><?= e(ucfirst($as['estado'])) ?></span></td>
                    <td class="small text-muted"><?= e($as['motivo_fin'] ?: '—') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php elseif ($tabActivo === 'documentos'): ?>
  <div class="row g-4">
    <div class="col-lg-4">
      <div class="card card-custom p-4">
        <h6 class="fw-bold mb-3"><i class="bi bi-envelope-paper text-primary me-1"></i> Generar documento (HU-027)</h6>
        <?php if ($asignacionVigente): ?>
          <form method="POST" action="/controllers/mg_documento_generar.php">
            <input type="hidden" name="id_declaracion" value="<?= $id ?>">
            <?= campoCsrf() ?>
            <div class="mb-3">
              <label class="form-label small fw-semibold">Tipo de documento</label>
              <select name="tipo" class="form-select">
                <option value="CARTA_ASIGNACION_TUTOR">Carta de asignación de Tutor</option>
              </select>
            </div>
            <button type="submit" class="btn btn-primary w-100" <?= $puedeDocumentos ? '' : 'disabled' ?>>
              <i class="bi bi-file-earmark-text me-1"></i>Generar y descargar
            </button>
          </form>
          <p class="text-muted small mt-3 mb-0">
            <i class="bi bi-info-circle me-1"></i>La carta usa la plantilla editable y guarda un snapshot para reimpresión fiel.
          </p>
        <?php else: ?>
          <div class="alert alert-light border small mb-0">
            <i class="bi bi-info-circle me-1 text-primary"></i>
            Asigna primero un tutor para poder generar la carta de asignación.
          </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col-lg-8">
      <div class="card card-custom p-3">
        <h6 class="fw-bold mb-3"><i class="bi bi-files text-primary me-1"></i> Documentos emitidos</h6>
        <?php if (empty($documentos)): ?>
          <p class="text-muted small mb-0">No se han emitido documentos para este expediente.</p>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table align-middle">
              <thead><tr><th>Documento</th><th>Correlativo</th><th>Destinatario</th><th>Emitido</th><th class="text-end">Ver</th></tr></thead>
              <tbody>
                <?php foreach ($documentos as $doc): ?>
                  <tr>
                    <td class="small fw-semibold"><?= e($doc['tipo']) ?></td>
                    <td class="small"><?= e($doc['numero_correlativo'] ?: '—') ?></td>
                    <td class="small"><?= e($doc['destinatario'] ?: '—') ?></td>
                    <td class="small text-muted"><?= date('d/m/Y H:i', strtotime($doc['fecha_generacion'])) ?></td>
                    <td class="text-end">
                      <a class="btn btn-sm btn-light border" href="/controllers/mg_documento_ver.php?id=<?= (int)$doc['id_documento'] ?>" target="_blank" title="Ver/Imprimir"><i class="bi bi-eye"></i></a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>