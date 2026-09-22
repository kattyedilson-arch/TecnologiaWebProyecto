<?php
// =========================================================
// VISTA: DASHBOARD DEL ADMINISTRADOR (views/dashboard/index.php)
// ---------------------------------------------------------
// Vista renderizada con VUE 3 (framework frontend) pero SIN
// cambiar la arquitectura MVC: el controlador PHP (controllers/
// dashboard.php) sigue consultando el modelo
// (DashboardModel.php) y entrega los datos a esta vista. La
// vista los recibe como JSON y Vue 3 los vuelve reactivos
// (contadores animados, búsqueda en vivo, transiciones).
// También demuestra el uso de un framework real (Vue) sobre
// la capa de presentación, sin convertirse en un SPA.
//
// Variables esperadas del controlador (controllers/dashboard.php):
//   $resumen, $ultimasTutorias, $topTutores, $materiasTop
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
require_once __DIR__ . '/../../includes/funciones.php';

// Solo el administrador puede ver el panel central
if (($_SESSION['rol'] ?? '') !== 'administrador') {
    if (($_SESSION['rol'] ?? '') === 'tutor') {
        header('Location: /views/tutor/panel.php');
    } elseif (($_SESSION['rol'] ?? '') === 'estudiante') {
        header('Location: /views/estudiante/panel.php');
    } else {
        header('Location: /views/login/login.php');
    }
    exit;
}

// Si se abre la vista directo (sin pasar por controllers/dashboard.php),
// las variables de datos no existen: se envía al controlador.
if (!isset($resumen) || !isset($ultimasTutorias) || !isset($topTutores) || !isset($materiasTop)) {
    header('Location: /controllers/dashboard.php');
    exit;
}

// Promedio por si llega null (no hay evaluaciones aún)
$resumen['promedio_evaluaciones'] = $resumen['promedio_evaluaciones'] ?? 0;

$tituloPagina = 'Dashboard Administrativo - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';
?>

<style>
  [v-cloak] { display: none !important; }
  .listado-tutorias-enter-active, .listado-tutorias-leave-active { transition: all .25s ease; }
  .listado-tutorias-enter-from, .listado-tutorias-leave-to { opacity: 0; transform: translateX(-8px); }
</style>

<!-- =========================================================
     Datos entregados por PHP -> JS para Vue 3
     (JSON seguro: sin etiquetas HTML, sin caracteres raros)
     ========================================================= -->
