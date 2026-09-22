<?php
// =========================================================
// VISTA: SOLICITAR TUTORÍA (views/tutorias/solicitar.php)
// ---------------------------------------------------------
// Formulario que el estudiante completa para agendar una
// sesión: materia, docente tutor, fecha, horas, modalidad y
// lugar/enlace, más observaciones.
// JavaScript incluido:
//   - filtrarTutores(): deshabilita los tutores que no imparten
//     la materia elegida o que no están activos (usa data-materias
//     y data-estado de cada <option>).
//   - Selector de disponibilidad: al elegir el tutor se muestran
//     los DÍAS en que atiende ESA materia (solo bloques del tutor
//     asociados a la materia elegida); al tocar un día el select de
//     FECHA se llena con las próximas 5 ocurrencias de ese día de la
//     semana (ej: los martes próximos). Al tocar un horario se rellenan
//     hora_inicio y hora_fin con el rango del bloque. Si el bloque de
//     hoy ya comenzó, la fecha ofrecida salta a la próxima semana. La
//     fecha se elige del select (no es escribible).
//   - Al elegir modalidad virtual, hace obligatorio y tipo URL
//     el campo lugar_o_enlace.
//   - Verifica que la hora de fin sea posterior a la de inicio.
// Variables del controlador (controllers/tutorias_solicitar.php):
//   $materias, $tutores (con materias_ids y estado), $errores
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
$tituloPagina = 'Solicitar Tutoría Académica - UPDS';
include __DIR__ . '/../layouts/header.php';
$rolAux = $_SESSION['rol'] ?? 'estudiante';
$volverUrl = ($rolAux === 'estudiante') ? '../views/estudiante/panel.php' : 'tutorias_listar.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-9 col-xl-8">
    <div class="hero-band p-4 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-calendar-plus-fill"></i>
            <span>Agendar Sesión de Tutoría</span>
          </h3>
          <p class="text-white-50 mb-0">Completa los datos para enviar tu solicitud de apoyo académico.</p>
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

    <div class="card card-custom p-4 p-md-5">
      <form method="POST" autocomplete="off" class="needs-validation" novalidate>
    <?= campoCsrf() ?>
        <div class="row g-3">
          <!-- Materia -->
          <div class="col-md-12">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Materia Académica *</label>
            <select name="id_materia" id="selMateria" class="form-select rounded-3 py-2" required>
              <option value="" disabled <?= empty($_POST['id_materia']) ? 'selected' : '' ?>>Selecciona la materia que deseas reforzar...</option>
              <?php foreach ($materias as $m): ?>
                <option value="<?= $m['id_materia'] ?>" <?= (isset($_POST['id_materia']) && $_POST['id_materia'] == $m['id_materia']) ? 'selected' : '' ?>>
                  <?= htmlspecialchars($m['nombre_materia']) ?> (<?= htmlspecialchars($m['nombre_carrera'] ?? 'General') ?>)
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (empty($materias)): ?>
              <div class="form-text text-warning"><i class="bi bi-exclamation-triangle me-1"></i>Tu carrera (<?= htmlspecialchars($carreraEstudiante ?: 'sin asignar') ?>) aún no tiene materias registradas. Pide al administrador que las registre para poder agendar tutorías.</div>
            <?php else: ?>
              <div class="form-text"><i class="bi bi-info-circle me-1"></i>Materias de tu carrera (<?= htmlspecialchars($carreraEstudiante ?: 'sin asignar') ?>).</div>
            <?php endif; ?>
            <div class="invalid-feedback">Debes seleccionar una materia.</div>
          </div>

          <!-- Tutor -->
          <div class="col-md-12">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Docente Tutor *</label>
            <select name="id_tutor" id="selTutor" class="form-select rounded-3 py-2" required>
              <option value="" disabled selected>Selecciona al tutor académico...</option>
              <?php foreach ($tutores as $t): ?>
                <option value="<?= $t['id_tutor'] ?>"
                        data-materias="<?= implode(' ', array_map('intval', $t['materias_ids'])) ?>"
                        data-estado="<?= htmlspecialchars($t['estado']) ?>"
                        data-disponibilidad="<?= htmlspecialchars(implode(',', array_map(fn($d) => $d['dia_semana'] . '|' . substr($d['hora_inicio'], 0, 5) . '|' . substr($d['hora_fin'], 0, 5) . '|' . (int)($d['id_materia'] ?? 0), $t['disponibilidad'] ?? [])), ENT_QUOTES) ?>"
                        <?= (isset($_POST['id_tutor']) && $_POST['id_tutor'] == $t['id_tutor']) ? 'selected' : '' ?>>
                  Prof. <?= htmlspecialchars($t['nombre'] . ' ' . $t['apellido']) ?> <?= !empty($t['especialidad']) ? '— ' . htmlspecialchars($t['especialidad']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Debes seleccionar un docente tutor para la materia elegida.</div>
            <div class="form-text" id="textoTutores"><i class="bi bi-info-circle me-1"></i>El docente debe impartir la materia seleccionada.</div>
          </div>

          <!-- Disponibilidad del tutor -->
          <div class="col-md-12">
            <div class="alert alert-info d-none mb-2" id="bloqueHorarios">
              <div class="fw-bold mb-2"><i class="bi bi-calendar-week me-1"></i>Días en que atiende este docente (elige un día y luego la hora):</div>
              <div id="listarDias" class="d-flex flex-wrap gap-2 mb-3"></div>
              <div class="fw-bold mb-2 d-none" id="tituloHoras"><i class="bi bi-clock-history me-1"></i>Horarios disponibles ese día:</div>
              <div id="listarHoras" class="d-flex flex-wrap gap-2"></div>
              <div class="form-text mt-2" id="infoDia"></div>
            </div>
          </div>

          <!-- Fecha -->
          <div class="col-md-4">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Fecha de la Sesión *</label>
            <select name="fecha" id="selFecha" class="form-select rounded-3 py-2 bg-light" required
                    title="Elige entre las próximas fechas en que el docente atiende ese día.">
              <option value="" disabled selected>Primero elige día y horario...</option>
            </select>
            <input type="hidden" id="postFecha" value="<?= htmlspecialchars($_POST['fecha'] ?? '') ?>">
            <div class="form-text">Solo se ofrecen las próximas 5 fechas que caen en el día elegido.</div>
            <div class="invalid-feedback">Elige una fecha (no puede ser en el pasado).</div>
          </div>

          <!-- Hora Inicio -->
          <div class="col-md-4">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Hora Inicio *</label>
            <input type="time" name="hora_inicio" class="form-control rounded-3 py-2 bg-light" value="<?= htmlspecialchars($_POST['hora_inicio'] ?? '') ?>" readonly title="Se fija según la disponibilidad del docente." required>
            <div class="invalid-feedback">Indica la hora de inicio.</div>
          </div>

          <!-- Hora Fin -->
          <div class="col-md-4">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Hora Fin *</label>
            <input type="time" name="hora_fin" class="form-control rounded-3 py-2 bg-light" value="<?= htmlspecialchars($_POST['hora_fin'] ?? '') ?>" readonly title="Se fija según la disponibilidad del docente." required>
            <div class="form-text">Se fija automáticamente según la disponibilidad del docente.</div>
            <div class="invalid-feedback">Indica la hora de fin (debe ser posterior).</div>
          </div>

          <!-- Modalidad -->
          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Modalidad *</label>
            <select name="modalidad" id="selModalidad" class="form-select rounded-3 py-2" required>
              <option value="presencial" <?= (isset($_POST['modalidad']) && $_POST['modalidad'] === 'presencial') ? 'selected' : '' ?>>Presencial (En campus UPDS)</option>
              <option value="virtual" <?= (isset($_POST['modalidad']) && $_POST['modalidad'] === 'virtual') ? 'selected' : '' ?>>Virtual (Meet / Teams / Zoom)</option>
            </select>
          </div>

          <!-- Lugar o Enlace -->
          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Lugar o Enlace</label>
            <input type="text" name="lugar_o_enlace" id="lugarEnlace" class="form-control rounded-3 py-2"
                   placeholder="Ej: Aula 204 o https://meet.google.com/..."
                   value="<?= htmlspecialchars($_POST['lugar_o_enlace'] ?? '') ?>">
            <div class="form-text" id="textoLugar">Indica el aula física o el enlace de la videollamada.</div>
          </div>

          <!-- Observaciones / Temas -->
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
  </div>
</div>

<script>
  // Filtro dinámico: solo mostrar tutores que impartan la materia seleccionada
  const selMateria = document.getElementById('selMateria');
  const selTutor   = document.getElementById('selTutor');
  const txtTutores = document.getElementById('textoTutores');

  function filtrarTutores() {
    const materiaId = selMateria.value;
    if (!materiaId) { return; }

    let visibles = 0;
    Array.from(selTutor.options).forEach(opt => {
      if (opt.value === '') return;
      const materias = (opt.dataset.materias || '').split(' ').filter(Boolean);
      const activo = opt.dataset.estado === 'activo';
      const coincide = materias.includes(materiaId);
      opt.disabled = !(activo && coincide);
      opt.hidden = !(activo && coincide);
      if (activo && coincide) visibles++;
    });

    if (selTutor.selectedOptions[0] && selTutor.selectedOptions[0].disabled) {
      selTutor.value = '';
    }

    txtTutores.innerHTML = visibles === 0
      ? '<i class="bi bi-exclamation-triangle me-1"></i>No hay tutores activos para esta materia aún.'
      : '<i class="bi bi-info-circle me-1"></i>' + visibles + ' tutor(es) activo(s) disponible(s) para esta materia.';
  }

  selMateria.addEventListener('change', onCambioMateria);
  filtrarTutores();

  // ---- Selector de disponibilidad: días y horarios del tutor ----
  const selFecha        = document.querySelector('[name="fecha"]');
  const ini             = document.querySelector('[name="hora_inicio"]');
  const fin             = document.querySelector('[name="hora_fin"]');
  const bloqueHorarios  = document.getElementById('bloqueHorarios');
  const listarDias      = document.getElementById('listarDias');
  const tituloHoras     = document.getElementById('tituloHoras');
  const listarHoras     = document.getElementById('listarHoras');
  const infoDia         = document.getElementById('infoDia');
  const diasSemana      = ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'];

  let seleccionDia    = null; // día de atención elegido por el estudiante (ej: 'Lunes')
  let seleccionBloque = null; // bloque elegido: { inicio, fin }

  // Mapa: id_tutor -> [{dia, inicio, fin, materia}]
  const bloquesPorTutor = {};
  Array.from(selTutor.options).forEach(opt => {
    if (opt.value === '') return;
    bloquesPorTutor[opt.value] = (opt.dataset.disponibilidad || '')
      .split(',').filter(Boolean)
      .map(b => { const [dia, inicio, fin, materia] = b.split('|'); return { dia, inicio, fin, materia }; });
  });

  // Solo se ofrecen los bloques del tutor que imparten LA MATERIA elegida
  function bloquesDeTutor() {
    const materiaId = selMateria.value;
    return (bloquesPorTutor[selTutor.value] || []).filter(b => b.materia === materiaId);
  }

  function limpiarSeleccionHorario() {
    seleccionDia = null;
    seleccionBloque = null;
    ini.value = '';
    fin.value = '';
    selFecha.innerHTML = '<option value="" disabled selected>Primero elige día y horario...</option>';
  }

  // Al cambiar la materia: se limpia el horario elegido y se filtran tutores/bloques
  function onCambioMateria() {
    limpiarSeleccionHorario();
    filtrarTutores();
    renderDias();
  }

  function diaSemanaDe(fechaStr) {
    if (!fechaStr) return null;
    const p = fechaStr.split('-');
    return new Date(+p[0], +p[1] - 1, +p[2]).getDay();
  }

  // Hora actual en formato HH:MM (para comparar con los bloques)
  function horaActual() {
    const h = new Date();
    return String(h.getHours()).padStart(2, '0') + ':' + String(h.getMinutes()).padStart(2, '0');
  }

  // Devuelve la próxima fecha (YYYY-MM-DD) que cae en el día 'dia'.
  // Si ese día es HOY pero la hora de inicio de su bloque ya pasó,
  // se avanza a la próxima semana (7 días después).
  function siguienteFechaDeDia(dia, horaInicio) {
    const obj = diasSemana.indexOf(dia);          // Lunes -> 1 ... Sabado -> 6
    const hoy = new Date();
    let diff = (obj + 7 - hoy.getDay()) % 7;      // 0 = es hoy mismo
    if (diff === 0 && horaInicio && horaInicio <= horaActual()) diff = 7;
    const dt = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() + diff);
    return dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0') + '-' + String(dt.getDate()).padStart(2, '0');
  }

  function hoyISO() {
    const h = new Date();
    return h.getFullYear() + '-' + String(h.getMonth() + 1).padStart(2, '0') + '-' + String(h.getDate()).padStart(2, '0');
  }

  function sumarDias(iso, n) {
    const p = iso.split('-');
    const dt = new Date(+p[0], +p[1] - 1, +p[2] + n);
    return dt.getFullYear() + '-' + String(dt.getMonth() + 1).padStart(2, '0') + '-' + String(dt.getDate()).padStart(2, '0');
  }

  // Ej: '2026-09-29' -> '29/09/2026'
  function formatearFechaCorta(iso) {
    const p = iso.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
  }

  // Última hora de inicio entre los bloques del tutor que caen ese día
  function ultimaHoraDelDia(dia) {
    return bloquesDeTutor().filter(b => b.dia === dia).reduce((max, b) => (b.inicio > max ? b.inicio : max), '');
  }

  // El select de fechas muestra las siguientes 5 ocurrencias del día elegido.
  // La primera se calcula con horaInicio para no ofrecer hoy si su bloque ya pasó.
  function poblarSelectFechas(dia, horaRef, fechaPreseleccionada) {
    const fechas = [];
    let iso = siguienteFechaDeDia(dia, horaRef || null);
    for (let i = 0; i < 5; i++) { fechas.push(iso); iso = sumarDias(iso, 7); }

    selFecha.innerHTML = fechas.map(f =>
      '<option value="' + f + '">' + diasSemana[diaSemanaDe(f)] + ' ' + formatearFechaCorta(f) + '</option>'
    ).join('');

    if (fechaPreseleccionada && fechas.includes(fechaPreseleccionada)) {
      selFecha.value = fechaPreseleccionada;
    }
    // si no hay preselección, queda marcada la primera (próxima ocurrencia válida)
  }

  // Paso 1: mostrar los días (únicos) en que atiende el tutor elegido
  function renderDias() {
    if (selTutor.value === '') {
      bloqueHorarios.classList.add('d-none');
      return;
    }
    bloqueHorarios.classList.remove('d-none');

    const tutoriaDias = [...new Set(bloquesDeTutor().map(b => b.dia))];

    if (tutoriaDias.length === 0) {
      listarDias.innerHTML = '<span class="badge rounded-pill px-3 py-2 border bg-light text-warning">Sin horarios para esta materia aún</span>';
      tituloHoras.classList.add('d-none');
      listarHoras.innerHTML = '';
      seleccionDia = null;
      seleccionBloque = null;
      infoDia.innerHTML = '';
      return;
    }

    listarDias.innerHTML = tutoriaDias.map(d =>
      '<button type="button" class="btn btn-sm rounded-pill px-3 border ' + (seleccionDia === d ? 'btn-primary text-white' : 'btn-light text-dark') + '" data-dia="' + d + '">' +
        '<i class="bi bi-calendar-event me-1"></i>' + d +
      '</button>'
    ).join('');
    renderHoras();
  }

  // Paso 2: mostrar los bloques horarios del día seleccionado
  function renderHoras() {
    tituloHoras.classList.toggle('d-none', !seleccionDia);
    if (!seleccionDia) {
      listarHoras.innerHTML = '';
      actualizarInfoDia();
      return;
    }

    const bloques = bloquesDeTutor().filter(b => b.dia === seleccionDia);
    if (bloques.length === 0) {
      listarHoras.innerHTML = '<span class="badge rounded-pill px-3 py-2 border bg-light text-warning">Sin horarios para ese día aún</span>';
    } else {
      listarHoras.innerHTML = bloques.map(b =>
        '<button type="button" class="btn btn-sm rounded-pill px-3 border ' +
          (seleccionBloque && seleccionBloque.inicio === b.inicio && seleccionBloque.fin === b.fin ? 'btn-success text-white' : 'btn-light text-dark') +
          '" data-inicio="' + b.inicio + '" data-fin="' + b.fin + '">' +
          '<i class="bi bi-clock me-1"></i>' + b.inicio + ' – ' + b.fin +
        '</button>'
      ).join('');
    }
    actualizarInfoDia();
  }

  // Al tocar un día: se ofrecen las próximas 5 fechas que caen ese día
  // (si el día de hoy ya no tiene bloques por comenzar, se salta a la próxima semana).
  listarDias.addEventListener('click', e => {
    const btn = e.target.closest('button[data-dia]');
    if (!btn) return;
    seleccionDia = btn.dataset.dia;
    seleccionBloque = null;
    ini.value = '';
    fin.value = '';
    poblarSelectFechas(seleccionDia, ultimaHoraDelDia(seleccionDia));
    renderDias();
  });

  // Al tocar un horario se rellenan solos: hora_inicio, hora_fin y (si falta) fecha.
  // Si el bloque de hoy ya comenzó, la fecha salta a la siguiente del listado.
  listarHoras.addEventListener('click', e => {
    const btn = e.target.closest('button[data-inicio]');
    if (!btn) return;
    const bloque = { inicio: btn.dataset.inicio, fin: btn.dataset.fin };
    seleccionBloque = bloque;

    if (!selFecha.value) {
      poblarSelectFechas(seleccionDia, ultimaHoraDelDia(seleccionDia));
    } else if (selFecha.value === hoyISO() && bloque.inicio <= horaActual()) {
      selFecha.selectedIndex = Math.min(selFecha.selectedIndex + 1, selFecha.options.length - 1);
    }

    ini.value = bloque.inicio;
    fin.value = bloque.fin;

    renderHoras();
  });

  function actualizarInfoDia() {
    const tutor = selTutor.value;
    const fecha = selFecha.value;
    if (!tutor || !fecha) { infoDia.innerHTML = ''; return; }

    const fechaDia = diasSemana[diaSemanaDe(fecha)];

    if (seleccionDia && seleccionDia !== fechaDia) {
      infoDia.innerHTML = '<i class="bi bi-exclamation-circle me-1 text-warning"></i>La fecha elegida cae <b>' + fechaDia + '</b>, pero seleccionaste <b>' + seleccionDia + '</b>. Al elegir la hora la fecha se ajustará al próximo día de atención.';
    } else if (seleccionDia && seleccionDia === fechaDia && seleccionBloque) {
      infoDia.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i>Horario seleccionado: <b>' + seleccionDia + '</b> de ' + seleccionBloque.inicio + ' a ' + seleccionBloque.fin + '. El horario queda fijado por la disponibilidad del docente.';
    } else if (seleccionDia && seleccionDia === fechaDia) {
      infoDia.innerHTML = '<i class="bi bi-check-circle me-1 text-success"></i>El día elegido (<b>' + seleccionDia + '</b>) coincide con la fecha. Ahora selecciona la hora.';
    } else if (!seleccionDia) {
      if (bloquesDeTutor().some(b => b.dia === fechaDia)) {
        infoDia.innerHTML = '<i class="bi bi-info-circle me-1"></i>El día elegido (<b>' + fechaDia + '</b>) es atendido por el tutor. Selecciona un día en la lista de arriba para ver sus horarios.';
      } else {
        infoDia.innerHTML = '<i class="bi bi-exclamation-circle me-1 text-danger"></i>El día elegido (<b>' + fechaDia + '</b>): este tutor <b>no atiende ese día</b>. Elige un día de la lista.';
      }
    }
  }

  selTutor.addEventListener('change', () => {
    limpiarSeleccionHorario();
    renderDias();
  });
  selFecha.addEventListener('change', actualizarInfoDia);

  // Al cargar (POST con errores) se restaura lo ya elegido,
  // poblando el select de fechas y ajustando la fecha al próximo día
  // si el bloque del día de hoy ya pasó
  if (selTutor.value) {
    const fechaPost = document.getElementById('postFecha').value || selFecha.value;
    const fechaDia = fechaPost ? diasSemana[diaSemanaDe(fechaPost)] : null;
    const diasDisponibles = [...new Set(bloquesDeTutor().map(b => b.dia))];
    if (fechaDia && diasDisponibles.includes(fechaDia)) {
      seleccionDia = fechaDia;
      const bloques = bloquesDeTutor().filter(b => b.dia === seleccionDia);
      const bloqueInicial = ini.value
        ? bloques.find(b => ini.value >= b.inicio && ini.value < b.fin)
        : null;
      seleccionBloque = bloqueInicial || bloques[0] || null;
      poblarSelectFechas(seleccionDia, ultimaHoraDelDia(seleccionDia), fechaPost);
      if (seleccionBloque && selFecha.value === hoyISO() && seleccionBloque.inicio <= horaActual()) {
        selFecha.selectedIndex = Math.min(selFecha.selectedIndex + 1, selFecha.options.length - 1);
      }
    }
    renderDias();
  }

  // Requerir URL válida en modalidad virtual
  const selModalidad = document.getElementById('selModalidad');
  const lugarEnlace  = document.getElementById('lugarEnlace');
  const textoLugar   = document.getElementById('textoLugar');

  selModalidad.addEventListener('change', () => {
    if (selModalidad.value === 'virtual') {
      lugarEnlace.required = true;
      lugarEnlace.type = 'url';
      textoLugar.innerHTML = '<i class="bi bi-info-circle me-1"></i>Obligatorio para tutorías virtuales: pega el enlace de la videoconferencia.';
    } else {
      lugarEnlace.required = false;
      lugarEnlace.type = 'text';
      textoLugar.innerHTML = 'Indica el aula física o el enlace de la videollamada.';
    }
  });

  // Validación visual de Bootstrap
  (() => {
    const form = document.querySelector('.needs-validation');
    if (!form) return;
    form.addEventListener('submit', (e) => {
      const inicio = form.querySelector('[name="hora_inicio"]');
      const fin    = form.querySelector('[name="hora_fin"]');
      if (inicio.value && fin.value && inicio.value >= fin.value) {
        fin.setCustomValidity('La hora de fin debe ser posterior a la de inicio');
      } else {
        fin.setCustomValidity('');
      }
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  })();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>