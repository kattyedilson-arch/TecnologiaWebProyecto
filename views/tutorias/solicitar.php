<?php
// =========================================================
// VISTA: SOLICITAR TUTORÍA (views/tutorias/solicitar.php)
// ---------------------------------------------------------
// Página DEDICADA al estudiante para agendar una tutoría a
// partir de los horarios PREDEFINIDOS por el administrador.
// El estudiante NO modifica aulas, días libres ni modalidad:
//  1. Carrera: informativa, con los metadatos MODELO DE ESTUDIO,
//     SISTEMA DE ESTUDIO y TURNO del programa.
//  2. Tipo de tutoría (Pregrado, Posgrado, Invierno, Verano).
//  3. Sistema de estudio: botones PRESENCIAL / HORARIO DE TRABAJO /
//     SEMI PRESENCIAL. El activo es el que declara su carrera.
//  4. Turno: botones generados desde el catálogo 'turnos'. Se
//     deshabilitan los que aún no tienen horario publicado+asignado.
//  5. Horario preestablecido: oferta (turno+tutor+modalidad+aula)
//     publicada por el admin. La materia NO es un campo del formulario:
//     se muestra dentro de cada horario y es la que se registra.
//     La fecha de la sesión se asigna automáticamente (próximo día libre del turno).
// Variables del controlador:
//   $ofertasDisponibles, $errores, $carreraEtiqueta, $carrera,
//   $sistemasEstudio, $sistemaActivo, $turnos
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
$postNivel   = isset($_POST['id_oferta']) ? '' : '';
$idsMateriasConOferta = array_values(array_unique(array_map('intval', array_column($ofertasDisponibles, 'id_materia'))));
?>