<script>
  window.__DASHBOARD_DATA__ = <?= json_encode([
      'nombreAdmin'      => $_SESSION['nombre'] ?? 'Administrador',
      'resumen'          => $resumen,
      'ultimasTutorias'  => $ultimasTutorias,
      'topTutores'       => $topTutores,
      'materiasTop'      => $materiasTop,
      'tokenUrl'         => tokenCsrfUrl(),
  ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>

<div id="app-dashboard" v-cloak>
  <!-- Banda de bienvenida -->
  <div class="hero-band p-4 p-md-5 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
      <div>
        <span class="badge rounded-pill px-3 py-1 mb-2" style="background: rgba(255,255,255,0.15); color:#fff;">Panel de Control</span>
        <h2 class="fw-bold text-white mb-1">¡Bienvenido de nuevo, {{ nombreAdmin }}! 👋</h2>
        <p class="text-white-50 mb-0">Visión general del estado académico y operativo del sistema de tutorías.</p>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a href="/controllers/tutorias_listar.php" class="btn btn-light fw-semibold d-flex align-items-center gap-2 shadow-sm">
          <i class="bi bi-calendar-week"></i> Ver Tutorías
        </a>
        <a href="/controllers/usuarios_listar.php" class="btn btn-primary fw-bold d-flex align-items-center gap-2 shadow-sm" style="border:1px solid rgba(255,255,255,.5);">
          <i class="bi bi-people-fill"></i> Gestionar Usuarios
        </a>
      </div>
    </div>
  </div>

  <!-- Métricas principales (contadores animados con Vue) -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card card-custom stat-card p-3 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-ico bg-primary bg-opacity-10 text-primary"><i class="bi bi-people-fill"></i></div>
          <div>
            <h4 class="fw-bold mb-0 text-dark">{{ contador('total_usuarios') }}</h4>
            <small class="text-muted">Usuarios registrados</small>
          </div>
        </div>
        <div class="stat-sub mt-2"><i class="bi bi-person-check me-1"></i>{{ resumen.usuarios_activos }} activos</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-custom stat-card p-3 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-ico bg-indigo text-indigo"><i class="bi bi-person-video3"></i></div>
          <div>
            <h4 class="fw-bold mb-0 text-dark">{{ contador('total_tutores') }}</h4>
            <small class="text-muted">Docentes tutores</small>
          </div>
        </div>
        <div class="stat-sub mt-2"><i class="bi bi-book me-1"></i>{{ resumen.total_materias }} materias</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-custom stat-card p-3 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-ico text-ok"><i class="bi bi-mortarboard"></i></div>
          <div>
            <h4 class="fw-bold mb-0 text-dark">{{ contador('total_estudiantes') }}</h4>
            <small class="text-muted">Estudiantes activos</small>
          </div>
        </div>
        <div class="stat-sub mt-2"><i class="bi bi-buildings me-1"></i>{{ resumen.total_carreras }} carreras</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-custom stat-card p-3 h-100">
        <div class="d-flex align-items-center gap-3">
          <div class="stat-ico bg-warning bg-opacity-10 text-warning"><i class="bi bi-award"></i></div>
          <div>
            <h4 class="fw-bold mb-0 text-dark">{{ promedioFmt }}</h4>
            <small class="text-muted">Prom. calificaciones</small>
          </div>
        </div>
        <div class="stat-sub mt-2"><i class="bi bi-chat-square-text me-1"></i>{{ resumen.total_evaluaciones }} evaluaciones</div>
      </div>
    </div>
  </div>

  <!-- Estado de tutorías -->
  <div class="card card-custom shadow-sm p-4 mb-4">
    <h6 class="fw-bold mb-3 text-uppercase small text-secondary d-flex align-items-center gap-2">
      <i class="bi bi-graph-up text-primary"></i> Estado del Ciclo de Tutorías
    </h6>
    <div class="row g-3">
      <div class="col-6 col-md-2">
        <div class="text-center p-3 rounded-3 bg-primary bg-opacity-10">
          <h4 class="fw-bold text-primary mb-0">{{ resumen.total_tutorias }}</h4>
          <small class="text-muted">Totales</small>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="text-center p-3 rounded-3 bg-warning bg-opacity-25">
          <h4 class="fw-bold text-warning mb-0">{{ resumen.pendientes }}</h4>
          <small class="text-muted">Pendientes</small>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="text-center p-3 rounded-3 bg-info bg-opacity-10">
          <h4 class="fw-bold text-info mb-0">{{ resumen.confirmadas }}</h4>
          <small class="text-muted">Confirmadas</small>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="text-center p-3 rounded-3 bg-success bg-opacity-10">
          <h4 class="fw-bold text-success mb-0">{{ resumen.realizadas }}</h4>
          <small class="text-muted">Realizadas</small>
        </div>
      </div>
      <div class="col-6 col-md-2">
        <div class="text-center p-3 rounded-3 bg-danger bg-opacity-10">
          <h4 class="fw-bold text-danger mb-0">{{ resumen.canceladas }}</h4>
          <small class="text-muted">Canceladas</small>
        </div>
      </div>
    </div>
  </div>

  <!-- Últimas tutorías y top tutores -->
  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card card-custom shadow-sm overflow-hidden h-100">
        <div class="card-header bg-white py-3 border-0 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2">
          <h6 class="fw-bold mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-clock-history text-primary"></i> Últimas Tutorías Solicitadas
          </h6>
          <div class="input-group input-group-sm" style="max-width: 260px;">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
            <input type="text" v-model.trim="busqueda" class="form-control bg-light border-start-0" placeholder="Buscar estudiante, materia o tutor...">
          </div>
        </div>
        <div class="table-responsive">
          <table class="table table-hover align-middle mb-0">
            <thead class="table-light text-muted text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.5px;">
              <tr>
                <th class="ps-4">Estudiante</th>
                <th>Materia</th>
                <th>Docente</th>
                <th>Fecha</th>
                <th>Estado</th>
                <th class="text-end pe-4">Acciones</th>
              </tr>
            </thead>
            <TransitionGroup name="listado-tutorias" tag="tbody">
              <tr v-for="tut in tutoriasFiltradas" :key="tut.id_tutoria">
                <td class="ps-4 fw-medium text-dark">{{ tut.est_nombre }} {{ tut.est_apellido }}</td>
                <td class="text-primary fw-semibold small">{{ tut.nombre_materia }}</td>
                <td class="small">Prof. {{ tut.tut_nombre }} {{ tut.tut_apellido }}</td>
                <td class="small">{{ formatearFecha(tut.fecha) }} <span class="text-muted">({{ horaCorta(tut.hora_inicio) }})</span></td>
                <td class="text-end pe-4">
                  <span class="badge rounded-pill px-3 py-1 text-capitalize" :class="claseEstado(tut.estado)">{{ tut.estado }}</span>
                </td>
                <td class="text-end pe-4">
                  <div class="btn-group" role="group">
                    <a v-if="tut.estado === 'pendiente'" :href="accionUrl(tut, 'confirmada')"
                       class="btn btn-sm btn-outline-success btn-icon" title="Aceptar y confirmar sesión">
                      <i class="bi bi-check-lg"></i>
                    </a>
                    <a v-if="tut.estado === 'confirmada'" :href="accionUrl(tut, 'realizada')"
                       class="btn btn-sm btn-outline-primary btn-icon" title="Marcar como realizada">
                      <i class="bi bi-check2-all"></i>
                    </a>
                    <button v-if="tut.estado !== 'cancelada' && tut.estado !== 'realizada'"
                            type="button" class="btn btn-sm btn-outline-warning btn-icon" title="Cancelar sesión"
                            @click="confirmarEliminacion(accionUrl(tut, 'cancelada'), '¿Deseas cancelar esta tutoría?')">
                      <i class="bi bi-slash-circle"></i>
                    </button>
                  </div>
                </td>
              </tr>
            </TransitionGroup>
          </table>
          <div v-if="!tutoriasFiltradas.length" class="text-center py-5 text-muted">
            <i class="bi bi-calendar-x fs-1 d-block mb-2 text-secondary"></i>
            <span v-if="busqueda">No hay tutorías que coincidan con "{{ busqueda }}".</span>
            <span v-else>Aún no hay tutorías registradas.</span>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card card-custom shadow-sm p-4 mb-4">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
          <i class="bi bi-trophy text-warning"></i> Tutores Mejor Calificados
        </h6>
        <div v-for="tp in topTutores" :key="tp.id_tutor" class="d-flex align-items-center justify-content-between py-2 border-bottom">
          <div>
            <div class="fw-bold text-dark small">Prof. {{ tp.nombre }} {{ tp.apellido }}</div>
            <div class="text-muted" style="font-size: 0.75rem;">
              <i class="bi bi-book me-1"></i>{{ tp.especialidad || 'Docencia' }}
              <span class="ms-1">• {{ tp.total_tutorias }} sesiones</span>
            </div>
          </div>
          <div class="text-end">
            <div class="fw-bold text-warning">{{ Number(tp.promedio).toFixed(2) }}<i class="bi bi-star-fill ms-1 small"></i></div>
            <small class="text-muted">{{ tp.total_evaluaciones }} eval.</small>
          </div>
        </div>
        <p v-if="!topTutores.length" class="text-muted small text-center py-3 mb-0">Aún no hay evaluaciones registradas.</p>
      </div>

      <div class="card card-custom shadow-sm p-4">
        <h6 class="fw-bold mb-3 d-flex align-items-center gap-2">
          <i class="bi bi-journal-bookmark-fill text-primary"></i> Materias Más Tutoradas
        </h6>
        <div v-for="mat in materiasTop" :key="mat.id_materia" class="mb-3">
          <div class="d-flex justify-content-between small mb-1">
            <span class="fw-semibold text-dark">{{ mat.nombre_materia }}</span>
            <span class="text-muted">{{ mat.total_tutorias }} tutorías</span>
          </div>
          <div class="progress" style="height: 8px;">
            <div class="progress-bar rounded-pill" :style="estiloBarra(mat)"></div>
          </div>
        </div>
        <p v-if="!materiasTop.length" class="text-muted small text-center py-3 mb-0">No hay sesiones registradas aún.</p>
      </div>
    </div>
  </div>
</div>

<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script>
  const { createApp, computed, ref } = Vue;

  createApp({
    setup() {
      const data = window.__DASHBOARD_DATA__ || {};

      const resumen = ref(data.resumen || {});
      const topTutores = ref(data.topTutores || []);
      const materiasTop = ref(data.materiasTop || []);
      const nombreAdmin = ref(data.nombreAdmin || 'Administrador');
      const busqueda = ref('');

      // Contador animado para las métricas principales
      const animados = {};
      const contador = (clave) => {
        if (!animados[clave]) {
          const objetivo = Number(resumen.value[clave] ?? 0);
          animados[clave] = ref(0);
          const inicio = performance.now();
          const duracion = 600;
          const paso = (ahora) => {
            const progreso = Math.min((ahora - inicio) / duracion, 1);
            const relajado = 1 - Math.pow(1 - progreso, 3); // ease-out cubic
            animados[clave].value = Math.round(objetivo * relajado);
            if (progreso < 1) { requestAnimationFrame(paso); }
          };
          requestAnimationFrame(paso);
        }
        return animados[clave].value;
      };

      const promedioFmt = computed(() => {
        const p = Number(resumen.value.promedio_evaluaciones ?? 0);
        return p.toFixed(2).replace('.', ',');
      });

      const tutoriasFiltradas = computed(() => {
        const q = busqueda.value.toLowerCase();
        if (!q) return data.ultimasTutorias || [];
        return (data.ultimasTutorias || []).filter((t) => {
          return (t.est_nombre + ' ' + t.est_apellido + ' ' + t.nombre_materia + ' ' + t.tut_nombre + ' ' + t.tut_apellido)
            .toLowerCase().includes(q);
        });
      });

      const claseEstado = (estado) => {
        const mapa = {
          pendiente:  'bg-warning bg-opacity-25 text-warning-emphasis',
          confirmada: 'bg-info bg-opacity-25 text-info-emphasis',
          realizada:  'bg-success bg-opacity-25 text-success-emphasis',
          cancelada:  'bg-danger bg-opacity-25 text-danger-emphasis',
        };
        return mapa[estado] || 'bg-light text-dark';
      };

      const formatearFecha = (fecha) => {
        if (!fecha) return '-';
        const f = new Date(fecha);
        return f.toLocaleDateString('es-BO', { day: '2-digit', month: '2-digit', year: 'numeric' });
      };

      const horaCorta = (hora) => hora ? String(hora).substring(0, 5) : '';

      const accionUrl = (tut, estado) => {
        const base = '/controllers/tutorias_cambiar_estado.php?id=' + tut.id_tutoria + '&estado=' + estado;
        return data.tokenUrl ? base + '&token=' + data.tokenUrl : base;
      };

      const maxMaterias = computed(() => {
        return Math.max(1, ...(materiasTop.value.map((m) => Number(m.total_tutorias)) || [0]));
      });

      const estiloBarra = (mat) => {
        const pct = Math.round((Number(mat.total_tutorias) / maxMaterias.value) * 100);
        return `width: ${pct}%; background: linear-gradient(90deg,#223B87,#48A4E0);`;
      };

      return { resumen, topTutores, materiasTop, nombreAdmin, busqueda, contador, promedioFmt, tutoriasFiltradas, claseEstado, formatearFecha, horaCorta, accionUrl, estiloBarra };
    },
  }).mount('#app-dashboard');
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>