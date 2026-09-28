<?php
// =========================================================
// VISTA: GESTIONAR OFERTAS (views/ofertas/listar.php)
// ---------------------------------------------------------
// Panel del administrador para crear, ver y gestionar ofertas
// de materias con turnos para que los tutores acepten.
// Variables del controlador:
//   $ofertas, $materias, $turnos, $errores,
//   $totalAbiertas, $totalAsignadas, $totalCerradas
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('administrador');
$tituloPagina = 'Gestionar Ofertas - UPDS';
include __DIR__ . '/../layouts/header.php';
?>

<!-- Banda de cabecera -->
<div class="hero-band p-4 mb-4">
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
    <div>
      <div class="d-flex align-items-center gap-2 mb-1">
        <span class="badge text-bg-light text-dark border px-3 py-1" style="font-size:.68rem; letter-spacing:.6px; text-transform:uppercase;">
          <i class="bi bi-megaphone me-1"></i> Administración
        </span>
      </div>
      <h2 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
        <i class="bi bi-calendar-check"></i>
        <span>Ofertas de Materias</span>
      </h2>
      <p class="text-white-50 mb-0">Crea ofertas de materias con turnos para que los tutores las acepten.</p>
    </div>
    <a href="dashboard.php" class="btn btn-light d-flex align-items-center gap-1">
      <i class="bi bi-arrow-left"></i> Volver
    </a>
  </div>
</div>

