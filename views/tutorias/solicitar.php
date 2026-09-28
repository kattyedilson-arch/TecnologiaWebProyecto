<?php
// =========================================================
// VISTA: SOLICITAR TUTORÍA (views/tutorias/solicitar.php)
// ---------------------------------------------------------
// Página DEDICADA al estudiante para agendar una tutoría a
// partir de los horarios PREDEFINIDOS por el administrador.
// El estudiante NO modifica aulas, días libres ni modalidad:
//  1. Materia académica (de su carrera).
//  2. Tipo de tutoría (Pregrado, Posgrado, Invierno, Verano).
//  3. Horario / Turno (Mañana, Mediodía, Tarde, Noche) — los
//     cuatro turnos fijos SIEMPRE se muestran; se deshabilitan
//     los que aún no tienen horario publicado+asignado.
//  4. Horario preestablecido: oferta (turno+tutor+modalidad+aula)
//     publicada por el admin y asignada a un docente.
//     La fecha de la sesión se asigna automáticamente (próximo día libre del turno).
// Variables del controlador:
//   $materias, $turnos, $ofertasDisponibles, $errores, $carreraEstudiante
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
$postMateria = (int)($_POST['id_materia'] ?? 0);
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
          <p class="text-white-50 mb-0">Elige tu materia y el tipo de tutoría. La fecha de la sesión se asigna automáticamente al primer día libre del turno elegido.</p>
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
        Tú eliges la materia y el tipo de tutoría; la <b>fecha de la sesión se asigna
        automáticamente</b> el próximo día disponible del turno elegido.
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
          <!-- 1. Materia (tarjetas clicables) -->
          <div class="col-12">
            <label class="form-label fw-semibold text-secondary small text-uppercase">1. Materia Disponible *</label>
            <div id="grillaMaterias" class="row g-2">
              <?php foreach ($materias as $m): ?>
                <?php $conOferta = in_array((int)$m['id_materia'], $idsMateriasConOferta, true); ?>
                <div class="col-6 col-md-4">
                  <button type="button"
                          class="materia-card <?= $postMateria === (int)$m['id_materia'] ? 'materia-active' : '' ?> <?= $conOferta ? '' : 'materia-disabled' ?>"
                          data-materia="<?= (int)$m['id_materia'] ?>"
                          aria-disabled="<?= $conOferta ? 'false' : 'true' ?>"
                          title="<?= $conOferta ? 'Haz clic para inscribirte' : 'Sin horarios publicados por la administración' ?>">
                    <span class="materia-icon"><i class="bi bi-journal-bookmark<?= $conOferta ? '' : '-fill' ?>"></i></span>
                    <span class="materia-info">
                      <span class="materia-nombre"><?= htmlspecialchars($m['nombre_materia']) ?></span>
                      <span class="materia-carrera"><?= htmlspecialchars($m['nombre_carrera'] ?? 'General') ?></span>
                    </span>
                    <?php if ($conOferta): ?>
                      <span class="materia-accion"><i class="bi bi-person-plus-fill"></i> Inscribirme</span>
                    <?php else: ?>
                      <span class="materia-accion materia-sin"><i class="bi bi-hourglass-split"></i> Sin horarios</span>
                    <?php endif; ?>
                  </button>
                </div>
              <?php endforeach; ?>
            </div>
            <select name="id_materia" id="selMateria" class="d-none" required>
              <option value=""></option>
              <?php foreach ($materias as $m): ?>
                <option value="<?= $m['id_materia'] ?>" <?= $postMateria === (int)$m['id_materia'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($m['nombre_materia']) ?> (<?= htmlspecialchars($m['nombre_carrera'] ?? 'General') ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text" id="textoMateria"><i class="bi bi-info-circle me-1"></i>Materias de tu carrera (<?= htmlspecialchars($carreraEstudiante ?: 'sin asignar') ?>). Haz clic en una tarjeta para continuar.</div>
            <div class="invalid-feedback">Debes seleccionar una materia.</div>
          </div>

          <!-- 2. Tipo de tutoría -->
          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase" for="selTipo">Tipo de Tutoría *</label>
            <select name="tipo_tutoria" id="selTipo" class="form-select rounded-3 py-2" required>
              <option value="" disabled selected>Selecciona el tipo...</option>
              <?php foreach ($tiposTutoria as $valor => $etiqueta): ?>
                <option value="<?= $valor ?>"><?= htmlspecialchars($etiqueta) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text" id="textoTipo"><i class="bi bi-info-circle me-1"></i>Primero elige la materia para ver los tipos con horarios publicados.</div>
            <div class="invalid-feedback">Debes seleccionar el tipo de tutoría.</div>
          </div>

          <!-- 3. Horario / Turno -->
          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase" for="selTurno">Horario (Turno) *</label>
            <select name="turno" id="selTurno" class="form-select rounded-3 py-2" required>
              <option value="" disabled selected>Selecciona el turno...</option>
              <?php foreach ($turnos as $t): ?>
                <option value="<?= (int)$t['id_turno'] ?>">
                  <?= htmlspecialchars($t['nombre_turno']) ?> (<?= substr($t['hora_inicio'], 0, 5) ?> - <?= substr($t['hora_fin'], 0, 5) ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text" id="textoTurno"><i class="bi bi-info-circle me-1"></i>Los 4 turnos fijos de la UPDS. Se habilitan solo los que tienen horario publicado y asignado.</div>
            <div class="invalid-feedback">Debes seleccionar un turno con horario publicado.</div>
          </div>

          <!-- 4. Horario preestablecido (turno + tutor + modalidad + aula) -->
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
            <div class="form-text" id="textoOfertas"><i class="bi bi-info-circle me-1"></i>Elige uno de los horarios publicados y asignados a un docente.</div>
            <div class="invalid-feedback">Debes seleccionar un horario disponible.</div>
          </div>

          <!-- 5. Observaciones / Temas -->
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
    const selMateria = document.getElementById('selMateria');
    const selTipo    = document.getElementById('selTipo');
    const selTurno   = document.getElementById('selTurno');
    const textoOfertas = document.getElementById('textoOfertas');
    const contenedor   = document.getElementById('contenedorOfertas');
    const radios       = Array.from(document.querySelectorAll('.oferta-radio'));
    const etiquetas    = Array.from(document.querySelectorAll('.oferta-option'));
    const sinOfertaMsg = 'No hay horarios publicados con esta combinación. Contacta a la administración.';

    // ---- Tarjetas de materias clicables ----
    const cardsMateria = Array.from(document.querySelectorAll('.materia-card'));
    const textoMateria = document.getElementById('textoMateria');

    function marcarTarjetaMateria() {
      cardsMateria.forEach(c => c.classList.toggle('materia-active', c.dataset.materia === selMateria.value));
    }

    function seleccionarTarjetaMateria(id) {
      if (selMateria.value === id) return;
      selMateria.value = id;
      marcarTarjetaMateria();
      if (textoMateria && id) {
        textoMateria.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i>Materia seleccionada. Elige el tipo de tutoría y el turno.';
      }
      sincronizarSelects();
      filtrarHorarios();
    }

    cardsMateria.forEach(c => c.addEventListener('click', () => {
      if (c.classList.contains('materia-disabled')) {
        if (textoMateria) textoMateria.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>Esta materia aún no tiene horarios publicados por la administración. Elige otra o vuelve más tarde.';
        return;
      }
      seleccionarTarjetaMateria(c.dataset.materia);
    }));

    // ---- Utilidades sobre las ofertas ----
    function ofertasCoincidentes(materiaId, nivel, turnoId) {
      return radios.filter(r =>
        (!materiaId || r.dataset.materia === materiaId) &&
        (!nivel    || r.dataset.nivel === nivel) &&
        (!turnoId  || r.dataset.turno === turnoId));
    }

    // Tipos con oferta para la materia elegida
    function tiposDisponibles(materiaId) {
      const set = new Set();
      radios.forEach(r => { if (!materiaId || r.dataset.materia === materiaId) set.add(r.dataset.nivel); });
      return set;
    }

    // Turnos con oferta para materia+tipo
    function turnosDisponibles(materiaId, nivel) {
      const set = new Set();
      radios.forEach(r => {
        if ((!materiaId || r.dataset.materia === materiaId) && (!nivel || r.dataset.nivel === nivel)) set.add(r.dataset.turno);
      });
      return set;
    }

    // ---- Habilitar/deshabilitar opciones de Tipo y Turno ----
    function sincronizarSelects() {
      const materiaId = selMateria.value;
      const nivel     = selTipo.value;
      const tiposOk   = tiposDisponibles(materiaId);

      Array.from(selTipo.options).forEach(op => { op.disabled = op.value !== '' && !tiposOk.has(op.value); });
      if (selTipo.value && !tiposOk.has(selTipo.value)) selTipo.value = '';
      if (!materiaId) selTipo.value = '';

      const turnosOk  = turnosDisponibles(materiaId, selTipo.value);
      Array.from(selTurno.options).forEach(op => { op.disabled = op.value !== '' && !turnosOk.has(op.value); });
      if (selTurno.value && !turnosOk.has(selTurno.value)) selTurno.value = '';
      if (!selTipo.value) selTurno.value = '';

      const textoAreaTipo = document.getElementById('textoTipo');
      if (!materiaId) textoAreaTipo.innerHTML = '<i class="bi bi-info-circle me-1"></i>Primero elige la materia para ver los tipos con horarios publicados.';
      else if (tiposOk.size === 0) textoAreaTipo.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>Ningún tipo de tutoría tiene horario publicado para esta materia.';
      else textoAreaTipo.innerHTML = '<i class="bi bi-info-circle me-1"></i>' + tiposOk.size + ' tipo(s) con horarios publicados.';

      const textoAreaTurno = document.getElementById('textoTurno');
      if (!selTipo.value) textoAreaTurno.innerHTML = '<i class="bi bi-info-circle me-1"></i>Primero elige el tipo de tutoría.';
      else if (turnosOk.size === 0) textoAreaTurno.innerHTML = '<i class="bi bi-exclamation-triangle me-1 text-warning"></i>Ningún turno tiene horario publicado para esta combinación.';
      else textoAreaTurno.innerHTML = '<i class="bi bi-info-circle me-1"></i>' + turnosOk.size + ' turno(s) disponibles para esta combinación.';
    }

    // ---- Filtrar tarjetas de horarios ----
    function filtrarHorarios() {
      const materiaId = selMateria.value;
      const nivel     = selTipo.value;
      const turnoId   = selTurno.value;
      let visibles = 0;

      etiquetas.forEach(et => {
        const coincide = (!materiaId || et.dataset.materia === materiaId) &&
                         (!nivel    || et.dataset.nivel === nivel) &&
                         (!turnoId  || et.dataset.turno === turnoId);
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
    selMateria.addEventListener('change', () => { sincronizarSelects(); filtrarHorarios(); });
    selTipo.addEventListener('change',    () => { sincronizarSelects(); filtrarHorarios(); });
    selTurno.addEventListener('change',   () => { syncTurno(); filtrarHorarios(); });

    function syncTurno() {
      const materiaId = selMateria.value;
      const nivel     = selTipo.value;
      const turnoId   = selTurno.value;
      if (!materiaId || !nivel || !turnoId) return;
      const existentes = ofertasCoincidentes(materiaId, nivel, turnoId);
      if (existentes.length === 1) {
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
    marcarTarjetaMateria();

    // Restaurar selección si vino de un POST con errores
    const radioPost = radios.find(r => r.checked);
    if (radioPost) {
      if (radioPost.dataset.nivel) selTipo.value = radioPost.dataset.nivel;
      if (radioPost.dataset.turno) selTurno.value = radioPost.dataset.turno;
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
  .materia-card {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: .5rem;
    text-align: left;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 1rem;
    padding: .9rem 1rem;
    transition: border-color .15s ease, background .15s ease, transform .15s ease, box-shadow .15s ease;
  }
  .materia-card:hover { border-color: #1e40af !important; background: #f8faff; transform: translateY(-1px); }
  .materia-card.materia-active { border-color: #1e40af !important; box-shadow: 0 0 0 1px #1e40af inset; background: #eef2ff; }
  .materia-card.materia-disabled { opacity: .55; cursor: not-allowed; filter: grayscale(.4); }
  .materia-card.materia-disabled:hover { border-color: #e5e7eb; background: #fff; transform: none; }
  .materia-icon { font-size: 1.15rem; color: #1e40af; line-height: 1; }
  .materia-info { display: flex; flex-direction: column; gap: .15rem; min-width: 0; }
  .materia-nombre { font-weight: 600; color: #0f172a; font-size: .9rem; line-height: 1.3; }
  .materia-carrera { font-size: .72rem; color: #64748b; }
  .materia-accion {
    font-size: .72rem; font-weight: 600; color: #1e40af; background: #e0e7ff;
    padding: .25rem .65rem; border-radius: 999px; margin-top: auto;
  }
  .materia-accion.materia-sin { color: #64748b; background: #f1f5f9; }
  .oferta-option:hover { border-color: #1e40af !important; background: #f8faff; }
  .oferta-option.oferta-active { border-color: #1e40af !important; box-shadow: 0 0 0 1px #1e40af inset; background: #eef2ff; }
</style>
<?php endif; ?>

<?php include __DIR__ . '/../layouts/footer.php'; ?>