<div class="row justify-content-center">
  <div class="col-lg-9 col-xl-8">
    <div class="hero-band p-4 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-calendar-plus-fill"></i>
            <span>Materias Disponibles</span>
          </h3>
          <p class="text-white-50 mb-0">Elige el tipo de tutoría y el turno. La fecha de la sesión se asigna automáticamente al primer día libre del turno elegido.</p>
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
        El <b>turno, modalidad y aula</b> son definidos exclusivamente por la administración.
        Tú eliges el <b>tipo de tutoría</b> y el <b>turno</b>; la <b>materia</b> es la del horario
        que selecciones y la <b>fecha de la sesión se asigna automáticamente</b> el próximo día
        disponible del turno elegido.
      </div>
    </div>

    <?php if (empty($ofertasDisponibles)): ?>
      <div class="card card-custom p-4 p-md-5 text-center">
        <div class="mb-2 fs-1 text-secondary"><i class="bi bi-calendar-x"></i></div>
        <h5 class="fw-bold text-dark mb-2">Aún no hay horarios disponibles</h5>
        <p class="text-muted mb-4 small">
          La administración aún no publica horarios asignados a un docente para las materias de tu carrera
          (<?= htmlspecialchars($carreraEstudiante ?: 'sin asignar') ?>).
          Vuelve más tarde o contacta a la administración.
        </p>
        <div class="d-flex justify-content-center gap-2">
          <a href="<?= $volverUrl ?>" class="btn btn-light px-4 py-2 rounded-3">Volver a mi panel</a>
        </div>
      </div>
    <?php else: ?>

    <div class="card card-custom p-4 p-md-5">
      <form method="POST" autocomplete="off" class="needs-validation" novalidate>
    <?= campoCsrf() ?>
        <div class="row g-3">
          <!-- 1. Carrera (informativa) + metadatos institucionales -->
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
                  <span class="meta-label"><i class="bi bi-building me-1"></i>Sistema de Estudio</span>
                  <span class="meta-valor" id="metaSistema"><?= htmlspecialchars($sistemaActivo) ?></span>
                </div>
              </div>
              <div class="col-6 col-md-4">
                <div class="meta-dato">
                  <span class="meta-label"><i class="bi bi-clock me-1"></i>Turno</span>
                  <span class="meta-valor" id="metaTurno">—</span>
                </div>
              </div>
            </div>
            <div class="form-text" id="textoCarrera">
              <i class="bi bi-info-circle me-1"></i>Programa de tu ficha académica. Los horarios publicados corresponden a esta carrera.
            </div>
          </div>

          <!-- 2. Tipo de tutoría -->
          <div class="col-12">
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

          <!-- 3. Sistema de estudio (botones) -->
          <div class="col-12">
            <span class="form-label fw-semibold text-secondary small text-uppercase d-block">Sistema de Estudio</span>
            <div class="grupo-botones" id="grupoSistema" role="group" aria-label="Sistema de estudio">
              <?php foreach ($sistemasEstudio as $sistema): ?>
                <button type="button"
                        class="btn-opcion<?= $sistema === $sistemaActivo ? ' btn-activo' : '' ?>"
                        data-sistema="<?= htmlspecialchars($sistema) ?>"
                        aria-pressed="<?= $sistema === $sistemaActivo ? 'true' : 'false' ?>">
                  <?= htmlspecialchars($sistema) ?>
                </button>
              <?php endforeach; ?>
            </div>
            <input type="hidden" name="sistema_estudio" id="inpSistema" value="<?= htmlspecialchars($sistemaActivo) ?>">
            <div class="form-text" id="textoSistema">
              <i class="bi bi-info-circle me-1"></i>Sistema declarado por tu carrera (<?= htmlspecialchars($sistemaActivo) ?>).
            </div>
          </div>

          <!-- 4. Turno (botones generados desde el catálogo 'turnos') -->
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
            <div class="form-text" id="textoTurno"><i class="bi bi-info-circle me-1"></i>Elige un turno. Se habilitan solo los que tienen horario publicado y asignado.</div>
            <div class="invalid-feedback" id="errorTurno">Debes seleccionar un turno con horario publicado.</div>
          </div>

          <!-- 5. Horario preestablecido (turno + tutor + modalidad + aula + materia) -->
          <div class="col-12">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Horario Preestablecido *</label>
            <div id="contenedorOfertas" class="d-flex flex-column gap-2">
              <?php foreach ($ofertasDisponibles as $o): ?>
                <label class="oferta-option border rounded-3 p-3 d-flex align-items-start gap-3 mb-1"
                       data-materia="<?= (int)$o['id_materia'] ?>"
                       data-nivel="<?= htmlspecialchars($o['nivel_academico']) ?>"
                       data-turno="<?= (int)$o['id_turno'] ?>"
                       style="cursor:pointer;" title="Elige este horario">
                  <input type="radio" name="id_oferta" value="<?= (int)$o['id_oferta'] ?>"
                         data-materia="<?= (int)$o['id_materia'] ?>"
                         data-nivel="<?= htmlspecialchars($o['nivel_academico']) ?>"
                         data-turno="<?= (int)$o['id_turno'] ?>"
                         class="form-check-input mt-1 oferta-radio"
                         <?= (isset($_POST['id_oferta']) && $_POST['id_oferta'] == $o['id_oferta']) ? 'checked' : '' ?>>
                  <span class="flex-grow-1">
                    <span class="d-flex flex-wrap align-items-center gap-2 flex-grow-1">
                      <span class="badge bg-light text-dark border px-2 py-1"><i class="bi bi-clock me-1"></i><?= htmlspecialchars($o['nombre_turno']) ?> (<?= substr($o['turno_hora_inicio'] ?? $o['hora_inicio'] ?? '', 0, 5) ?> – <?= substr($o['turno_hora_fin'] ?? $o['hora_fin'] ?? '', 0, 5) ?>)</span>
                      <?= modalidadBadge($o['modalidad'] ?? 'presencial') ?>
                      <?php if (!empty($o['lugar_o_enlace'])): ?>
                        <span class="badge bg-light text-muted border px-2 py-1"><i class="bi bi-geo me-1"></i><?= htmlspecialchars($o['lugar_o_enlace']) ?></span>
                      <?php endif; ?>
                    </span>
                    <span class="d-block small text-muted mt-1">
                      <i class="bi bi-mortarboard me-1"></i><?= htmlspecialchars($o['nombre_materia']) ?>
                      <span class="mx-1">•</span><?= htmlspecialchars($o['nivel_academico'] ?? 'pregrado') ?>
                      <span class="mx-1">•</span>Prof. <?= htmlspecialchars($o['tut_nombre'] . ' ' . $o['tut_apellido']) ?>
                    </span>
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
            <div class="form-text" id="textoOfertas"><i class="bi bi-info-circle me-1"></i>Elige uno de los horarios publicados. La <b>materia</b> de tu tutoría es la de la tarjeta que selecciones.</div>
            <div class="invalid-feedback">Debes seleccionar un horario disponible.</div>
          </div>

          <!-- 6. Observaciones / Temas -->
          <div class="col-12">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Temas o Preguntas a Tratar</label>
            <textarea name="observaciones" class="form-control rounded-3" rows="3" maxlength="1000"
                      placeholder="Describe brevemente las dudas o temas puntuales que necesitas repasar con el tutor..."><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
          <a href="<?= $volverUrl ?>" class="btn btn-light px-4 py-2 rounded-3">Cancelar</a>
          <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-send-fill"></i>
            <span>Confirmar y Enviar Solicitud</span>
          </button>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($ofertasDisponibles)): ?>
