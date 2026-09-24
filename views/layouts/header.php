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
  <style>
    /* ==================== TEMA PROPIO AZUL/BLANCO ==================== */
    :root {
      --cn-blue: #1e40af;
      --cn-blue-dark: #1e3a8a;
      --cn-blue-soft: #dbeafe;
      --cn-night: #172554;
      --cn-celeste: #3b82f6;
      --cn-celeste-soft: #eff6ff;
      --cn-slate: #64748b;
      --cn-ink: #0f172a;
      --cn-bg: #f8fafc;
      --cn-sidebar: 264px;
      --cn-sidebar-collapsed: 84px;
    }
    * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    body {
      background: var(--cn-bg);
      color: var(--cn-ink);
      min-height: 100vh;
      overflow-x: hidden;
    }
    ::-webkit-scrollbar { width: 8px; height: 8px; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; }
    ::-webkit-scrollbar-track { background: transparent; }

    /* ---------- SIDEBAR ---------- */
    .app-sidebar {
      position: fixed; top: 0; left: 0; bottom: 0;
      width: var(--cn-sidebar);
      background: linear-gradient(180deg, #172554 0%, #1e3a8a 60%, #0f172a 100%);
      z-index: 1050;
      display: flex; flex-direction: column;
      transition: transform .3s ease, width .3s ease;
    }
    .brand-side {
      display: flex; align-items: center; gap: .75rem;
      padding: 1.25rem 1.25rem 1rem;
      border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .brand-logo {
      width: 42px; height: 42px; border-radius: 13px; flex-shrink: 0;
      background: #fff;
      padding: 3px;
      box-shadow: 0 6px 14px rgba(30, 64, 175, .45);
    }
    .brand-logo img { width: 100%; height: 100%; object-fit: contain; border-radius: inherit; }
    .brand-txt b { display: block; color: #fff; font-weight: 800; font-size: .98rem; letter-spacing: -.2px; line-height: 1.1; }
    .brand-txt span { font-size: .66rem; color: #a9b9de; text-transform: uppercase; letter-spacing: .6px; font-weight: 600; }
    .sidebar-nav { flex: 1; overflow-y: auto; padding: .9rem .8rem; }
    .nav-label {
      font-size: .63rem; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
      color: #64748b; padding: .6rem .9rem .35rem;
    }
    .sidebar-nav a {
      display: flex; align-items: center; gap: .8rem;
      padding: .68rem .85rem; margin-bottom: .15rem;
      border-radius: 12px; color: #c7d2fe; font-size: .88rem; font-weight: 600;
      text-decoration: none; transition: all .15s; white-space: nowrap;
    }
    .sidebar-nav a i { font-size: 1.05rem; width: 22px; text-align: center; flex-shrink: 0; }
    .sidebar-nav a:hover { background: rgba(72,164,224,.18); color: #fff; }
    .sidebar-nav a.active { background: linear-gradient(90deg, #1e40af, #2563eb); color: #fff; box-shadow: 0 6px 16px rgba(30, 64, 175,.4); border-left: 3px solid var(--cn-celeste); }
    .sidebar-foot { padding: .9rem 1rem; border-top: 1px solid rgba(255,255,255,.08); }
    .sidebar-foot a { display: flex; align-items: center; gap: .7rem; color: #a9b9de; font-size: .8rem; font-weight: 600; text-decoration: none; padding: .4rem .4rem; border-radius: 10px; }
    .sidebar-foot a:hover { color: #fff; background: rgba(255,255,255,.06); }

    /* ---------- SIDEBAR COLAPSADO (iconos solos + menú flotante al hover) ---------- */
    .app-sidebar.collapsed { width: var(--cn-sidebar-collapsed); }
    .app-sidebar.collapsed .brand-side { justify-content: center; padding: 1.15rem .5rem .9rem; }
    .app-sidebar.collapsed .brand-logo { width: 40px; height: 40px; font-size: 1.2rem; }
    .app-sidebar.collapsed .brand-txt,
    .app-sidebar.collapsed .nav-label { display: none; }
    .app-sidebar.collapsed .sidebar-nav { padding: .9rem .65rem; overflow: visible; }
    .app-sidebar.collapsed .sidebar-nav a,
    .app-sidebar.collapsed .sidebar-foot a { justify-content: center; gap: 0; padding: .72rem .5rem; position: relative; }
    .app-sidebar.collapsed .sidebar-nav a i,
    .app-sidebar.collapsed .sidebar-foot a i { margin: 0; width: auto; }
    .app-sidebar.collapsed .sidebar-foot { padding: .8rem .75rem; }
    .app-sidebar.collapsed .sidebar-foot a { font-size: 1rem; }

    /* Panel flotante que aparece al posar el mouse sobre un icono */
    .app-sidebar.collapsed .sidebar-nav a span,
    .app-sidebar.collapsed .sidebar-foot a span {
      position: absolute;
      left: calc(100% + 12px);
      top: 50%;
      transform: translateY(-50%) translateX(-6px);
      background: #fff;
      color: #16255c;
      font-size: .8rem;
      font-weight: 700;
      letter-spacing: -.1px;
      white-space: nowrap;
      padding: .45rem .85rem;
      border-radius: 10px;
      box-shadow: 0 10px 28px rgba(15,23,42,.22);
      border: 1px solid #e8edf5;
      opacity: 0;
      visibility: hidden;
      transition: opacity .16s ease, transform .16s ease;
      z-index: 20;
      pointer-events: none;
    }
    .app-sidebar.collapsed .sidebar-nav a span::before,
    .app-sidebar.collapsed .sidebar-foot a span::before {
      content: "";
      position: absolute;
      left: -5px; top: 50%;
      transform: translateY(-50%) rotate(45deg);
      width: 9px; height: 9px;
      background: #fff;
      border-left: 1px solid #e8edf5;
      border-bottom: 1px solid #e8edf5;
    }
    .app-sidebar.collapsed .sidebar-nav a:hover span,
    .app-sidebar.collapsed .sidebar-foot a:hover span {
      opacity: 1;
      visibility: visible;
      transform: translateY(-50%) translateX(0);
    }

    /* ---------- BARRA SUPERIOR ---------- */
    .app-topbar {
      position: fixed; top: 0; right: 0; left: var(--cn-sidebar);
      height: 66px; background: rgba(255,255,255,.92); backdrop-filter: blur(10px);
      border-bottom: 1px solid #e6eaf2; z-index: 1030;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 1.5rem; transition: left .3s ease;
    }
    .topbar-left { display: flex; align-items: center; gap: .9rem; }
    .btn-toggle { border: 1px solid #e2e8f0; background: #fff; color: var(--cn-ink); width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; cursor: pointer; }
    .btn-toggle:hover { background: var(--cn-celeste-soft); }
    .page-title { font-size: 1.08rem; font-weight: 800; margin: 0; letter-spacing: -.3px; }
    .topbar-right { display: flex; align-items: center; gap: .8rem; }
    .avatar-md {
      width: 40px; height: 40px; border-radius: 50%; flex-shrink: 0;
      background: linear-gradient(135deg, #1e40af, #3b82f6); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: .85rem;
    }
    .avatar-foto { overflow: hidden; padding: 0; background: #fff; }
    .avatar-foto img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block; }
    .dropdown-menu { border: none; border-radius: 14px; box-shadow: 0 16px 40px rgba(15,23,42,.14); padding: .5rem; }
    .dropdown-item { border-radius: 9px; padding: .55rem .8rem; font-size: .88rem; font-weight: 600; }
    .dropdown-item:hover { background: #f1f5f9; color: var(--cn-blue); }
    .dropdown-divider { margin: .35rem 0; }

    /* ---------- CONTENIDO ---------- */
    .app-main { margin-left: var(--cn-sidebar); transition: margin-left .3s ease; min-height: 100vh; display: flex; flex-direction: column; }
    .app-content { padding: 5.4rem 1.5rem 1.5rem; flex: 1; }
    .app-main.collapsed-sidebar { margin-left: var(--cn-sidebar-collapsed); }
    .app-main.collapsed-sidebar ~ .app-topbar, .app-topbar.collapsed-sidebar { left: var(--cn-sidebar-collapsed); }
    [v-cloak] { display: none !important; }

    /* ---------- COMPONENTES (tema azul/blanco) ---------- */
    .card-custom { border: 1px solid #e8edf5; border-radius: 16px; box-shadow: 0 6px 18px rgba(15,23,42,.05); background: #fff; transition: box-shadow .2s, transform .2s; }
    .card-custom:hover { box-shadow: 0 14px 32px rgba(15,23,42,.09); }
    .stat-card:hover { transform: translateY(-3px); }
    .hero-band {
      background: linear-gradient(120deg, #1e3a8a 0%, #1e40af 55%, #3b82f6 130%);
      color: #fff; border-radius: 18px;
      box-shadow: 0 12px 32px rgba(30,64,175,.28);
    }
    .stat-ico { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; flex-shrink: 0; }
    .bg-indigo { background: #dbeafe !important; }
    .text-indigo { color: #1e40af !important; }
    .text-ok { color: #059669 !important; }
    .avatar-lg { width: 84px; height: 84px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.8rem; background: linear-gradient(135deg, #1e40af, #3b82f6); color: #fff; box-shadow: 0 8px 20px rgba(30, 64, 175,.35); }
    .stat-sub { font-size: .78rem; color: var(--cn-slate); border-top: 1px solid #f1f5f9; padding-top: .5rem; }

    /* Botones primarios azul */
    .btn-primary { background: #1e40af; border-color: #1e40af; font-weight: 600; }
    .btn-primary:hover { background: #1e3a8a; border-color: #1e3a8a; }
    .btn-primary.disabled, .btn-primary:disabled { background: #93c5fd; border-color: #93c5fd; }
    .btn-success { background: #059669; border-color: #059669; font-weight: 600; }
    .btn-info { background: #0ea5e9; border-color: #0ea5e9; color: #fff; font-weight: 600; }
    .btn-outline-primary { color: #1e40af; border-color: #bfdbfe; }
    .btn-outline-primary:hover { background: #1e40af; border-color: #1e40af; }
    .btn-icon { width: 32px; height: 32px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; }

    /* Badges */
    .badge-state { font-size: .72rem; font-weight: 600; }
    .badge-rol { font-size: .72rem; letter-spacing: .4px; }
    .badge-admin { background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-tutor { background-color: #dbeafe; color: #1e3a8a; border: 1px solid #93c5fd; }
    .badge-estudiante { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }

    /* Tablas */
    .table { --bs-table-hover-bg: #f8fafc; }
    .table > :not(caption) > * > * { padding: .8rem .75rem; }
    .table thead th { font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: var(--cn-slate); background: #f8fafc; }
    .table thead { border-bottom: 2px solid #e2e8f0; }

    /* Formularios */
    .form-control, .form-select { border-color: #dbe2ef; border-radius: 10px; }
    .form-control:focus, .form-select:focus { border-color: #1e40af; box-shadow: 0 0 0 .2rem rgba(30, 64, 175,.12); }
    .form-check-input:checked { background-color: #1e40af; border-color: #1e40af; }

    /* Alertas */
    .alert { border-radius: 12px; }

    /* ---------- RESPONSIVE ---------- */
    @media (max-width: 991.98px) {
      .app-sidebar { transform: translateX(-100%); width: var(--cn-sidebar) !important; }
      .app-sidebar.open { transform: translateX(0); box-shadow: 0 0 60px rgba(0,0,0,.35); }
      .app-topbar { left: 0 !important; }
      .app-main { margin-left: 0 !important; }
      .sidebar-backdrop { position: fixed; inset: 0; background: rgba(15,23,42,.5); z-index: 1040; display: none; }
      .sidebar-backdrop.show { display: block; }
    }
  </style>
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
    <?php elseif ($rolSesion === 'tutor'): ?>
      <a class="<?= strpos($act, 'tutor/panel') !== false ? 'active' : '' ?>" href="/views/tutor/panel.php"><i class="bi bi-speedometer2"></i><span>Mi Panel</span></a>
      <a class="<?= strpos($act, 'ofertas') !== false ? 'active' : '' ?>" href="/controllers/ofertas_tutor.php"><i class="bi bi-megaphone"></i><span>Ofertas Disponibles</span></a>
      <a class="<?= strpos($act, 'disponibilidad') !== false ? 'active' : '' ?>" href="/controllers/tutores_disponibilidad.php"><i class="bi bi-clock-history"></i><span>Mis Horarios y Materias</span></a>
      <a class="<?= strpos($act, 'mis_estudiantes') !== false ? 'active' : '' ?>" href="/views/tutor/mis_estudiantes.php"><i class="bi bi-people-fill"></i><span>Mis Estudiantes</span></a>
      <a class="<?= strpos($act, 'perfil') !== false ? 'active' : '' ?>" href="/controllers/perfil.php"><i class="bi bi-person-gear"></i><span>Mi Perfil</span></a>
    <?php elseif ($rolSesion === 'estudiante'): ?>
      <a class="<?= strpos($act, 'estudiante/panel') !== false ? 'active' : '' ?>" href="/views/estudiante/panel.php"><i class="bi bi-speedometer2"></i><span>Mi Panel</span></a>
      <a class="<?= strpos($act, 'solicitar') !== false ? 'active' : '' ?>" href="/controllers/tutorias_solicitar.php"><i class="bi bi-calendar-plus"></i><span>Solicitar Tutoría</span></a>
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
  <div class="alert alert-<?= htmlspecialchars($mensajeFlash['tipo']) ?> d-flex align-items-center gap-2 py-2 px-3 rounded-3 shadow-sm mb-3" data-flash-toast>
    <i class="bi bi-<?= $tipoIcono ?> fs-5 flex-shrink-0"></i>
    <div class="fw-semibold"><?= htmlspecialchars($mensajeFlash['texto']) ?></div>
  </div>
<?php endif; ?>
<?php else: ?>
<main>
<?php endif; ?>