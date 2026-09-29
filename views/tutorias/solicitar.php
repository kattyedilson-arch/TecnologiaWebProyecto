<?php
// =========================================================
// VISTA: SOLICITAR TUTORÍA (views/tutorias/solicitar.php)
// ---------------------------------------------------------
// Página DEDICADA al estudiante. Cada MATERIA de su carrera es una
// card, y dentro de cada card viven los HORARIOS que la
// administración publicó para esa materia. Un mismo horario puede
// estar en dos situaciones:
//
//   - CON DOCENTE (oferta 'asignada')
//       Elige el horario con el radio y confirma abajo. La fecha la
//       asigna el sistema en el próximo día libre del turno.
//
//   - SIN DOCENTE (oferta 'abierta')
//       Nadie ha aceptado impartirlo todavía, así que no hay sesión
//       que reservar. El botón "Registrar interés" deja anotada la
//       petición y avisa a la administración; en cuanto un tutor
//       acepte la oferta, el estudiante recibe la notificación y
//       vuelve a esta pantalla para reservar.
//
// El estudiante NO modifica aulas, días libres ni modalidad: el
// turno, la modalidad y el aula los define la administración y la
// fecha de la sesión se asigna automáticamente.
//
// Variables del controlador:
//   $materiasAgrupadas, $ofertasDisponibles, $errores, $carreraEtiqueta,
//   $carrera, $sistemasEstudio, $sistemaDeclarado, $turnos, $carreraEstudiante
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
$tituloPagina = 'Materias Disponibles - UPDS';
include __DIR__ . '/../layouts/header.php';
$rolAux = $_SESSION['rol'] ?? 'estudiante';
$volverUrl = ($rolAux === 'estudiante') ? '../views/estudiante/panel.php' : 'tutorias_listar.php';

// Helper: etiqueta y color de la modalidad
function modalidadBadge($modalidad) {
    if (($modalidad ?? 'presencial') === 'virtual') {
        return '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1"><i class="bi bi-camera-video me-1"></i>Virtual</span>';
    }
    return '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-geo-alt me-1"></i>Presencial</span>';
}

$tiposTutoria = [
    'pregrado' => 'Pregrado',
    'posgrado' => 'Posgrado',
    'invierno' => 'Invierno (Intensivo)',
    'verano'   => 'Verano (Intensivo)',
];

// Selección y textarea que vuelven del POST cuando la validación falla
$ofertaPost       = (int)($_POST['id_oferta'] ?? 0);
$observacionesPost = (string)($_POST['observaciones'] ?? '');

$hayOfertas    = !empty($ofertasDisponibles);
$totalHorarios = count($ofertasDisponibles);
$conDocente    = count(array_filter($ofertasDisponibles, function ($o) { return empty($o['sin_docente']); }));
$sinDocente    = $totalHorarios - $conDocente;
?>