<script>
  (() => {
    const selTipo    = document.getElementById('selTipo');
    const inpTurno   = document.getElementById('inpTurno');
    const inpSistema = document.getElementById('inpSistema');
    const grupoTurno = document.getElementById('grupoTurno');
    const grupoSistema = document.getElementById('grupoSistema');
    const metaTurno  = document.getElementById('metaTurno');
    const metaSistema = document.getElementById('metaSistema');
    const textoSistema = document.getElementById('textoSistema');
    const textoTipo  = document.getElementById('textoTipo');
    const textoTurno = document.getElementById('textoTurno');
    const textoOfertas = document.getElementById('textoOfertas');
    const contenedor   = document.getElementById('contenedorOfertas');
    const errorTurno   = document.getElementById('errorTurno');
    const radios       = Array.from(document.querySelectorAll('.oferta-radio'));
    const etiquetas    = Array.from(document.querySelectorAll('.oferta-option'));
    const botonesTurno = Array.from(document.querySelectorAll('.btn-turno'));
    const sinOfertaMsg = 'No hay horarios publicados con esta combinación. Contacta a la administración.';

    // ---- Utilidades sobre las ofertas ----
    function ofertasCoincidentes(nivel, turnoId) {
      return radios.filter(r =>
        (!nivel   || r.dataset.nivel === nivel) &&
        (!turnoId || r.dataset.turno === turnoId));
    }

    // Tipos con oferta publicada. La materia ya no es un filtro: todas las
    // ofertas pertenecen a la carrera del estudiante, así que basta el nivel.
    function tiposDisponibles() {
      const set = new Set();
      radios.forEach(r => set.add(r.dataset.nivel));
      return set;
    }

    // Turnos con oferta para el nivel elegido
    function turnosDisponibles(nivel) {
      const set = new Set();
      radios.forEach(r => { if (!nivel || r.dataset.nivel === nivel) set.add(r.dataset.turno); });
      return set;
    }

    // ---- Habilitar/deshabilitar las opciones de Tipo y Turno ----
    function sincronizarSelects() {
      const tiposOk = tiposDisponibles();

      Array.from(selTipo.options).forEach(op => { op.disabled = op.value !== '' && !tiposOk.has(op.value); });
      if (selTipo.value && !tiposOk.has(selTipo.value)) selTipo.value = '';

      const turnosOk = turnosDisponibles(selTipo.value);

      // Los botones de turno se pintan según si tienen horario publicado
      botonesTurno.forEach(b => {
        const id = b.dataset.turno;
        const disponible = turnosOk.has(id);
        b.disabled = !disponible;
        b.classList.toggle('btn-activo', disponible && id === inpTurno.value);
        b.setAttribute('aria-pressed', (disponible && id === inpTurno.value) ? 'true' : 'false');
      });

      if (inpTurno.value && !turnosOk.has(inpTurno.value)) inpTurno.value = '';
      if (!selTipo.value) inpTurno.value = '';

      // El metadato TURNO refleja el botón activo
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
    }

    // ---- Filtrar tarjetas de horarios ----
    function filtrarHorarios() {
      const nivel   = selTipo.value;
      const turnoId = inpTurno.value;
      let visibles = 0;

      etiquetas.forEach(et => {
        const coincide = (!nivel   || et.dataset.nivel === nivel) &&
                         (!turnoId || et.dataset.turno === turnoId);
        et.style.display = coincide ? '' : 'none';
        if (coincide) visibles++;
      });

      // Desmarcar radios ocultos
      radios.forEach(r => {
        if (r.checked && r.closest('.oferta-option').style.display === 'none') r.checked = false;
      });

      const vacio = document.getElementById('avisoVacio');
      if (vacio) vacio.remove();

      if (visibles === 0) {
        const aviso = document.createElement('div');
        aviso.id = 'avisoVacio';
        aviso.className = 'bg-light rounded-3 border text-center text-muted p-3 small';
        aviso.innerHTML = '<i class="bi bi-calendar-x d-block fs-4 mb-1"></i>' + sinOfertaMsg;
        contenedor.appendChild(aviso);
        textoOfertas.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>' + sinOfertaMsg;
      } else {
        textoOfertas.innerHTML = '<i class="bi bi-info-circle me-1"></i>' + visibles + ' horario(s) publicado(s) para esta combinación.';
      }
    }

    // ---- Eventos ----
    selTipo.addEventListener('change', () => { sincronizarSelects(); filtrarHorarios(); });

    // Botones de SISTEMA DE ESTUDIO: actualizan el activo y el metadato
    if (grupoSistema) {
      grupoSistema.addEventListener('click', (ev) => {
        const boton = ev.target.closest('.btn-opcion');
        if (!boton || boton.disabled) return;
        grupoSistema.querySelectorAll('.btn-opcion').forEach(b => {
          const activo = b === boton;
          b.classList.toggle('btn-activo', activo);
          b.setAttribute('aria-pressed', activo ? 'true' : 'false');
        });
        const valor = boton.dataset.sistema;
        if (inpSistema) inpSistema.value = valor;
        if (metaSistema) metaSistema.textContent = valor;
        if (textoSistema) textoSistema.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i>Sistema de estudio: ' + valor + '.';
      });
    }

    // Botones de TURNO
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

    // Si el turno elegido deja un único horario, se preselecciona
    function marcarTurnoSiUnico() {
      if (!selTipo.value || !inpTurno.value) return;
      const existentes = ofertasCoincidentes(selTipo.value, inpTurno.value);
      if (existentes.length === 1 && !existentes[0].checked) {
        existentes[0].checked = true;
        marcacionActiva();
      }
    }

    function marcacionActiva() {
      etiquetas.forEach(et => et.classList.remove('oferta-active'));
      const checked = radios.find(r => r.checked);
      const et = checked ? checked.closest('.oferta-option') : null;
      if (et) et.classList.add('oferta-active');
    }

    radios.forEach(r => r.addEventListener('change', () => { marcacionActiva(); }));

    // ----- Inicialización (y restauración en POST con errores) -----
    sincronizarSelects();
    filtrarHorarios();

    // Restaurar selección si vino de un POST con errores: el radio marcado
    // identifica el nivel y el turno que el estudiante había elegido.
    const radioPost = radios.find(r => r.checked);
    if (radioPost) {
      if (radioPost.dataset.nivel) selTipo.value = radioPost.dataset.nivel;
      if (radioPost.dataset.turno) inpTurno.value = radioPost.dataset.turno;
      sincronizarSelects();
      filtrarHorarios();
      if (radioPost.closest('.oferta-option').style.display !== 'none') {
        marcacionActiva();
      }
    }

    // Validación visual de Bootstrap
    const form = document.querySelector('.needs-validation');
    if (form) {
      form.addEventListener('submit', (e) => {
        const visible = Array.from(document.querySelectorAll('.oferta-radio'))
                            .filter(r => r.closest('.oferta-option').style.display !== 'none');
        const checkedVisible = visible.find(r => r.checked);
        let ok = true;

        // El turno es un input oculto: HTML5 no lo valida, se comprueba aquí
        if (!inpTurno.value) {
          ok = false;
          if (errorTurno) errorTurno.style.display = 'block';
        } else if (errorTurno) {
          errorTurno.style.display = 'none';
        }

        visible.forEach(r => { r.setCustomValidity(''); });
        if (visible.length === 0) {
          ok = false;
          if (document.getElementById('avisoVacio')) document.getElementById('avisoVacio').scrollIntoView({ behavior: 'smooth', block: 'center' });
        } else if (!checkedVisible) {
          visible[0].setCustomValidity('Debes seleccionar un horario disponible.');
          ok = false;
        }

        if (!ok || !form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add('was-validated');
      }, false);
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

  .oferta-option:hover { border-color: #1e40af !important; background: #f8faff; }
  .oferta-option.oferta-active { border-color: #1e40af !important; box-shadow: 0 0 0 1px #1e40af inset; background: #eef2ff; }
</style>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>