<?php if (!empty($errores)): ?>
  <div class="alert alert-danger py-2 px-3 rounded-3 shadow-sm mb-4">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Corrige los siguientes errores:</div>
    <ul class="mb-0 ps-3 small">
      <?php foreach ($errores as $e): ?>
        <li><?= htmlspecialchars($e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<!-- Estadísticas -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="card card-custom p-3 text-center">
      <div class="fw-bold fs-3 text-success"><?= $totalAbiertas ?></div>
      <small class="text-muted">Abiertas</small>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card card-custom p-3 text-center">
      <div class="fw-bold fs-3 text-primary"><?= $totalAsignadas ?></div>
      <small class="text-muted">Asignadas</small>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card card-custom p-3 text-center">
      <div class="fw-bold fs-3 text-secondary"><?= $totalCerradas ?></div>
      <small class="text-muted">Cerradas</small>
    </div>
  </div>
</div>

<div class="row g-4">
  <!-- Columna 1: Crear nueva oferta -->
  <div class="col-lg-4">
    <div class="card card-custom p-4 h-100">
      <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
        <span class="stat-ico bg-primary text-primary" style="width:38px; height:38px; font-size:1rem;"><i class="bi bi-plus-circle"></i></span>
        <div class="flex-grow-1">
          <h5 class="fw-bold mb-0">Crear Oferta</h5>
          <small class="text-muted">Publica una nueva oferta de tutoría.</small>
        </div>
      </div>
      <form method="POST" class="needs-validation" novalidate>
        <?= campoCsrf() ?>
        <input type="hidden" name="accion" value="crear_oferta">
        <div class="mb-3">
          <label class="form-label small text-muted fw-semibold">Materia *</label>
          <select name="id_materia" class="form-select form-select-sm" required>
            <option value="" disabled selected>Selecciona materia...</option>
            <?php foreach ($materias as $mat): ?>
              <option value="<?= $mat['id_materia'] ?>"><?= htmlspecialchars($mat['nombre_materia']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small text-muted fw-semibold">Nivel Académico *</label>
          <select name="nivel_academico" id="selNivelOferta" class="form-select form-select-sm" required>
            <option value="" disabled selected>Selecciona nivel...</option>
            <option value="pregrado">Materias Regulares (Curriculares)</option>
            <option value="invierno">Materia de Invierno</option>
            <option value="verano">Materia de Verano</option>
            <option value="otra">Otra (Personalizar)</option>
          </select>
          <div class="mt-2 d-none" id="divNivelOtra">
            <label class="form-label small text-muted fw-semibold">Nombre de la categoría personalizada *</label>
            <input type="text" name="nivel_academico_otra" id="inpNivelOtra" class="form-control form-control-sm"
                   maxlength="80" placeholder="Ej: Diplomado, Taller de Extensión, Tutoría Especial...">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small text-muted fw-semibold">Modalidad *</label>
          <select name="modalidad" id="selModalidadOferta" class="form-select form-select-sm" required>
            <option value="presencial">Presencial (En campus UPDS)</option>
            <option value="virtual">Virtual (Meet / Teams / Zoom)</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small text-muted fw-semibold" id="lblLugarOferta">Aula o Lugar</label>
          <input type="text" name="lugar_o_enlace" id="lugarOferta" class="form-control form-control-sm"
                 placeholder="Ej: Aula 204 (presencial) o https://meet.google.com/... (virtual)">
          <div class="form-text small" id="textoLugarOferta">Aula física para presencial; enlace obligatorio para virtual.</div>
        </div>
        <div class="info-guia mb-3">
          <div class="alert alert-info py-2 px-3 mb-0 small rounded-3">
            <i class="bi bi-info-circle me-1"></i>
            Horarios, modalidad y aula quedan definidos aquí y el estudiante no podrá modificarlos:
            solo elegirá una fecha y uno de estos turnos al solicitar la tutoría.
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small text-muted fw-semibold">Turno *</label>
          <select name="id_turno" class="form-select form-select-sm" required>
            <option value="" disabled selected>Selecciona turno...</option>
            <?php foreach ($turnos as $turno): ?>
              <option value="<?= $turno['id_turno'] ?>"><?= htmlspecialchars($turno['nombre_turno']) ?> (<?= substr($turno['hora_inicio'], 0, 5) ?> - <?= substr($turno['hora_fin'], 0, 5) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary btn-sm w-100">
          <i class="bi bi-plus-lg me-1"></i> Crear Oferta
        </button>
      </form>
    </div>
  </div>

  <!-- Columna 2: Lista de ofertas -->
  <div class="col-lg-8">
    <div class="card card-custom p-4">
      <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
        <span class="stat-ico bg-indigo text-indigo" style="width:38px; height:38px; font-size:1rem;"><i class="bi bi-list-ul"></i></span>
        <div class="flex-grow-1">
          <h5 class="fw-bold mb-0">Ofertas Registradas</h5>
          <small class="text-muted">Todas las ofertas creadas y su estado actual.</small>
        </div>
        <span class="badge text-bg-light border px-3 py-2"><?= count($ofertas) ?> oferta(s)</span>
      </div>

      <!-- Filtros -->
      <div class="d-flex gap-2 mb-3">
        <a href="ofertas_listar.php" class="btn btn-sm <?= empty($filtroEstado) ? 'btn-primary' : 'btn-outline-secondary' ?>">Todas</a>
        <a href="ofertas_listar.php?estado=abierta" class="btn btn-sm <?= ($filtroEstado ?? '') === 'abierta' ? 'btn-success' : 'btn-outline-secondary' ?>">Abiertas</a>
        <a href="ofertas_listar.php?estado=asignada" class="btn btn-sm <?= ($filtroEstado ?? '') === 'asignada' ? 'btn-primary' : 'btn-outline-secondary' ?>">Asignadas</a>
        <a href="ofertas_listar.php?estado=cerrada" class="btn btn-sm <?= ($filtroEstado ?? '') === 'cerrada' ? 'btn-secondary' : 'btn-outline-secondary' ?>">Cerradas</a>
      </div>

      <?php if (!empty($ofertas)): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Materia</th>
                <th>Turno</th>
                <th>Modalidad</th>
                <th>Aula / Enlace</th>
                <th>Estado</th>
                <th>Tutor Asignado</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ofertas as $o): ?>
                <tr>
                  <td class="fw-semibold"><?= htmlspecialchars($o['nombre_materia']) ?></td>
                  <td>
                    <?= htmlspecialchars($o['nombre_turno']) ?>
                    <small class="text-muted d-block">(<?= substr($o['hora_inicio'] ?? '', 0, 5) ?> - <?= substr($o['hora_fin'] ?? '', 0, 5) ?>)</small>
                  </td>
                  <td>
                    <?php if (($o['modalidad'] ?? 'presencial') === 'virtual'): ?>
                      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1">Virtual</span>
                    <?php else: ?>
                      <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2 py-1">Presencial</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($o['lugar_o_enlace'])): ?>
                      <span class="small text-truncate d-block" style="max-width:150px;" title="<?= htmlspecialchars($o['lugar_o_enlace']) ?>">
                        <?= htmlspecialchars($o['lugar_o_enlace']) ?>
                      </span>
                    <?php else: ?>
                      <span class="text-muted small">—</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($o['estado'] === 'abierta'): ?>
                      <span class="badge bg-success">Abierta</span>
                    <?php elseif ($o['estado'] === 'asignada'): ?>
                      <span class="badge bg-primary">Asignada</span>
                    <?php else: ?>
                      <span class="badge bg-secondary">Cerrada</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($o['id_tutor_assigned']): ?>
                      <span class="fw-semibold">Prof. <?= htmlspecialchars($o['tut_nombre'] . ' ' . $o['tut_apellido']) ?></span>
                    <?php else: ?>
                      <span class="text-muted">Sin asignar</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <?php if ($o['estado'] === 'abierta'): ?>
                      <form method="POST" class="d-inline"
                          data-confirm="La oferta de <b><?= htmlspecialchars($o['nombre_materia']) ?></b> se cerrará y ya no podrá ser aceptada."
                          data-confirm-title="¿Cerrar esta oferta?"
                          data-confirm-icon="warning"
                          data-confirm-text="Sí, cerrar"
                          data-confirm-color="#d97706">
                        <?= campoCsrf() ?>
                        <input type="hidden" name="accion" value="cerrar_oferta">
                        <input type="hidden" name="id_oferta" value="<?= $o['id_oferta'] ?>">
                        <button type="submit" class="btn btn-outline-warning btn-sm" title="Cerrar">
                          <i class="bi bi-lock"></i>
                        </button>
                      </form>
                      <form method="POST" class="d-inline"
                            data-confirm="La oferta de <b><?= htmlspecialchars($o['nombre_materia']) ?></b> se eliminará <b>permanentemente</b>."
                            data-confirm-title="¿Eliminar esta oferta?"
                            data-confirm-icon="error"
                            data-confirm-text="Sí, eliminar"
                            data-confirm-color="#dc2626">
                        <?= campoCsrf() ?>
                        <input type="hidden" name="accion" value="eliminar_oferta">
                        <input type="hidden" name="id_oferta" value="<?= $o['id_oferta'] ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar">
                          <i class="bi bi-trash"></i>
                        </button>
                      </form>
                    <?php elseif ($o['estado'] === 'asignada'): ?>
                      <span class="text-muted small">Completada</span>
                    <?php else: ?>
                      <span class="text-muted small">Cerrada</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <div class="p-4 bg-light rounded-3 text-center text-muted">
          <i class="bi bi-calendar-x d-block fs-1 mb-2"></i>
          <p class="mb-0">No hay ofertas registradas. Crea una usando el formulario.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
  (() => {
    document.querySelectorAll('.needs-validation').forEach(form => {
      form.addEventListener('submit', (e) => {
        if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
        form.classList.add('was-validated');
      }, false);
    });

    // Modalidad virtual: exigir URL válida en el lugar/enlace
    const selModalidad = document.getElementById('selModalidadOferta');
    const lugarOferta  = document.getElementById('lugarOferta');
    const textoLugar   = document.getElementById('textoLugarOferta');
    const lblLugar     = document.getElementById('lblLugarOferta');

    if (selModalidad) {
      const actualizar = () => {
        if (selModalidad.value === 'virtual') {
          lugarOferta.type = 'url';
          lugarOferta.placeholder = 'https://meet.google.com/...';
          textoLugar.innerHTML = '<i class="bi bi-exclamation-circle me-1 text-danger"></i>Obligatorio para ofertas virtuales: pega el enlace de la videoconferencia.';
          lblLugar.textContent = 'Enlace de Videoconferencia *';
        } else {
          lugarOferta.type = 'text';
          lugarOferta.placeholder = 'Ej: Aula 204';
          textoLugar.innerHTML = 'Aula física para la modalidad presencial.';
          lblLugar.textContent = 'Aula o Lugar';
        }
      };
      selModalidad.addEventListener('change', actualizar);
      actualizar();
    }

    // Nivel académico: "Otra (Personalizar)" muestra campo de texto libre
    const selNivel = document.getElementById('selNivelOferta');
    const divNivelOtra = document.getElementById('divNivelOtra');
    const inpNivelOtra = document.getElementById('inpNivelOtra');

    if (selNivel) {
      const syncNivel = () => {
        const esOtra = selNivel.value === 'otra';
        divNivelOtra.classList.toggle('d-none', !esOtra);
        inpNivelOtra.required = esOtra;
        if (!esOtra) inpNivelOtra.value = '';
      };
      selNivel.addEventListener('change', syncNivel);
      syncNivel();
    }
  })();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