<div class="row justify-content-center">
  <div class="col-lg-11 col-xl-10">
    <div class="hero-band p-4 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-calendar-plus-fill"></i>
            <span>Materias Disponibles</span>
          </h3>
          <p class="text-white-50 mb-0">
            Elige la materia y el horario. Si todavía no hay docente, puedes registrar tu interés
            y te avisamos en cuanto se asigne.
          </p>
        </div>
        <a href="<?= $volverUrl ?>" class="btn btn-light d-flex align-items-center gap-1">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>
    </div>

    <?php if (!empty($errores)): ?>
      <div class="alert alert-danger py-2 px-3 rounded-3 shadow-sm mb-4">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Corrige los siguientes datos:</div>
        <ul class="mb-0 ps-3 small">
          <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <div class="alert alert-info d-flex align-items-start gap-2 py-2 px-3 rounded-3 shadow-sm mb-4 small">
      <i class="bi bi-info-circle-fill mt-1"></i>
      <div>
        El <b>turno, la modalidad y el aula</b> los define exclusivamente la administración.
        Tú eliges la <b>materia</b> y un <b>horario con docente</b>, y la
        <b>fecha de la sesión se asigna automáticamente</b> al próximo día libre del turno;
        si el horario tiene <b>sesión grupal</b>, te unes a la fecha que ya tenga cupo.
        Los horarios marcados <b>sin docente</b> todavía no se pueden reservar:
        registra tu interés y te avisaremos cuando haya tutor.
      </div>
    </div>

    <?php if (!$hayOfertas): ?>
      <div class="card card-custom p-4 p-md-5 text-center">
        <div class="mb-2 fs-1 text-secondary"><i class="bi bi-calendar-x"></i></div>
        <h5 class="fw-bold text-dark mb-2">Aún no hay horarios disponibles</h5>
        <p class="text-muted mb-4 small">
          La administración aún no publica horarios para las materias de tu carrera
          (<?= htmlspecialchars($carreraEstudiante ?: 'sin asignar') ?>).
          Vuelve más tarde o contacta a la administración.
        </p>
        <div class="d-flex justify-content-center gap-2">
          <a href="<?= $volverUrl ?>" class="btn btn-light px-4 py-2 rounded-3">Volver a mi panel</a>
        </div>
      </div>
    <?php else: ?>

      <!-- ============================================================
           FILTROS: tipo de tutoría, sistema de estudio y turno.
           Actúan sobre las cards: una card se oculta cuando ninguna de
           sus filas cumple la combinación elegida.
           ============================================================ -->
      <div class="card card-custom p-3 p-md-4 mb-4">
        <div class="row g-3">
          <!-- Carrera (informativa) + metadatos institucionales -->
          <div class="col-12">
            <label class="form-label fw-semibold text-secondary small text-uppercase" for="selCarrera">Carrera</label>
            <select id="selCarrera" class="form-select rounded-3 py-2" disabled
                    title="Tu carrera es la de tu ficha académica; no se puede cambiar aquí.">
              <option selected><?= htmlspecialchars($carreraEtiqueta) ?></option>
            </select>

            <div class="row g-2 mt-1" id="metadatosCarrera">
              <div class="col-6 col-md-4">
                <div class="meta-dato">
                  <span class="meta-label"><i class="bi bi-mortarboard me-1"></i>Modelo de Estudio</span>
                  <span class="meta-valor" id="metaModelo"><?= htmlspecialchars($carrera['modelo_estudio'] ?? '—') ?></span>
                </div>
              </div>
              <div class="col-6 col-md-4">
                <div class="meta-dato">
                  <span class="meta-label"><i class="bi bi-building me-1"></i>Sistema de la carrera</span>
                  <span class="meta-valor" id="metaSistema"><?= htmlspecialchars($sistemasEstudio[$sistemaDeclarado]) ?></span>
                </div>
              </div>
              <div class="col-6 col-md-4">
                <div class="meta-dato">
                  <span class="meta-label"><i class="bi bi-clock me-1"></i>Turno</span>
                  <span class="meta-valor" id="metaTurno">—</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Tipo de tutoría -->
          <div class="col-12 col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase" for="selTipo">Tipo de Tutoría *</label>
            <select name="tipo_tutoria" id="selTipo" class="form-select rounded-3 py-2" required>
              <option value="" disabled selected>Selecciona el tipo...</option>
              <?php foreach ($tiposTutoria as $valor => $etiqueta): ?>
                <option value="<?= $valor ?>"><?= htmlspecialchars($etiqueta) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text" id="textoTipo"><i class="bi bi-info-circle me-1"></i>Se habilitan los tipos con horarios publicados.</div>
            <div class="invalid-feedback">Debes seleccionar el tipo de tutoría.</div>
          </div>

          <!-- Sistema de estudio (botones): filtro por modalidad del horario -->
          <div class="col-12 col-md-6">
            <span class="form-label fw-semibold text-secondary small text-uppercase d-block">Sistema de Estudio</span>
            <div class="grupo-botones" id="grupoSistema" role="group" aria-label="Sistema de estudio">
              <?php foreach ($sistemasEstudio as $valor => $etiqueta): ?>
                <button type="button"
                        class="btn-opcion"
                        data-sistema="<?= htmlspecialchars($valor) ?>"
                        aria-pressed="false">
                  <i class="bi <?= $valor === 'virtual' ? 'bi-camera-video' : 'bi-geo-alt' ?> me-1"></i><?= htmlspecialchars($etiqueta) ?>
                </button>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="sistema_estudio" id="inpSistema" value="">
            <div class="form-text" id="textoSistema">
              <i class="bi bi-info-circle me-1"></i>Opcional: sin selección se ven presencial y virtual.
            </div>
          </div>

          <!-- Turno (botones generados desde el catálogo 'turnos') -->
          <div class="col-12">
            <span class="form-label fw-semibold text-secondary small text-uppercase d-block">Turno *</span>
            <div class="grupo-botones" id="grupoTurno" role="group" aria-label="Turno">
              <?php foreach ($turnos as $t): ?>
                <button type="button"
                        class="btn-opcion btn-turno"
                        data-turno="<?= (int)$t['id_turno'] ?>"
                        data-nombre="<?= htmlspecialchars(mb_strtoupper($t['nombre_turno'])) ?>">
                  <?= htmlspecialchars(mb_strtoupper($t['nombre_turno'])) ?>
                  <small><?= substr($t['hora_inicio'], 0, 5) ?> – <?= substr($t['hora_fin'], 0, 5) ?></small>
                </button>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="turno" id="inpTurno" value="">
            <div class="form-text" id="textoTurno"><i class="bi bi-info-circle me-1"></i>Elige un turno. Se habilitan solo los que tienen horario publicado.</div>
            <div class="invalid-feedback" id="errorTurno">Debes seleccionar un turno con horario publicado.</div>
          </div>
        </div>
      </div>

      <!-- ============================================================
           CARDS POR MATERIA
           ============================================================ -->
      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
        <h6 class="fw-bold text-dark mb-0 d-flex align-items-center gap-2">
          <i class="bi bi-journals text-primary"></i>
          <span>Materias de tu carrera</span>
        </h6>
        <div class="d-flex flex-wrap gap-2 small" id="contadorMaterias">
          <span class="badge text-bg-light border"><i class="bi bi-calendar-week me-1"></i><?= $totalHorarios ?> horario(s)</span>
          <span class="badge text-bg-light border"><i class="bi bi-person-video3 me-1"></i><?= $conDocente ?> con docente</span>
          <span class="badge text-bg-warning border"><i class="bi bi-hourglass-split me-1"></i><?= $sinDocente ?> sin docente</span>
        </div>
      </div>

      <div class="row g-3" id="contenedorMaterias">
        <?php foreach ($materiasAgrupadas as $m): ?>
          <div class="col-12 col-md-6 col-xl-4 materia-card" data-id-materia="<?= (int)$m['id_materia'] ?>">
            <div class="card card-custom h-100 overflow-hidden">
              <div class="card-body d-flex flex-column gap-2">

                <!-- Cabecera de la card -->
                <div class="d-flex justify-content-between align-items-start gap-2">
                  <div class="d-flex align-items-start gap-2 min-w-0">
                    <span class="stat-ico bg-primary bg-opacity-10 text-primary" style="width:40px;height:40px;font-size:1.05rem;">
                      <i class="bi bi-journal-bookmark-fill"></i>
                    </span>
                    <div class="min-w-0">
                      <div class="fw-bold text-dark text-truncate" title="<?= htmlspecialchars($m['nombre_materia']) ?>">
                        <?= htmlspecialchars($m['nombre_materia']) ?>
                      </div>
                      <small class="text-muted text-truncate d-block">
                        <?= htmlspecialchars($m['nombre_carrera'] ?: 'Carrera no asignada') ?>
                      </small>
                    </div>
                  </div>
                  <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle flex-shrink-0">
                    <?= (int)$m['total_horarios'] ?>
                  </span>
                </div>

                <div class="d-flex flex-column gap-2">
                    <?php foreach ($m['ofertas'] as $o): ?>
                      <?php
                        $sinDocenteOferta = !empty($o['sin_docente']);
                        $yaSolicitado     = !empty($o['mi_solicitud']);
                        $modalidadOferta  = ($o['modalidad'] ?? 'presencial') === 'virtual' ? 'virtual' : 'presencial';
                        $cupoOferta      = max(1, (int)($o['cupo'] ?? 1));
                        // Un horario con docente y sin cupo libre no se puede
                        // reservar: el servidor lo rechaza, así que tampoco se
                        // ofrece como opción (evita el error al enviar).
                        $cupoCompleto    = !$sinDocenteOferta
                                           && (int)($o['inscritos'] ?? 0) >= $cupoOferta;
                        $turnoEtiqueta   = $o['nombre_turno'] ?? '';
                        $horario         = substr($o['turno_hora_inicio'] ?? '', 0, 5) . ' – ' . substr($o['turno_hora_fin'] ?? '', 0, 5);
                      ?>
                      <div class="oferta-fila border rounded-3 p-2 d-flex align-items-start gap-2
                                  <?= ($sinDocenteOferta || $cupoCompleto) ? 'oferta-espera' : '' ?>"
                           data-nivel="<?= htmlspecialchars($o['nivel_academico']) ?>"
                           data-turno="<?= (int)$o['id_turno'] ?>"
                           data-modalidad="<?= $modalidadOferta ?>"
                           data-reservable="<?= ($sinDocenteOferta || $cupoCompleto) ? '0' : '1' ?>">

                        <?php if ($sinDocenteOferta): ?>
                          <!-- Sin docente: no se puede reservar, se puede pedir -->
                          <span class="oferta-espera-ico flex-shrink-0"><i class="bi bi-hourglass-split"></i></span>
                          <span class="flex-grow-1 min-w-0">
                            <span class="d-flex flex-wrap align-items-center gap-1">
                              <span class="badge bg-warning text-dark border"><i class="bi bi-person-x me-1"></i>Sin docente</span>
                              <span class="badge bg-light text-dark border">
                                <i class="bi bi-clock me-1"></i><?= htmlspecialchars($turnoEtiqueta) ?> (<?= $horario ?>)
                              </span>
                              <?= modalidadBadge($o['modalidad'] ?? 'presencial') ?>
                            </span>
                            <span class="d-block small text-muted mt-1">
                              <?= htmlspecialchars($o['nivel_academico'] ?? 'pregrado') ?>
                              <?php if (!empty($o['lugar_o_enlace'])): ?>
                                <span class="mx-1">•</span><?= htmlspecialchars($o['lugar_o_enlace']) ?>
                              <?php endif; ?>
                            </span>
                            <span class="d-block small text-muted mt-1">
                              <i class="bi bi-people me-1"></i>
                              <?php if ((int)($o['interesados'] ?? 0) > 0): ?>
                                <?= (int)$o['interesados'] ?> estudiante(s) esperando docente
                              <?php else: ?>
                                Sé el primero en pedirla
                              <?php endif; ?>
                            </span>
                          </span>
                          <?php if ($yaSolicitado): ?>
                            <span class="btn btn-sm btn-outline-secondary disabled flex-shrink-0" tabindex="-1">
                              <i class="bi bi-check-circle me-1"></i>Enviada
                            </span>
                          <?php else: ?>
                            <button type="button" class="btn btn-sm btn-primary btn-solicitar flex-shrink-0"
                                    data-id-oferta="<?= (int)$o['id_oferta'] ?>"
                                    data-materia="<?= htmlspecialchars($m['nombre_materia']) ?>"
                                    data-turno="<?= htmlspecialchars($turnoEtiqueta) ?>">
                              <i class="bi bi-hand-index-thumb me-1"></i>Pedir
                            </button>
                          <?php endif; ?>

                        <?php elseif ($cupoCompleto): ?>
                          <!-- Con docente pero sin cupo: no se puede reservar -->
                          <span class="oferta-espera-ico flex-shrink-0"><i class="bi bi-people-fill"></i></span>
                          <span class="flex-grow-1 min-w-0">
                            <span class="d-flex flex-wrap align-items-center gap-1">
                              <span class="badge bg-light text-dark border">
                                <i class="bi bi-clock me-1"></i><?= htmlspecialchars($turnoEtiqueta) ?> (<?= $horario ?>)
                              </span>
                              <?= modalidadBadge($o['modalidad'] ?? 'presencial') ?>
                              <span class="badge bg-secondary text-light border">
                                <i class="bi bi-person-lock me-1"></i>Cupo completo
                              </span>
                            </span>
                            <span class="d-block small text-muted mt-1">
                              <i class="bi bi-person-video3 me-1"></i>Prof. <?= htmlspecialchars(trim(($o['tut_nombre'] ?? '') . ' ' . ($o['tut_apellido'] ?? ''))) ?>
                              <span class="mx-1">•</span><?= htmlspecialchars($o['nivel_academico'] ?? 'pregrado') ?>
                            </span>
                            <span class="d-block small text-muted">
                              <?= (int)($o['inscritos'] ?? 0) ?> de <?= $cupoOferta ?> lugares ocupados
                            </span>
                          </span>
                        <?php else: ?>
                          <!-- Con docente: se elige con el radio -->
                          <input type="radio" name="oferta_sel" value="<?= (int)$o['id_oferta'] ?>"
                                 class="form-check-input mt-1 oferta-radio flex-shrink-0"
                                 data-materia="<?= htmlspecialchars($m['nombre_materia']) ?>"
                                 data-turno="<?= htmlspecialchars($turnoEtiqueta) ?>"
                                 data-horario="<?= htmlspecialchars($horario) ?>"
                                 data-modalidad="<?= htmlspecialchars($o['modalidad'] ?? 'presencial') ?>"
                                 data-lugar="<?= htmlspecialchars($o['lugar_o_enlace'] ?? '') ?>"
                                 data-docente="<?= htmlspecialchars(trim(($o['tut_nombre'] ?? '') . ' ' . ($o['tut_apellido'] ?? ''))) ?>"
                                 <?= ($ofertaPost && $ofertaPost === (int)$o['id_oferta']) ? 'checked' : '' ?>>
                          <label class="flex-grow-1 min-w-0" style="cursor:pointer;">
                            <span class="d-flex flex-wrap align-items-center gap-1">
                              <span class="badge bg-light text-dark border">
                                <i class="bi bi-clock me-1"></i><?= htmlspecialchars($turnoEtiqueta) ?> (<?= $horario ?>)
                              </span>
                              <?= modalidadBadge($o['modalidad'] ?? 'presencial') ?>
                              <?php if (!empty($o['lugar_o_enlace'])): ?>
                                <span class="badge bg-light text-muted border"><i class="bi bi-geo me-1"></i><?= htmlspecialchars($o['lugar_o_enlace']) ?></span>
                              <?php endif; ?>
                            </span>
                            <span class="d-block small text-dark mt-1">
                              <i class="bi bi-person-video3 me-1 text-primary"></i>Prof. <?= htmlspecialchars(trim(($o['tut_nombre'] ?? '') . ' ' . ($o['tut_apellido'] ?? ''))) ?>
                            </span>
                            <span class="d-block small text-muted">
                              <?= htmlspecialchars($o['nivel_academico'] ?? 'pregrado') ?>
                              <span class="mx-1">•</span>
                              <?php if ($cupoOferta > 1): ?>
                                <?php if (!empty($o['fecha_sesion'])): ?>
                                  Sesión grupal de <?= $cupoOferta ?> · te unes a la del <?= date('d/m/Y', strtotime($o['fecha_sesion'])) ?>
                                <?php else: ?>
                                  Sesión grupal de <?= $cupoOferta ?>, aún vacía
                                <?php endif; ?>
                              <?php else: ?>
                                Tutoría individual
                              <?php endif; ?>
                            </span>
                          </label>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                </div>
              </div>

                <div class="card-footer bg-white border-0 pt-0 pb-3 d-flex flex-wrap gap-1">
                  <?php if ($m['con_docente'] > 0): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle">
                      <i class="bi bi-check-circle me-1"></i><?= (int)$m['con_docente'] ?> disponible(s)
                    </span>
                  <?php endif; ?>
                  <?php if ($m['esperando'] > 0): ?>
                    <span class="badge bg-warning bg-opacity-25 text-dark border">
                      <i class="bi bi-hourglass-split me-1"></i><?= (int)$m['esperando'] ?> sin docente
                    </span>
                  <?php endif; ?>
                </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Aviso cuando los filtros dejan la grilla vacía -->
      <div id="avisoVacio" class="d-none text-center text-muted py-5">
        <i class="bi bi-calendar-x d-block fs-1 mb-2 text-secondary"></i>
        <p class="mb-0 small">No hay horarios publicados con esta combinación. Contacta a la administración.</p>
      </div>

      <!-- ============================================================
           CONFIRMACIÓN: resumen de la selección, temas a tratar y envío
           ============================================================ -->
      <div class="card card-custom mt-4 sombra-flotante">
        <div class="card-body">
          <form method="POST" id="formReserva" autocomplete="off" class="needs-validation" novalidate>
            <?= campoCsrf() ?>
            <input type="hidden" name="id_oferta" id="inpOferta" value="<?= $ofertaPost ?>">
            <div class="row g-3 align-items-end">
              <div class="col-12 col-lg-4">
                <label class="form-label fw-semibold text-secondary small text-uppercase">Horario elegido</label>
                <div id="resumenOferta" class="resumen-seleccion">
                  <i class="bi bi-info-circle me-1"></i>Elige un horario con docente en las tarjetas de arriba.
                </div>
              </div>
              <div class="col-12 col-lg-5">
                <label class="form-label fw-semibold text-secondary small text-uppercase" for="obs">Temas o Preguntas a Tratar</label>
                <textarea name="observaciones" id="obs" class="form-control rounded-3" rows="2" maxlength="1000"
                          placeholder="Describe brevemente las dudas o temas puntuales que necesitas repasar con el tutor..."><?= htmlspecialchars($observacionesPost) ?></textarea>
              </div>
              <div class="col-12 col-lg-3 d-flex gap-2">
                <a href="<?= $volverUrl ?>" class="btn btn-light px-4 py-2 rounded-3">Cancelar</a>
                <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm flex-grow-1" id="btnConfirmar" disabled>
                  <i class="bi bi-send-fill me-1"></i>Confirmar
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if ($hayOfertas): ?>
<script>
  (() => {
    const selTipo    = document.getElementById('selTipo');
    const inpTurno   = document.getElementById('inpTurno');
    const inpSistema = document.getElementById('inpSistema');
    const grupoTurno = document.getElementById('grupoTurno');
    const grupoSistema = document.getElementById('grupoSistema');
    const metaTurno  = document.getElementById('metaTurno');
    const textoSistema = document.getElementById('textoSistema');
    const textoTipo  = document.getElementById('textoTipo');
    const textoTurno = document.getElementById('textoTurno');
    const errorTurno = document.getElementById('errorTurno');
    const inpOferta  = document.getElementById('inpOferta');
    const btnConfirmar = document.getElementById('btnConfirmar');
    const resumen    = document.getElementById('resumenOferta');
    const avisoVacio = document.getElementById('avisoVacio');

    const radios   = Array.from(document.querySelectorAll('.oferta-radio'));
    const filas    = Array.from(document.querySelectorAll('.oferta-fila'));
    const cards    = Array.from(document.querySelectorAll('.materia-card'));
    const botonesTurno = Array.from(document.querySelectorAll('.btn-turno'));
    const botonesSistema = Array.from(document.querySelectorAll('#grupoSistema .btn-opcion'));
    const sinOfertaMsg = 'No hay horarios publicados con esta combinación. Contacta a la administración.';

    // ---- Utilidades sobre las filas de horario ----
    function filasVisibles() {
      return filas.filter(f => f.style.display !== 'none');
    }

    // El sistema de estudio es opcional: vacío significa "ambas modalidades".
    function coincide(fila, nivel, turnoId, sistema) {
      return (!nivel   || fila.dataset.nivel === nivel) &&
             (!turnoId || fila.dataset.turno === turnoId) &&
             (!sistema || fila.dataset.modalidad === sistema);
    }

    // Tipos (niveles) con horario publicado en la carrera del estudiante
    function tiposDisponibles() {
      const set = new Set();
      filas.forEach(f => set.add(f.dataset.nivel));
      return set;
    }

    function turnosDisponibles(nivel) {
      const set = new Set();
      filas.forEach(f => { if (!nivel || f.dataset.nivel === nivel) set.add(f.dataset.turno); });
      return set;
    }

    // Modalidades con horario publicado en la carrera del estudiante
    function sistemasDisponibles() {
      const set = new Set();
      filas.forEach(f => set.add(f.dataset.modalidad));
      return set;
    }

    // ---- Habilitar/deshabilitar los filtros ----
    function sincronizarSelects() {
      const tiposOk = tiposDisponibles();

      Array.from(selTipo.options).forEach(op => { op.disabled = op.value !== '' && !tiposOk.has(op.value); });
      if (selTipo.value && !tiposOk.has(selTipo.value)) selTipo.value = '';

      const turnosOk = turnosDisponibles(selTipo.value);

      botonesTurno.forEach(b => {
        const id = b.dataset.turno;
        const disponible = turnosOk.has(id);
        b.disabled = !disponible;
        b.classList.toggle('btn-activo', disponible && id === inpTurno.value);
        b.setAttribute('aria-pressed', (disponible && id === inpTurno.value) ? 'true' : 'false');
      });

      if (inpTurno.value && !turnosOk.has(inpTurno.value)) inpTurno.value = '';
      if (!selTipo.value) inpTurno.value = '';

      const botonActivo = botonesTurno.find(b => b.dataset.turno === inpTurno.value);
      if (metaTurno) metaTurno.textContent = botonActivo ? botonActivo.dataset.nombre : '—';

      if (tiposOk.size === 0) {
        textoTipo.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>Ningún tipo de tutoría tiene horario publicado para tu carrera.';
      } else {
        textoTipo.innerHTML = '<i class="bi bi-info-circle me-1"></i>' + tiposOk.size + ' tipo(s) con horarios publicados.';
      }

      if (!selTipo.value) textoTurno.innerHTML = '<i class="bi bi-info-circle me-1"></i>Primero elige el tipo de tutoría.';
      else if (turnosOk.size === 0) textoTurno.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>Ningún turno tiene horario publicado para este tipo.';
      else textoTurno.innerHTML = '<i class="bi bi-info-circle me-1"></i>' + turnosOk.size + ' turno(s) disponibles para este tipo.';

      // Sistema de estudio: no se deshabilita aunque una modalidad no tenga
      // horario; al elegirla el estudiante ve el aviso de que no hay
      // combinaciones, que informa más que un botón muerto.
      const sistemasOk = sistemasDisponibles();
      botonesSistema.forEach(b => {
        const activo = b.dataset.sistema === inpSistema.value;
        b.classList.toggle('btn-activo', activo);
        b.setAttribute('aria-pressed', activo ? 'true' : 'false');
      });

      if (!inpSistema.value) {
        textoSistema.innerHTML = '<i class="bi bi-info-circle me-1"></i>Opcional: sin selección se ven presencial y virtual.';
      } else if (!sistemasOk.has(inpSistema.value)) {
        textoSistema.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>No hay horarios ' + (inpSistema.value === 'virtual' ? 'virtuales' : 'presenciales') + ' publicados para tu carrera.';
      } else {
        textoSistema.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i>Mostrando solo horarios ' + (inpSistema.value === 'virtual' ? 'virtuales' : 'presenciales') + '.';
      }
    }

    // ---- Aplicar los filtros a filas y cards ----
    function filtrarHorarios() {
      const nivel   = selTipo.value;
      const turnoId = inpTurno.value;
      const sistema = inpSistema.value;

      filas.forEach(f => { f.style.display = coincide(f, nivel, turnoId, sistema) ? '' : 'none'; });

      // Toda card viene con al menos una fila (el controlador no lista
      // materias sin ofertas): basta con que le quede una visible.
      let cardsVisibles = 0;
      cards.forEach(card => {
        const visibles = Array.from(card.querySelectorAll('.oferta-fila'))
          .filter(f => f.style.display !== 'none');
        const visible = visibles.length > 0;
        card.style.display = visible ? '' : 'none';
        if (visible) cardsVisibles++;
      });

      avisoVacio.classList.toggle('d-none', cardsVisibles > 0);

      // Desmarcar radios que quedaron ocultos
      radios.forEach(r => {
        if (r.checked && r.closest('.oferta-fila').style.display === 'none') r.checked = false;
      });

      if (!cardsVisibles) {
        textoTurno.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>' + sinOfertaMsg;
      }

      // Los radios ocultos recién desmarcados dejan de ser la selección:
      // sin esto el resumen y el botón "Reservar" seguirían apuntando a un
      // horario que el estudiante ya no ve en pantalla.
      actualizarResumen();
    }

    // ---- Selección de horario y resumen de confirmación ----
    function actualizarResumen() {
      const marcado = radios.find(r => r.checked);
      if (!marcado) {
        inpOferta.value = '';
        btnConfirmar.disabled = true;
        resumen.innerHTML = '<i class="bi bi-info-circle me-1"></i>Elige un horario con docente en las tarjetas de arriba.';
        return;
      }

      const d = marcado.dataset;
      inpOferta.value = marcado.value;
      btnConfirmar.disabled = false;

      const partes = [d.materia, d.turno + ' (' + d.horario + ')'];
      if (d.docente) partes.push('Prof. ' + d.docente);
      if (d.lugar) partes.push(d.lugar);

      resumen.innerHTML =
        '<i class="bi bi-check-circle-fill text-success me-1"></i><span>' + partes.join(' · ') + '</span>';
    }

    // Si el turno elegido deja un único horario reservable, se preselecciona
    function marcarTurnoSiUnico() {
      if (!selTipo.value || !inpTurno.value) return;
      const candidatos = filasVisibles().filter(f => f.dataset.reservable === '1' && f.dataset.turno === inpTurno.value);
      if (candidatos.length === 1) {
        const radio = candidatos[0].querySelector('.oferta-radio');
        if (radio && !radio.checked) { radio.checked = true; actualizarResumen(); }
      }
    }

    // ---- Registrar interés en un horario sin docente ----
    // Se envía a un endpoint aparte (interes_registrar.php) porque no
    // es una reserva: no crea sesión, deja el interés anotado.
    function registrarInteres(boton) {
      const d = boton.dataset;

      Swal.fire({
        title: 'Pedir ' + d.materia,
        html: 'Todavía <b>no hay docente</b> para el turno de <b>' + d.turno + '</b>.<br>' +
              'Registra tu interés y la administración avisará al equipo docente. ' +
              'Te escribimos en cuanto se asigne uno.',
        input: 'textarea',
        inputPlaceholder: 'Temas que te gustaría repasar (opcional)',
        inputAttributes: { maxlength: 500, rows: 3 },
        showCancelButton: true,
        confirmButtonText: 'Registrar mi interés',
        cancelButtonText: 'Ahora no',
        confirmButtonColor: '#1e40af',
        focusConfirm: false
      }).then((resultado) => {
        if (!resultado.isConfirmed) return;

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/controllers/interes_registrar.php';

        const token = document.querySelector('#formReserva input[name="csrf_token"]');
        if (token) {
          const oculto = document.createElement('input');
          oculto.type = 'hidden';
          oculto.name = 'csrf_token';
          oculto.value = token.value;
          form.appendChild(oculto);
        }

        [['id_oferta', d.idOferta], ['mensaje', resultado.value || '']].forEach(([nombre, valor]) => {
          const oculto = document.createElement('input');
          oculto.type = 'hidden';
          oculto.name = nombre;
          oculto.value = valor;
          form.appendChild(oculto);
        });

        document.body.appendChild(form);
        form.submit();
      });
    }

    document.querySelectorAll('.btn-solicitar').forEach(b => {
      b.addEventListener('click', () => registrarInteres(b));
    });

    // ---- Eventos ----
    selTipo.addEventListener('change', () => { sincronizarSelects(); filtrarHorarios(); });

    if (grupoSistema) {
      grupoSistema.addEventListener('click', (ev) => {
        const boton = ev.target.closest('.btn-opcion');
        if (!boton || boton.disabled) return;
        // Segunda pulsación sobre el botón ya activo = quitar el filtro,
        // que es como se vuelve a ver presencial y virtual.
        const valor = boton.dataset.sistema;
        inpSistema.value = (inpSistema.value === valor) ? '' : valor;
        sincronizarSelects();
        filtrarHorarios();
        marcarTurnoSiUnico();
      });
    }

    if (grupoTurno) {
      grupoTurno.addEventListener('click', (ev) => {
        const boton = ev.target.closest('.btn-turno');
        if (!boton || boton.disabled) return;
        inpTurno.value = boton.dataset.turno;
        sincronizarSelects();
        filtrarHorarios();
        marcarTurnoSiUnico();
      });
    }

    radios.forEach(r => r.addEventListener('change', actualizarResumen));

    // ---- Inicialización (y restauración en POST con errores) ----
    sincronizarSelects();
    filtrarHorarios();
    actualizarResumen();

    // Tras un POST con errores se restaura la selección. El tipo de tutoría
    // no viaja en el POST (el servidor solo valida la oferta), así que se
    // recupera de la fila que volvió marcada. Ojo: el radio lleva el *nombre*
    // del turno para el resumen, el id está en la fila, que es donde lo
    // espera el resto del script (dataset.turno se compara con turnoId).
    const radioPost = radios.find(r => r.checked);
    if (radioPost) {
      const filaPost = radioPost.closest('.oferta-fila');
      if (filaPost) {
        if (filaPost.dataset.nivel) selTipo.value = filaPost.dataset.nivel;
        if (filaPost.dataset.turno) inpTurno.value = filaPost.dataset.turno;
      }
      sincronizarSelects();
      filtrarHorarios();
      actualizarResumen();
    }
  })();
