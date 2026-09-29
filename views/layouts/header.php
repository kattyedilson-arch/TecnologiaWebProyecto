<?php
// =========================================================
// VISTA PARCIAL: LAYOUT PRINCIPAL (views/layouts/header.php)
// ---------------------------------------------------------
// Estructura profesional compartida por TODAS las páginas
// internas: sidebar azul oscuro con menú por rol, barra
// superior blanca con título y avatar del usuario, y área de
// contenido sobre fondo claro. Bootstrap actúa como motor
// interno (grid y utilidades) pero el tema visual es propio:
// colores azul/blanco, sombras suaves y tipografía Inter.
//
// Variable del controlador: $tituloPagina (para el título y
// el <title>). El menú se adapta al rol de la sesión.
// =========================================================
require_once __DIR__ . '/../../includes/funciones.php';
iniciarSesion();
$rolSesion = $_SESSION['rol'] ?? '';
$nombreSesion = $_SESSION['nombre'] ?? 'Usuario';
$fotoSesion = $_SESSION['foto'] ?? '';
$mensajeFlash = getMensaje();
$act = basename($_SERVER['PHP_SELF']);

// Notificaciones de la campanita (contador + últimas 5) solo para sesión iniciada
$notifNoLeidas = 0;
$notifRecientes = [];
if (isset($_SESSION['id_usuario'])) {
    require_once __DIR__ . '/../../config/conexion.php';
    require_once __DIR__ . '/../../models/NotificacionModel.php';
    $notifModel = new NotificacionModel($pdo);
    $notifNoLeidas = $notifModel->contarNoLeidas($_SESSION['id_usuario']);
    $notifRecientes = $notifModel->obtenerPorUsuario($_SESSION['id_usuario'], 5);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $tituloPagina ?? 'Sistema de Tutorías - UPDS' ?></title>
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <!-- Bootstrap 5.3 CSS (motor interno: grid y utilidades) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- SweetAlert2 (confirmaciones de borrado) -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Tema visual UPDS -->
  <link rel="stylesheet" href="/assets/css/upds-theme.css">
</head>
<body>

<?php if (isset($_SESSION['id_usuario'])): ?>
<!-- ============ SIDEBAR ============ -->
<aside class="app-sidebar collapsed" id="appSidebar">
  <div class="brand-side">
    <span class="brand-logo"><img src="/assets/img/upds-logo.png" alt="Logo UPDS"></span>
    <span class="brand-txt"><b>UPDS Tutorías</b><span>Apoyo Académico</span></span>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-label">Menú principal</div>
    <?php if ($rolSesion === 'administrador'): ?>
      <a class="<?= strpos($act, 'dashboard') !== false ? 'active' : '' ?>" href="/controllers/dashboard.php"><i class="bi bi-speedometer2"></i><span>Dashboard</span></a>
      <a class="<?= strpos($act, 'usuarios') !== false ? 'active' : '' ?>" href="/controllers/usuarios_listar.php"><i class="bi bi-people-fill"></i><span>Usuarios</span></a>
      <a class="<?= strpos($act, 'materias') !== false ? 'active' : '' ?>" href="/controllers/materias_listar.php"><i class="bi bi-journal-bookmark-fill"></i><span>Materias</span></a>
      <a class="<?= strpos($act, 'carreras') !== false ? 'active' : '' ?>" href="/controllers/carreras_listar.php"><i class="bi bi-mortarboard"></i><span>Carreras</span></a>
      <a class="<?= strpos($act, 'tutores') !== false ? 'active' : '' ?>" href="/controllers/tutores_listar.php"><i class="bi bi-person-video3"></i><span>Tutores</span></a>
      <a class="<?= strpos($act, 'estudiantes') !== false ? 'active' : '' ?>" href="/controllers/estudiantes_listar.php"><i class="bi bi-mortarboard-fill"></i><span>Estudiantes</span></a>
      <a class="<?= strpos($act, 'ofertas') !== false ? 'active' : '' ?>" href="/controllers/ofertas_listar.php"><i class="bi bi-megaphone"></i><span>Ofertas</span></a>
      <a class="<?= strpos($act, 'tutorias') !== false ? 'active' : '' ?>" href="/controllers/tutorias_listar.php"><i class="bi bi-calendar-check-fill"></i><span>Tutorías</span></a>
      <a class="<?= strpos($act, 'periodos') !== false ? 'active' : '' ?>" href="/controllers/admin_periodos.php"><i class="bi bi-calendar-range"></i><span>Periodos</span></a>
      <a class="<?= strpos($act, 'cohortes') !== false ? 'active' : '' ?>" href="/controllers/admin_cohortes.php"><i class="bi bi-people-fill"></i><span>Cohortes MG</span></a>
      <a class="<?= strpos($act, 'expediente') !== false ? 'active' : '' ?>" href="/controllers/mg_expedientes.php"><i class="bi bi-folder2-open"></i><span>Expedientes MG</span></a>
      <a class="<?= strpos($act, 'importar') !== false ? 'active' : '' ?>" href="/controllers/mg_importar.php"><i class="bi bi-upload"></i><span>Importar padrón</span></a>
      <a class="<?= strpos($act, 'plantillas') !== false ? 'active' : '' ?>" href="/controllers/mg_plantillas.php"><i class="bi bi-journal-text"></i><span>Plantillas</span></a>
      <a class="<?= strpos($act, 'calendario') !== false ? 'active' : '' ?>" href="/controllers/mg_calendario.php"><i class="bi bi-calendar3"></i><span>Calendario MG</span></a>
      <a class="<?= strpos($act, 'parametros_mg') !== false ? 'active' : '' ?>" href="/controllers/parametros_mg.php"><i class="bi bi-sliders"></i><span>Parámetros MG</span></a>
      <a class="<?= strpos($act, 'modalidades') !== false ? 'active' : '' ?>" href="/controllers/admin_modalidades.php"><i class="bi bi-journal-bookmark-fill"></i><span>Modalidades MG</span></a>
      <a class="<?= strpos($act, 'reportes') !== false ? 'active' : '' ?>" href="/controllers/reportes_mg.php"><i class="bi bi-bar-chart-line-fill"></i><span>Reportes MG</span></a>
    <?php elseif ($rolSesion === 'coordinador_mg' || $rolSesion === 'auxiliar_mg'): ?>
      <a class="<?= strpos($act, 'admin_modalidades') !== false ? 'active' : '' ?>" href="/controllers/admin_modalidades.php"><i class="bi bi-journal-bookmark-fill"></i><span>Catálogo MG</span></a>
      <a class="<?= strpos($act, 'mg_declaraciones') !== false ? 'active' : '' ?>" href="/controllers/mg_declaraciones.php"><i class="bi bi-file-earmark-text-fill"></i><span>Declaraciones MG</span></a>
      <a class="<?= strpos($act, 'mg_jurados') !== false || strpos($act, 'mg_tribunal') !== false ? 'active' : '' ?>" href="/controllers/mg_jurados.php"><i class="bi bi-people-fill"></i><span>Tribunales y Avales</span></a>
      <?php if ($rolSesion === 'coordinador_mg'): ?>
        <a class="<?= strpos($act, 'cohortes') !== false ? 'active' : '' ?>" href="/controllers/admin_cohortes.php"><i class="bi bi-people-fill"></i><span>Cohortes MG</span></a>
        <a class="<?= strpos($act, 'calendario') !== false ? 'active' : '' ?>" href="/controllers/mg_calendario.php"><i class="bi bi-calendar3"></i><span>Calendario MG</span></a>
        <a class="<?= strpos($act, 'periodos') !== false ? 'active' : '' ?>" href="/controllers/admin_periodos.php"><i class="bi bi-calendar-range"></i><span>Periodos</span></a>
      <?php endif; ?>
      <a class="<?= strpos($act, 'expediente') !== false ? 'active' : '' ?>" href="/controllers/mg_expedientes.php"><i class="bi bi-folder2-open"></i><span>Expedientes MG</span></a>
      <a class="<?= strpos($act, 'importar') !== false ? 'active' : '' ?>" href="/controllers/mg_importar.php"><i class="bi bi-upload"></i><span>Importar padrón</span></a>
      <?php if ($rolSesion === 'coordinador_mg'): ?>
        <a class="<?= strpos($act, 'parametros_mg') !== false ? 'active' : '' ?>" href="/controllers/parametros_mg.php"><i class="bi bi-sliders"></i><span>Parámetros MG</span></a>
        <a class="<?= strpos($act, 'plantillas') !== false ? 'active' : '' ?>" href="/controllers/mg_plantillas.php"><i class="bi bi-journal-text"></i><span>Plantillas</span></a>
      <?php endif; ?>
      <?php if ($rolSesion === 'coordinador_mg' || $rolSesion === 'auxiliar_mg'): ?>
        <a class="<?= strpos($act, 'reportes') !== false ? 'active' : '' ?>" href="/controllers/reportes_mg.php"><i class="bi bi-bar-chart-line-fill"></i><span>Reportes MG</span></a>
      <?php endif; ?>
      <a class="<?= strpos($act, 'notificaciones') !== false ? 'active' : '' ?>" href="/controllers/notificaciones_listar.php"><i class="bi bi-bell"></i><span>Notificaciones</span></a>
      <a class="<?= strpos($act, 'perfil') !== false ? 'active' : '' ?>" href="/controllers/perfil.php"><i class="bi bi-person-gear"></i><span>Mi Perfil</span></a>
    <?php elseif ($rolSesion === 'tutor'): ?>
      <a class="<?= strpos($act, 'tutor/panel') !== false ? 'active' : '' ?>" href="/views/tutor/panel.php"><i class="bi bi-speedometer2"></i><span>Mi Panel</span></a>
      <a class="<?= strpos($act, 'ofertas') !== false ? 'active' : '' ?>" href="/controllers/ofertas_tutor.php"><i class="bi bi-megaphone"></i><span>Ofertas Disponibles</span></a>
      <a class="<?= strpos($act, 'disponibilidad') !== false ? 'active' : '' ?>" href="/controllers/tutores_disponibilidad.php"><i class="bi bi-clock-history"></i><span>Mis Horarios y Materias</span></a>
      <a class="<?= strpos($act, 'mis_estudiantes') !== false ? 'active' : '' ?>" href="/views/tutor/mis_estudiantes.php"><i class="bi bi-people-fill"></i><span>Mis Estudiantes</span></a>
      <a class="<?= strpos($act, 'defensas') !== false ? 'active' : '' ?>" href="/views/tutor/defensas.php"><i class="bi bi-briefcase-fill"></i><span>Mis Defensas (MG)</span></a>
      <a class="<?= strpos($act, 'perfil') !== false ? 'active' : '' ?>" href="/controllers/perfil.php"><i class="bi bi-person-gear"></i><span>Mi Perfil</span></a>
    <?php elseif ($rolSesion === 'estudiante'): ?>
      <a class="<?= strpos($act, 'estudiante/panel') !== false ? 'active' : '' ?>" href="/views/estudiante/panel.php"><i class="bi bi-speedometer2"></i><span>Mi Panel</span></a>
      <a class="<?= strpos($act, 'solicitar') !== false ? 'active' : '' ?>" href="/controllers/tutorias_solicitar.php"><i class="bi bi-calendar-plus"></i><span>Solicitar Tutoría</span></a>
      <a class="<?= strpos($act, 'declaracion') !== false ? 'active' : '' ?>" href="/controllers/estudiante_declaracion.php"><i class="bi bi-mortarboard"></i><span>Modalidad de Grado</span></a>
      <a class="<?= strpos($act, 'perfil') !== false ? 'active' : '' ?>" href="/controllers/perfil.php"><i class="bi bi-person-gear"></i><span>Mi Perfil</span></a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-foot">
    <a href="/controllers/logout.php" style="color:#fca5a5;"><i class="bi bi-box-arrow-right"></i><span>Cerrar Sesión</span></a>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<!-- ============ BARRA SUPERIOR ============ -->
<header class="app-topbar collapsed-sidebar" id="appTopbar">
  <div class="topbar-left">
    <button class="btn-toggle" id="btnSidebarToggle" type="button" aria-label="Alternar menú"><i class="bi bi-list"></i></button>
    <h1 class="page-title"><?= htmlspecialchars($tituloPagina ?? 'Panel de Control') ?></h1>
  </div>
  <div class="topbar-right">
    <!-- CAMPANITA DE NOTIFICACIONES -->
    <div class="dropdown">
      <button class="btn position-relative d-flex align-items-center justify-content-center border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notificaciones" style="width:40px;height:40px;background:transparent;">
        <i class="bi bi-bell fs-5" style="color:#334155;"></i>
        <?php if ($notifNoLeidas > 0): ?>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill" id="notifBadge" style="background:#dc2626;font-size:.6rem;"><?= $notifNoLeidas > 99 ? '99+' : $notifNoLeidas ?></span>
        <?php endif; ?>
      </button>
      <div class="dropdown-menu dropdown-menu-end p-2" style="width:340px;">
        <h6 class="dropdown-header px-2 pt-1 pb-2 fw-bold" style="font-size:.8rem;">Notificaciones</h6>
        <?php if (empty($notifRecientes)): ?>
          <span class="dropdown-item-text text-muted text-center d-block py-3" style="font-size:.85rem;">No tienes notificaciones</span>
        <?php else: ?>
          <?php foreach ($notifRecientes as $notifItem):
            $notifIcono = $notifItem['tipo'] === 'tutoria'   ? 'calendar-check-fill' :
                          ($notifItem['tipo'] === 'modalidad' ? 'file-earmark-text-fill' :
                          ($notifItem['tipo'] === 'acta'      ? 'clipboard-check-fill' : 'info-circle-fill'));
            $notifEnlace = $notifItem['enlace'] ?: '/controllers/notificaciones_listar.php';
          ?>
          <a class="dropdown-item d-flex gap-2 align-items-start rounded-2 py-2" href="/controllers/notificaciones_abrir.php?id=<?= (int)$notifItem['id_notificacion'] ?>" title="<?= htmlspecialchars($notifEnlace) ?>">
            <i class="bi bi-<?= $notifIcono ?> mt-1 <?= $notifItem['leida'] ? 'text-muted' : 'text-primary' ?> flex-shrink-0"></i>
            <span class="d-block overflow-hidden min-w-0 flex-grow-1">
              <span class="d-block small fw-semibold text-truncate"><?= htmlspecialchars($notifItem['titulo']) ?></span>
              <span class="d-block text-muted" style="font-size:.72rem;">
                <?= htmlspecialchars(mb_substr($notifItem['mensaje'] ?? '', 0, 80)) ?><?= mb_strlen($notifItem['mensaje'] ?? '') > 80 ? '…' : '' ?>
              </span>
              <span class="d-block text-muted" style="font-size:.65rem;">
                <?= date('d/m/Y H:i', strtotime($notifItem['fecha_creacion'])) ?>
              </span>
            </span>
            <?php if (!$notifItem['leida']): ?><span class="ms-auto mt-1 badge rounded-circle flex-shrink-0" style="width:8px;height:8px;padding:0;background:#2563eb;"></span><?php endif; ?>
          </a>
          <?php endforeach; ?>
          <hr class="dropdown-divider my-1">
        <?php endif; ?>
        <div class="d-flex justify-content-between align-items-center px-2 py-1 mt-1" id="notifAcciones">
          <a class="small fw-semibold text-primary text-decoration-none" href="/controllers/notificaciones_listar.php">Ver todas</a>
          <?php if ($notifNoLeidas > 0): ?>
          <form method="POST" action="/controllers/notificaciones_estado.php" id="notifFormMarcarTodas" class="m-0">
            <input type="hidden" name="accion" value="todas">
            <?= campoCsrf() ?>
            <button class="btn btn-link small p-0 fw-semibold text-muted text-decoration-none" type="submit">Marcar leídas</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="dropdown">
      <button class="btn d-flex align-items-center gap-2 rounded-3 px-2 py-1 border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="background:transparent;">
        <span class="avatar-md">
          <?php
            $iniNombre = mb_substr($nombreSesion, 0, 1);
            $segPalabra = explode(' ', $nombreSesion);
            $iniSegundo = isset($segPalabra[1]) ? mb_substr($segPalabra[1], 0, 1) : '';
            echo avatarHTML($fotoSesion, strtoupper($iniNombre . $iniSegundo), 'avatar-md');
          ?>
        </span>
        <span class="d-none d-md-inline text-start lh-sm me-1">
          <span class="d-block fw-bold" style="font-size:.82rem;"><?= htmlspecialchars($nombreSesion) ?></span>
          <span class="d-block text-uppercase" style="font-size:.6rem; opacity:.8; color:#1e40af;"><?= htmlspecialchars($rolSesion) ?></span>
        </span>
      </button>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item d-flex align-items-center gap-2" href="/controllers/perfil.php"><i class="bi bi-person-gear text-primary"></i> Mi Perfil</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="/controllers/logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
      </ul>
    </div>
  </div>
</header>

<!-- ============ CONTENIDO ============ -->
<main class="app-main collapsed-sidebar" id="appMain">
  <div class="app-content">
<?php if ($mensajeFlash): ?>
  <?php $tipoIcono = $mensajeFlash['tipo'] === 'success' ? 'check-circle-fill' : ($mensajeFlash['tipo'] === 'danger' ? 'exclamation-triangle-fill' : 'info-circle-fill'); ?>
  <div class="alert alert-<?= htmlspecialchars($mensajeFlash['tipo']) ?> d-flex align-items-start gap-2 py-2 px-3 rounded-3 shadow-sm mb-3" data-flash-toast>
    <i class="bi bi-<?= $tipoIcono ?> fs-5 flex-shrink-0"></i>
    <?php if (!empty($mensajeFlash['textos'])): ?>
    <ul class="fw-semibold mb-0 ps-3">
      <?php foreach ($mensajeFlash['textos'] as $textoFlash): ?>
        <li><?= htmlspecialchars($textoFlash) ?></li>
      <?php endforeach; ?>
    </ul>
    <?php else: ?>
    <div class="fw-semibold"><?= htmlspecialchars($mensajeFlash['texto']) ?></div>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php else: ?>
<main>
<?php endif; ?>