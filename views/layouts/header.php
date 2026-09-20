<?php
// =========================================================
// VISTA PARCIAL: ENCABEZADO (views/layouts/header.php)
// ---------------------------------------------------------
// Contiene el <head> con todos los estilos (Bootstrap 5.3 +
// Inter + Bootstrap Icons + SweetAlert2) y la navbar superior.
// La navbar se adapta según el rol de la sesión:
//   - administrador : Dashboard, Usuarios, Materias, Carreras,
//                     Tutores, Estudiantes y Tutorías
//   - tutor         : Mi Panel y Mis Horarios y Materias
//   - estudiante    : Mis Tutorías y Solicitar Tutoría
// Además muestra el menú desplegable de perfil (Mi Perfil /
// Cerrar Sesión) y el mensaje flash de la sesión.
//
// Variable opcional del controlador: $tituloPagina
// =========================================================
require_once __DIR__ . '/../../includes/funciones.php';
iniciarSesion();
$BASE = base_url();
$rolSesion = $_SESSION['rol'] ?? '';
$nombreSesion = $_SESSION['nombre'] ?? 'Usuario';
$mensajeFlash = getMensaje();
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
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <!-- SweetAlert2 (solo para confirmaciones de borrado) -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --upds-indigo: #4f46e5;
      --upds-indigo-dark: #3730a3;
      --upds-night: #1e1b4b;
      --upds-ink: #1e293b;
      --upds-muted: #64748b;
      --upds-bg: #f1f5f9;
    }
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      background-color: var(--upds-bg);
      color: var(--upds-ink);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    /* Barra de navegación */
    .navbar-custom {
      background: linear-gradient(120deg, var(--upds-night) 0%, var(--upds-indigo-dark) 45%, var(--upds-indigo) 100%);
      box-shadow: 0 4px 20px rgba(30, 27, 75, 0.25);
    }
    .navbar-custom .navbar-brand {
      display: flex;
      align-items: center;
      gap: .6rem;
      font-weight: 800;
      letter-spacing: .2px;
    }
    .brand-logo {
      width: 38px;
      height: 38px;
      border-radius: 12px;
      background: linear-gradient(135deg, #f59e0b, #f97316);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.15rem;
      box-shadow: 0 4px 10px rgba(0,0,0,0.25);
    }
    /* Tarjetas */
    .card-custom {
      border: none;
      border-radius: 16px;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.06);
      background: #fff;
      transition: transform .15s ease, box-shadow .15s ease;
    }
    .card-custom:hover { box-shadow: 0 8px 24px rgba(15, 23, 42, 0.10); }
    .stat-card:hover { transform: translateY(-3px); }
    /* Iconos de estadísticas */
    .stat-ico {
      width: 48px; height: 48px;
      border-radius: 14px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.35rem;
      flex-shrink: 0;
    }
    .stat-sub { font-size: .78rem; color: var(--upds-muted); border-top: 1px solid #f1f5f9; padding-top: .5rem; }
    .bg-indigo { background: #e0e7ff !important; }
    .text-indigo { color: #4338ca !important; }
    .text-ok { color: #059669 !important; }
    /* Banda hero para cabeceras de página */
    .hero-band {
      background: linear-gradient(120deg, var(--upds-night) 0%, var(--upds-indigo) 100%);
      color: #fff;
      border-radius: 18px;
      box-shadow: 0 10px 30px rgba(79, 70, 229, 0.25);
    }
    /* Avatares */
    .avatar-md {
      width: 40px; height: 40px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 800; font-size: .85rem;
      background: var(--upds-indigo);
      color: #fff;
      flex-shrink: 0;
    }
    .avatar-lg {
      width: 84px; height: 84px;
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 900; font-size: 1.8rem;
      background: linear-gradient(135deg, var(--upds-indigo), var(--upds-indigo-dark));
      color: #fff;
      box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
    }
    /* Badges por rol */
    .badge-rol { font-size: .72rem; letter-spacing: .4px; }
    .badge-admin { background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-tutor { background-color: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
    .badge-estudiante { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    /* Tablas */
    .table > :not(caption) > * > * { padding: .8rem .75rem; }
    .table thead th {
      font-size: .72rem; text-transform: uppercase; letter-spacing: .5px; color: var(--upds-muted);
    }
    /* Botones de acción circulares */
    .btn-icon {
      width: 32px; height: 32px;
      padding: 0;
      display: inline-flex; align-items: center; justify-content: center;
      border-radius: 10px;
    }
    /* Barra de página */
    .page-title { font-weight: 800; color: var(--upds-ink); }
    /* Formularios */
    .form-control:focus, .form-select:focus {
      border-color: var(--upds-indigo);
      box-shadow: 0 0 0 .2rem rgba(79, 70, 229, 0.15);
    }
    .badge-state { font-size: .72rem; font-weight: 600; }
  </style>
</head>
<body>

<?php if (isset($_SESSION['id_usuario'])): ?>
<!-- Navbar principal del sistema -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-custom sticky-top">
  <div class="container">
    <a class="navbar-brand" href="<?= $BASE ?>/controllers/dashboard.php">
      <span class="brand-logo"><i class="bi bi-mortarboard-fill"></i></span>
      <span>UPDS Tutorías</span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <?php if ($rolSesion === 'administrador'): ?>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'dashboard') !== false ? 'active fw-bold' : 'text-white-50' ?> d-flex align-items-center gap-1" href="<?= $BASE ?>/controllers/dashboard.php">
              <i class="bi bi-speedometer2"></i> Dashboard
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'usuarios') !== false ? 'active fw-bold' : 'text-white-50' ?> d-flex align-items-center gap-1" href="<?= $BASE ?>/controllers/usuarios_listar.php">
              <i class="bi bi-people-fill"></i> Usuarios
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'materias') !== false ? 'active fw-bold' : 'text-white-50' ?> d-flex align-items-center gap-1" href="<?= $BASE ?>/controllers/materias_listar.php">
              <i class="bi bi-journal-bookmark-fill"></i> Materias
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'carreras') !== false ? 'active fw-bold' : 'text-white-50' ?> d-flex align-items-center gap-1" href="<?= $BASE ?>/controllers/carreras_listar.php">
              <i class="bi bi-mortarboard"></i> Carreras
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'tutores') !== false ? 'active fw-bold' : 'text-white-50' ?> d-flex align-items-center gap-1" href="<?= $BASE ?>/controllers/tutores_listar.php">
              <i class="bi bi-person-video3"></i> Tutores
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'estudiantes') !== false ? 'active fw-bold' : 'text-white-50' ?> d-flex align-items-center gap-1" href="<?= $BASE ?>/controllers/estudiantes_listar.php">
              <i class="bi bi-mortarboard-fill"></i> Estudiantes
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'tutorias') !== false ? 'active fw-bold' : 'text-white-50' ?> d-flex align-items-center gap-1" href="<?= $BASE ?>/controllers/tutorias_listar.php">
              <i class="bi bi-calendar-check-fill"></i> Tutorías
            </a>
          </li>
        <?php elseif ($rolSesion === 'tutor'): ?>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'tutor/panel') !== false ? 'active fw-bold' : 'text-white-50' ?> d-flex align-items-center gap-1" href="<?= $BASE ?>/views/tutor/panel.php">
              <i class="bi bi-speedometer2"></i> Mi Panel
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'disponibilidad') !== false ? 'active fw-bold' : 'text-white-50' ?> d-flex align-items-center gap-1" href="<?= $BASE ?>/controllers/tutores_disponibilidad.php">
              <i class="bi bi-clock-history"></i> Mis Horarios y Materias
            </a>
          </li>
        <?php elseif ($rolSesion === 'estudiante'): ?>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'estudiante/panel') !== false ? 'active fw-bold' : 'text-white-50' ?> d-flex align-items-center gap-1" href="<?= $BASE ?>/views/estudiante/panel.php">
              <i class="bi bi-calendar2-check"></i> Mis Tutorías
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link <?= strpos($_SERVER['PHP_SELF'], 'solicitar') !== false ? 'active fw-bold' : 'text-white-50' ?> d-flex align-items-center gap-1" href="<?= $BASE ?>/controllers/tutorias_solicitar.php">
              <i class="bi bi-calendar-plus"></i> Solicitar Tutoría
            </a>
          </li>
        <?php endif; ?>
      </ul>

      <!-- Perfil de usuario y menú de salida -->
      <div class="d-flex align-items-center gap-2">
        <div class="dropdown">
          <button class="btn btn-light d-flex align-items-center gap-2 rounded-3 px-2 py-1 dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <span class="avatar-md" style="background:#fff; color:var(--upds-indigo); font-size:.75rem;">
              <?php
                $iniNombre = mb_substr($nombreSesion, 0, 1);
                $segPalabra = explode(' ', $nombreSesion);
                $iniSegundo = isset($segPalabra[1]) ? mb_substr($segPalabra[1], 0, 1) : '';
                echo strtoupper($iniNombre . $iniSegundo);
              ?>
            </span>
            <span class="d-none d-md-inline text-start lh-sm me-1">
              <span class="d-block fw-bold" style="font-size:.82rem;"><?= htmlspecialchars($nombreSesion) ?></span>
              <span class="d-block text-uppercase" style="font-size:.6rem; opacity:.8;"><?= htmlspecialchars($rolSesion) ?></span>
            </span>
          </button>
          <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2" href="<?= $BASE ?>/controllers/perfil.php">
                <i class="bi bi-person-gear text-primary"></i> Mi Perfil
              </a>
            </li>
            <li><hr class="dropdown-divider"></li>
            <li>
              <a class="dropdown-item d-flex align-items-center gap-2 text-danger" href="<?= $BASE ?>/controllers/logout.php">
                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>
<?php endif; ?>

<main class="container py-4 flex-grow-1">

<?php if ($mensajeFlash): ?>
  <?php $tipoIcono = $mensajeFlash['tipo'] === 'success' ? 'check-circle-fill' : ($mensajeFlash['tipo'] === 'danger' ? 'exclamation-triangle-fill' : 'info-circle-fill'); ?>
  <div class="alert alert-<?= htmlspecialchars($mensajeFlash['tipo']) ?> d-flex align-items-center gap-2 py-2 px-3 rounded-3 shadow-sm" data-flash-toast>
    <i class="bi bi-<?= $tipoIcono ?> fs-5 flex-shrink-0"></i>
    <div class="fw-semibold"><?= htmlspecialchars($mensajeFlash['texto']) ?></div>
  </div>
<?php endif; ?>