</script>

<style>
  /* ---- Metadatos institucionales bajo el select de carrera ---- */
  .meta-dato {
    display: flex;
    flex-direction: column;
    gap: .15rem;
    height: 100%;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 3px solid #1e40af;
    border-radius: .6rem;
    padding: .5rem .7rem;
  }
  .meta-label {
    font-size: .66rem;
    font-weight: 700;
    letter-spacing: .5px;
    text-transform: uppercase;
    color: #64748b;
  }
  .meta-valor { font-weight: 700; color: #0f172a; font-size: .88rem; }

  /* ---- Grupos de botones (Sistema de Estudio y Turno) ---- */
  .grupo-botones { display: flex; flex-wrap: wrap; gap: .5rem; }
  .btn-opcion {
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    gap: .1rem;
    background: #fff;
    border: 1px solid #cbd5e1;
    border-radius: .6rem;
    padding: .5rem .95rem;
    font-size: .78rem;
    font-weight: 700;
    letter-spacing: .3px;
    color: #334155;
    cursor: pointer;
    transition: border-color .15s ease, background .15s ease, color .15s ease, box-shadow .15s ease;
  }
  .btn-opcion small { font-weight: 500; font-size: .68rem; color: #64748b; letter-spacing: 0; }
  .btn-opcion:hover:not(:disabled) { border-color: #1e40af; background: #f8faff; color: #1e40af; }
  /* Estado activo: azul institucional */
  .btn-opcion.btn-activo {
    border-color: #1e40af;
    background: #1e40af;
    color: #fff;
    box-shadow: 0 6px 16px rgba(30, 64, 175, .28);
  }
  .btn-opcion.btn-activo small { color: rgba(255, 255, 255, .82); }
  .btn-opcion:disabled {
    opacity: .45;
    cursor: not-allowed;
    background: #f1f5f9;
    color: #94a3b8;
  }
  .btn-opcion:disabled small { color: #94a3b8; }

  /* ---- Cards de materia ---- */
  .materia-card .card-body { padding: .9rem; }
  .min-w-0 { min-width: 0; }

  .oferta-fila {
    background: #fff;
    border-color: #e2e8f0;
    transition: border-color .15s ease, background .15s ease, box-shadow .15s ease;
  }
  /* Fila seleccionable (con docente) */
  .oferta-fila:not(.oferta-espera):hover { border-color: #1e40af; background: #f8faff; }
  .oferta-fila:not(.oferta-espera):has(.oferta-radio:checked) {
    border-color: #1e40af;
    background: #eef2ff;
    box-shadow: 0 0 0 1px #1e40af inset;
  }
  /* Fila en espera de docente */
  .oferta-espera { background: #fffbeb; border-color: #fde68a; }
  .oferta-espera-ico {
    width: 28px; height: 28px;
    display: inline-flex; align-items: center; justify-content: center;
    border-radius: 8px;
    background: #fef3c7;
    color: #92400e;
    font-size: .85rem;
    flex-shrink: 0;
  }

  .resumen-seleccion {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-left: 3px solid #1e40af;
    border-radius: .6rem;
    padding: .55rem .7rem;
    font-size: .82rem;
    font-weight: 600;
    color: #475569;
    min-height: 2.5rem;
    display: flex;
    align-items: center;
  }

  .sombra-flotante { box-shadow: 0 14px 34px rgba(15, 23, 42, .12); }
</style>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
