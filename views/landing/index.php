<?php
// =========================================================
// VISTA: PÁGINA PRINCIPAL / LANDING (views/landing/index.php)
// ---------------------------------------------------------
// Portada pública inspirada en el diseño institucional UPDS:
// doble barra superior (blanca + azul), hero con llamada a la
// acción, secciones académicas, catálogo de materias (datos
// reales), tutores destacados, "¿Por qué elegir UPDS?" y
// footer institucional con contacto y redes sociales.
// Interactividad con VUE 3 (contadores animados, revelado al
// scroll, menú móvil) sin romper el MVC: PHP provee los datos.
// =========================================================
require_once __DIR__ . '/../../includes/funciones.php';
iniciarSesion();

// Si el usuario ya tiene sesión, va directo a su panel
if (isset($_SESSION['id_usuario'])) {
    if ($_SESSION['rol'] === 'administrador') {
        header('Location: ../../controllers/dashboard.php');
    } elseif ($_SESSION['rol'] === 'tutor') {
        header('Location: ../tutor/panel.php');
    } else {
        header('Location: ../estudiante/panel.php');
    }
    exit;
}

// Datos reales para las secciones (si la BD responde)
$stats = ['usuarios' => 0, 'materias' => 0, 'tutores' => 0, 'tutorias' => 0];
$materiasTop = [];
$tutoresTop = [];
try {
    require_once __DIR__ . '/../../config/conexion.php';
    require_once __DIR__ . '/../../models/DashboardModel.php';
    $dash = new DashboardModel($pdo);
    $r = $dash->obtenerResumenGlobal();
    $stats = [
        'usuarios' => (int)$r['total_usuarios'],
        'materias' => (int)$r['total_materias'],
        'tutores'  => (int)$r['total_tutores'],
        'tutorias' => (int)$r['total_tutorias'],
    ];
    $materiasTop = $dash->obtenerMateriasTop(6);
    $tutoresTop = $dash->obtenerTopTutores(3);
} catch (Throwable $e) {
    error_log('Landing stats: ' . $e->getMessage());
}
$maxMaterias = 1;
foreach ($materiasTop as $m) { $maxMaterias = max($maxMaterias, (int)$m['total_tutorias']); }
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema de Tutorías UPDS - Apoyo Académico</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Encode+Sans+Condensed:wght@400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    [v-cloak] { display: none !important; }
    :root {
      --upds-blue: #1e40af;
      --upds-blue-dark: #1a2c6b;
      --upds-blue-bright: #2f4ba7;
      --upds-celeste: #48A4E0;
      --upds-celeste-dark: #3B8AC0;
      --upds-celeste-light: #87CEEB;
      --upds-bg: #f4f5f7;
      --upds-ink: #1a1a1a;
      --upds-gray: #5f6b85;
    }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--upds-ink);
      background: #fff;
    }
    h1, h2, h3, h4, .font-encode { font-family: 'Encode Sans Condensed', 'Segoe UI', sans-serif; }
    ::selection { background: var(--upds-blue); color: #fff; }

    /* ============ NAVBAR DOBLE (estilo UPDS) ============ */
    .main-header { position: fixed; top: 0; left: 0; width: 100%; z-index: 50; }
    .top-bar { background: #fff; border-bottom: 1px solid #e5e7eb; transition: all .3s ease; overflow: hidden; }
    .top-bar-inner { max-width: 1200px; margin: 0 auto; padding: 0 1.25rem; height: 88px; display: flex; align-items: center; justify-content: space-between; transition: height .3s ease; }
    .top-bar-inner .logo-area { display: flex; align-items: center; gap: .7rem; }
    .brand-logo {
      width: 52px; height: 52px; border-radius: 12px;
      background: #fff;
      padding: 3px;
      box-shadow: 0 6px 16px rgba(34,59,135,.35);
    }
    .brand-logo img { width: 100%; height: 100%; object-fit: contain; border-radius: inherit; }
    .brand-name { font-weight: 800; font-size: 1.1rem; color: var(--upds-blue); letter-spacing: -.3px; line-height: 1.05; }
    .brand-sub { font-size: .66rem; color: var(--upds-gray); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
    .top-nav { display: flex; gap: 1.8rem; align-items: center; }
    .top-nav a { color: #4b5563; font-weight: 700; font-size: .93rem; text-decoration: none; position: relative; transition: color .2s; }
    .top-nav a:hover, .top-nav a.active { color: var(--upds-blue); }
    .top-nav a::after { content: ''; position: absolute; left: 0; bottom: -4px; width: 0; height: 2px; background: var(--upds-blue); transition: width .25s; }
    .top-nav a:hover::after, .top-nav a.active::after { width: 100%; }
    .btn-acceder { background: var(--upds-blue); color: #fff !important; border-radius: 8px; padding: .6rem 1.3rem; font-weight: 700; transition: background .2s, transform .15s; }
    .btn-acceder:hover { background: var(--upds-blue-dark); transform: translateY(-1px); }
    .btn-acceder::after { display: none; }

    .bottom-bar { background: var(--upds-blue); transition: all .3s ease; }
    .bottom-bar-inner { max-width: 1200px; margin: 0 auto; padding: 0 1.25rem; height: 58px; display: flex; align-items: center; justify-content: space-between; }
    .sub-nav { display: flex; gap: 1.6rem; align-items: center; }
    .sub-nav a { color: rgba(255,255,255,.9); font-family: 'Encode Sans Condensed', sans-serif; font-weight: 600; font-size: .95rem; letter-spacing: .4px; text-decoration: none; padding: .3rem 0; border-bottom: 2px solid transparent; transition: all .2s; text-transform: uppercase; }
    .sub-nav a:hover { color: #fff; border-color: var(--upds-celeste); }
    .sub-nav-right a { color: #c9e6f7; font-weight: 700; text-decoration: none; font-size: .9rem; }
    .main-header.scrolled .top-bar-inner { height: 0; padding: 0; overflow: hidden; }
    .main-header.scrolled .top-bar { border-bottom: none; }

    /* Menú hamburguesa móvil */
    .hamburger { display: none; background: none; border: none; font-size: 1.4rem; color: var(--upds-blue); cursor: pointer; }
    .mobile-drawer { position: fixed; top: 88px; left: 0; right: 0; background: #fff; z-index: 40; box-shadow: 0 12px 30px rgba(0,0,0,.12); display: none; flex-direction: column; padding: 1rem 1.25rem; border-bottom: 2px solid var(--upds-blue); }
    .mobile-drawer.open { display: flex; }
    .mobile-drawer a { padding: .8rem 0; color: var(--upds-ink); text-decoration: none; font-weight: 700; border-bottom: 1px solid #f0f1f4; }

    /* ============ HERO ============ */
    .hero {
      margin-top: 146px;
      position: relative;
      min-height: 560px;
      display: flex; align-items: center; justify-content: center; text-align: center;
      background:
        radial-gradient(circle at 85% 20%, rgba(72,164,224,.4), transparent 45%),
        radial-gradient(circle at 10% 80%, rgba(135,206,235,.28), transparent 50%),
        linear-gradient(120deg, #1e3a8a 0%, #1e40af 55%, #2563eb 100%);
      color: #fff;
      overflow: hidden;
    }
    .hero::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        linear-gradient(rgba(255,255,255,.05) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.05) 1px, transparent 1px);
      background-size: 60px 60px;
    }
    .hero-overlay { position: absolute; inset: 0; background: rgba(10,20,50,.35); }
    .hero-content { position: relative; max-width: 820px; padding: 3rem 1.5rem; z-index: 2; animation: heroIn .8s ease both; }
    @keyframes heroIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: none; } }
    .hero-eyebrow {
      display: inline-flex; align-items: center; gap: .5rem;
      background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.22);
      padding: .4rem 1.1rem; border-radius: 999px;
      font-size: .8rem; font-weight: 700; letter-spacing: .6px; text-transform: uppercase;
      margin-bottom: 1.2rem;
    }
    .hero h1 { font-weight: 900; font-size: clamp(2.2rem, 4.6vw, 3.6rem); letter-spacing: -.5px; line-height: 1.1; margin-bottom: 1.1rem; }
    .hero h1 span { color: var(--upds-celeste-light); }
    .hero p { font-size: 1.1rem; color: rgba(255,255,255,.88); max-width: 640px; margin: 0 auto 2rem; line-height: 1.6; }
    .hero-btns { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
    .btn-cta { background: var(--upds-celeste); color: #16255c; border: none; border-radius: 8px; padding: .85rem 1.8rem; font-weight: 800; font-size: 1rem; box-shadow: 0 10px 24px rgba(72,164,224,.35); transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: .5rem; }
    .btn-cta:hover { background: var(--upds-celeste-dark); transform: translateY(-2px); color: #16255c; }
    .btn-outline-light { border: 1px solid rgba(255,255,255,.5); color: #fff; border-radius: 8px; padding: .85rem 1.8rem; font-weight: 700; transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: .5rem; background: transparent; }
    .btn-outline-light:hover { background: rgba(255,255,255,.12); color: #fff; }

    /* ============ FRANJA DE CIFRAS ============ */
    .stats-band { background: #fff; border-bottom: 1px solid #eaecef; }
    .stat-number { font-weight: 900; font-size: 2.4rem; color: var(--upds-blue); letter-spacing: -1px; font-family: 'Encode Sans Condensed', sans-serif; }
    .stat-caption { color: var(--upds-gray); font-weight: 600; font-size: .95rem; }

    /* ============ SECCIONES ============ */
    .section-eyebrow { color: var(--upds-blue); font-weight: 800; font-size: .8rem; letter-spacing: 1.5px; text-transform: uppercase; }
    .section-title { font-weight: 900; font-size: clamp(1.8rem, 3vw, 2.5rem); color: var(--upds-ink); letter-spacing: -.5px; }
    .section-sub { color: var(--upds-gray); max-width: 640px; margin: 0 auto; }

    .card-upds { background: #fff; border: 1px solid #e7e9ef; border-radius: 14px; transition: all .25s; overflow: hidden; }
    .card-upds:hover { transform: translateY(-5px); box-shadow: 0 18px 40px rgba(34,59,135,.12); border-color: #d3d9ea; }
    .academia-top { height: 7px; border-radius: 14px 14px 0 0; background: linear-gradient(90deg, var(--upds-celeste), var(--upds-celeste-dark)); }
    .academia-icon { width: 54px; height: 54px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; background: rgba(34,59,135,.08); color: var(--upds-blue); }

    .servicio-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.35rem; color: #fff; background: linear-gradient(135deg, #2563eb, #1e40af); box-shadow: 0 8px 18px rgba(30,64,175,.3); }

    /* Por qué elegir UPDS */
    .why-check { color: var(--upds-blue); font-size: 1.5rem; margin-top: .15rem; }
    .why-box { background: #fff; border: 1px solid #e7e9ef; border-radius: 16px; box-shadow: 0 20px 45px rgba(34,59,135,.1); }
    .why-box .btn-cta { width: 100%; justify-content: center; }

    /* Tutores + estrellas */
    .avatar-circle { width: 66px; height: 66px; border-radius: 50%; background: linear-gradient(135deg, #2563eb, #1e40af); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 1.3rem; font-family: 'Encode Sans Condensed', sans-serif; margin: 0 auto 1rem; box-shadow: 0 8px 20px rgba(30,64,175,.3); }
    .stars { color: var(--upds-celeste); }

    /* Testimonios */
    .testimonial-card { background: #fff; border: 1px solid #e7e9ef; border-radius: 16px; }
    .testimonial-card blockquote { color: #45537a; font-size: .95rem; }

    /* ============ CTA FINAL ============ */
    .cta-band { background: linear-gradient(120deg, #1e3a8a, #1e40af 60%, #2563eb); position: relative; overflow: hidden; }
    .cta-band::before { content: ''; position: absolute; top: -80px; right: -80px; width: 260px; height: 260px; border-radius: 50%; background: rgba(255,255,255,.06); }

    /* ============ FOOTER ============ */
    .footer-upds { background: linear-gradient(180deg, #16255c, #0f1b46); color: #c3cbe8; }
    .footer-upds .contact-card { background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; transition: all .2s; }
    .footer-upds .contact-card:hover { background: rgba(255,255,255,.09); }
    .social-btn { width: 40px; height: 40px; border-radius: 50%; background: rgba(255,255,255,.1); color: #fff; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: all .2s; }
    .social-btn:hover { transform: translateY(-3px); background: var(--upds-blue-bright); color: #fff; }
    .footer-upds a { color: #c3cbe8; text-decoration: none; transition: color .2s; }
    .footer-upds a:hover { color: #7fb5ff; }
    .footer-divider { height: 1px; background: rgba(255,255,255,.15); }

    /* Revelado al scroll */
    .reveal { opacity: 0; transform: translateY(26px); transition: opacity .7s ease, transform .7s ease; }
    .reveal.visible { opacity: 1; transform: translateY(0); }

    .progress { height: 9px; border-radius: 8px; background: #edf0f7; }
    .progress-bar { border-radius: 8px; background: linear-gradient(90deg, #2563eb, #1e40af); }

    /* Móvil */
    @media (max-width: 991px) {
      .top-nav, .bottom-bar-inner .sub-nav { display: none; }
      .hamburger { display: block; }
      .hero { margin-top: 88px; min-height: 480px; }
    }
  </style>
</head>
<body id="app-landing" v-cloak>

<!-- ============ NAVBAR DOBLE ============ -->
<header class="main-header" :class="{ scrolled: scrolled }">
  <!-- Barra superior blanca -->
  <div class="top-bar">
    <div class="top-bar-inner">
      <a class="logo-area text-decoration-none" href="/">
        <span class="brand-logo"><img src="/assets/img/upds-logo.png" alt="Logo UPDS"></span>
        <span>
          <span class="brand-name d-block">Tutorías UPDS</span>
          <span class="brand-sub">Apoyo Académico</span>
        </span>
      </a>
      <nav class="top-nav">
        <a href="#inicio" class="active">Inicio</a>
        <a href="#materias">Materias</a>
        <a href="#tutores">Tutores</a>
        <a href="#porque">Nosotros</a>
        <a href="#testimonios">Opiniones</a>
      </nav>
      <div class="d-flex align-items-center gap-2">
        <a href="/views/login/login.php" class="btn-acceder"><i class="fa-solid fa-right-to-bracket me-1"></i>Acceder</a>
      </div>
      <button class="hamburger" @click="menuAbierto = !menuAbierto"><i class="fa-solid fa-bars"></i></button>
    </div>
  </div>
  <!-- Barra inferior azul -->
  <div class="bottom-bar">
    <div class="bottom-bar-inner">
      <nav class="sub-nav">
        <a href="#inicio">Inicio</a>
        <a href="#materias">Nuestras Materias</a>
        <a href="#beneficios">Servicios</a>
        <a href="#tutores">Tutores Destacados</a>
        <a href="#porque">¿Por qué elegirnos?</a>
      </nav>
      <div class="sub-nav-right"><a href="/views/login/login.php"><i class="fa-solid fa-angle-right me-1"></i>Empezar ahora</a></div>
    </div>
  </div>
</header>

<!-- Dibujo lateral para móvil -->
<div class="mobile-drawer" :class="{ open: menuAbierto }">
  <a href="#inicio" @click="menuAbierto=false">Inicio</a>
  <a href="#materias" @click="menuAbierto=false">Materias</a>
  <a href="#beneficios" @click="menuAbierto=false">Servicios</a>
  <a href="#tutores" @click="menuAbierto=false">Tutores</a>
  <a href="#porque" @click="menuAbierto=false">Nosotros</a>
  <a href="/views/login/login.php" style="color:#1e40af; font-weight:800;">Acceder <i class="fa-solid fa-right-to-bracket"></i></a>
</div>

<!-- ============ HERO ============ -->
<section class="hero" id="inicio">
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <span class="hero-eyebrow"><i class="fa-solid fa-mortarboard me-1"></i>Universidad Privada Domingo Savio</span>
    <h1>Apoyo académico que <span>impulsa tu futuro</span></h1>
    <p>Conectamos a estudiantes con docentes tutores especializados para reforzar materias, resolver dudas y alcanzar el rendimiento que mereces.</p>
    <div class="hero-btns">
      <a href="#materias" class="btn-cta"><i class="fa-solid fa-book-open me-1"></i>Explorar materias</a>
      <a href="/views/login/login.php" class="btn-outline-light"><i class="fa-solid fa-right-to-bracket me-1"></i>Ingresar al sistema</a>
    </div>
  </div>
</section>

<!-- ============ CIFRAS ============ -->
<section class="stats-band py-5">
  <div class="container py-3">
    <div class="row g-4 text-center">
      <div class="col-6 col-lg-3">
        <div class="stat-number">{{ animar('usuarios') }}</div>
        <div class="stat-caption">Estudiantes registrados</div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-number">{{ animar('materias') }}</div>
        <div class="stat-caption">Materias disponibles</div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-number">{{ animar('tutores') }}</div>
        <div class="stat-caption">Docentes tutores</div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="stat-number">{{ animar('tutorias') }}</div>
        <div class="stat-caption">Tutorías gestionadas</div>
      </div>
    </div>
  </div>
</section>

<!-- ============ MATERIAS (catálogo) ============ -->
<section class="py-5" id="materias" style="background: var(--upds-bg);">
  <div class="container py-4">
    <div class="text-center mb-5 reveal">
      <div class="section-eyebrow">Nuestro catálogo</div>
      <h2 class="section-title">Nuestras Materias</h2>
      <p class="section-sub mt-2">Las asignaturas más tutoradas de la UPDS, con docentes especializados listos para ayudarte.</p>
    </div>
    <div class="row g-4 justify-content-center">
      <div class="col-md-6 col-lg-4" v-for="mat in materias" :key="mat.id_materia">
        <div class="card-upds h-100 reveal">
          <div class="academia-top"></div>
          <div class="p-4 d-flex flex-column h-100">
            <div class="d-flex align-items-center gap-3 mb-3">
              <div class="academia-icon"><i class="fa-solid fa-book"></i></div>
              <h6 class="fw-bold mb-0" style="color:var(--upds-blue);">{{ mat.nombre_materia }}</h6>
            </div>
            <div class="mt-auto">
              <div class="d-flex justify-content-between small text-muted mb-1">
                <span>Tutorías registradas</span>
                <span class="fw-bold">{{ mat.total_tutorias }}</span>
              </div>
              <div class="progress">
                <div class="progress-bar" :style="{ width: anchoMateria(mat) + '%' }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <p v-if="!materias.length" class="col-12 text-muted small text-center py-3 mb-0">Aún no hay materias registradas.</p>
    </div>
  </div>
</section>

<!-- ============ SERVICIOS / BENEFICIOS ============ -->
<section class="py-5" id="beneficios">
  <div class="container py-4">
    <div class="text-center mb-5 reveal">
      <div class="section-eyebrow">Servicios</div>
      <h2 class="section-title">¿Qué puedes hacer en la plataforma?</h2>
      <p class="section-sub mt-2">Un ecosistema completo para que aproveches al máximo tu tiempo de estudio.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-6 col-lg-3" v-for="ben in beneficios" :key="ben.titulo">
        <div class="card-upds p-4 h-100 text-center reveal">
          <div class="servicio-icon mx-auto mb-3"><i :class="ben.icono"></i></div>
          <h6 class="fw-bold mb-2">{{ ben.titulo }}</h6>
          <p class="text-muted small mb-0">{{ ben.desc }}</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ ¿POR QUÉ ELEGIR UPDS? ============ -->
<section class="py-5" id="porque" style="background: var(--upds-bg);">
  <div class="container py-4">
    <div class="why-box p-4 p-md-5 reveal">
      <div class="row g-4 align-items-center">
        <div class="col-lg-7">
          <div class="section-eyebrow mb-2">Nosotros</div>
          <h2 class="section-title mb-4" style="font-size:1.9rem;">¿Por qué elegir UPDS Tutorías?</h2>
          <ul class="list-unstyled d-flex flex-column gap-3 mb-0">
            <li class="d-flex align-items-start">
              <i class="fa-solid fa-circle-check why-check me-3"></i>
              <div>
                <h6 class="fw-bold mb-1">Docentes expertos</h6>
                <p class="text-muted mb-0">Aprende de profesionales con experiencia real en la docencia universitaria.</p>
              </div>
            </li>
            <li class="d-flex align-items-start">
              <i class="fa-solid fa-book-open why-check me-3"></i>
              <div>
                <h6 class="fw-bold mb-1">Enfoque práctico</h6>
                <p class="text-muted mb-0">Sesiones orientadas a resolver dudas concretas y preparar tus evaluaciones.</p>
              </div>
            </li>
            <li class="d-flex align-items-start">
              <i class="fa-solid fa-users why-check me-3"></i>
              <div>
                <h6 class="fw-bold mb-1">Comunidad activa</h6>
                <p class="text-muted mb-0">Conecta con tutores y compañeros que te ayudan a avanzar cada semestre.</p>
              </div>
            </li>
            <li class="d-flex align-items-start">
              <i class="fa-solid fa-star why-check me-3"></i>
              <div>
                <h6 class="fw-bold mb-1">Retroalimentación continua</h6>
                <p class="text-muted mb-0">Califica cada sesión para garantizar la calidad del apoyo que recibes.</p>
              </div>
            </li>
          </ul>
        </div>
        <div class="col-lg-5 text-center">
          <div class="academia-icon mx-auto mb-3" style="width:64px; height:64px; background:rgba(34,59,135,.08); color:var(--upds-blue); display:flex; align-items:center; justify-content:center; border-radius:16px; font-size:1.7rem;"><i class="fa-solid fa-graduation-cap"></i></div>
          <h4 class="fw-bold mb-2">¡Empieza hoy mismo!</h4>
          <p class="text-muted mb-4">Únete a estudiantes que ya están mejorando su rendimiento académico con las tutorías UPDS.</p>
          <div class="why-box p-4 border-0 shadow-sm bg-white">
            <div class="d-flex justify-content-around align-items-center mb-3">
              <div><div class="stat-number" style="font-size:1.6rem;">{{ statsPT }}</div><div class="text-muted small fw-semibold">Profesión</div></div>
              <div class="vr"></div>
              <div><div class="stat-number" style="font-size:1.6rem;">{{ statsMT }}</div><div class="text-muted small fw-semibold">Medio título UE</div></div>
            </div>
            <a href="/views/login/login.php" class="btn-cta"><i class="fa-solid fa-arrow-right me-1"></i>Ver acceso</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ TUTORES DESTACADOS ============ -->
<section class="py-5" id="tutores">
  <div class="container py-4">
    <div class="text-center mb-5 reveal">
      <div class="section-eyebrow">Nuestro equipo</div>
      <h2 class="section-title">Tutores mejor calificados</h2>
      <p class="section-sub mt-2">Docentes comprometidos con tu aprendizaje, avalados por las evaluaciones de nuestros estudiantes.</p>
    </div>
    <div class="row justify-content-center g-4">
      <div class="col-md-4" v-for="tut in tutores" :key="tut.id_tutor">
        <div class="card-upds p-4 text-center h-100 reveal">
          <div class="avatar-circle">{{ inicialesTutor(tut.nombre, tut.apellido) }}</div>
          <h6 class="fw-bold mb-1">Prof. {{ tut.nombre }} {{ tut.apellido }}</h6>
          <div class="text-muted small mb-2">{{ tut.especialidad || 'Docencia general' }}</div>
          <div class="stars mb-1">
            <i v-for="n in 5" :key="n" class="fa-solid" :class="n <= Math.round(Number(tut.promedio)) ? 'fa-star' : 'fa-star-o'" style="color:#48A4E0;"></i>
            <span class="text-dark fw-bold ms-1">{{ Number(tut.promedio).toFixed(2) }}</span>
          </div>
          <div class="text-muted" style="font-size:.78rem;">
            <i class="fa-regular fa-comment-dots me-1"></i>{{ tut.total_evaluaciones }} evaluaciones &bull;
            <i class="fa-regular fa-calendar me-1"></i>{{ tut.total_tutorias }} sesiones
          </div>
        </div>
      </div>
      <p v-if="!tutores.length" class="col-12 text-muted small text-center py-3 mb-0">Aún no hay tutores evaluados.</p>
    </div>
  </div>
</section>

<!-- ============ TESTIMONIOS ============ -->
<section class="py-5" id="testimonios" style="background: var(--upds-bg);">
  <div class="container py-4">
    <div class="text-center mb-5 reveal">
      <div class="section-eyebrow">Lo que dicen</div>
      <h2 class="section-title">Opiniones de estudiantes</h2>
      <p class="section-sub mt-2">El impacto real de las tutorías en la experiencia académica de nuestros usuarios.</p>
    </div>
    <div class="row g-4">
      <div class="col-md-4" v-for="tes in testimonios" :key="tes.nombre">
        <div class="testimonial-card p-4 h-100 reveal">
          <div class="stars mb-2">
            <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
          </div>
          <blockquote class="mb-3">"{{ tes.cita }}"</blockquote>
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="fw-bold text-dark small">{{ tes.nombre }}</div>
              <div class="text-muted" style="font-size:.72rem;">{{ tes.rol }}</div>
            </div>
            <div class="avatar-circle" style="width:42px; height:42px; font-size:.95rem;">{{ tes.inicial }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ============ CTA ============ -->
<section class="cta-band py-5 text-center text-white">
  <div class="container py-4 position-relative" style="z-index:1;">
    <div class="brand-logo mx-auto mb-3" style="width:64px; height:64px;"><img src="/assets/img/upds-logo.png" alt="Logo UPDS"></div>
    <h2 class="fw-bold mb-2" style="font-family:'Encode Sans Condensed',sans-serif; font-size:clamp(1.8rem,3vw,2.6rem);">¿Listo para mejorar tus notas?</h2>
    <p class="mb-4" style="color:rgba(255,255,255,.85); max-width:560px; margin-left:auto; margin-right:auto;">Solicita tu primera tutoría hoy y da el primer paso hacia un mejor rendimiento académico.</p>
    <a href="/views/login/login.php" class="btn-cta btn-lg"><i class="fa-solid fa-arrow-right me-1"></i>Empezar ahora</a>
  </div>
</section>

<!-- ============ FOOTER ============ -->
<footer class="footer-upds pt-5 pb-4">
  <div class="container">
    <div class="row g-4 pb-4">
      <div class="col-lg-4">
        <div class="d-flex align-items-center gap-2 mb-3">
          <span class="brand-logo" style="height:100%;"><img src="/assets/img/upds-logo.png" alt="Logo UPDS"></span>
          <span class="fw-bold fs-4" style="font-family:'Encode Sans Condensed',sans-serif;">UNIVERSIDAD PRIVADA<br>DOMINGO SAVIO</span>
        </div>
        <p class="small" style="max-width:300px;">Sistema Web de Apoyo Académico para Tutorías. Profesionales más humanos, con un futuro más prometedor.</p>
        <div class="d-flex gap-2">
          <a class="social-btn" href="#" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
          <a class="social-btn" href="#" aria-label="Twitter"><i class="fa-brands fa-x-twitter"></i></a>
          <a class="social-btn" href="#" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a class="social-btn" href="#" aria-label="YouTube"><i class="fa-brands fa-youtube"></i></a>
          <a class="social-btn" href="#" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
        </div>
      </div>
      <div class="col-6 col-lg-2 offset-lg-1">
        <h6 class="text-white mb-3 small text-uppercase fw-bold">Plataforma</h6>
        <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
          <li><a href="#materias">Materias</a></li>
          <li><a href="#beneficios">Servicios</a></li>
          <li><a href="#tutores">Tutores</a></li>
          <li><a href="#testimonios">Opiniones</a></li>
        </ul>
      </div>
      <div class="col-6 col-lg-2">
        <h6 class="text-white mb-3 small text-uppercase fw-bold">Acceso</h6>
        <ul class="list-unstyled small d-flex flex-column gap-2 mb-0">
          <li><a href="/views/login/login.php">Iniciar sesión</a></li>
        </ul>
      </div>
      <div class="col-lg-3">
        <h6 class="text-white mb-3 small text-uppercase fw-bold">Contacto</h6>
        <div class="contact-card p-3 mb-2">
          <div class="small fw-bold text-white mb-1"><i class="fa-solid fa-location-dot me-2"></i>Tarija</div>
          <div class="small">Av. Los Sauces, esq. Senac<br>Santa Cruz - Bolivia</div>
        </div>
        <div class="contact-card p-3">
          <div class="small"><i class="fa-solid fa-phone me-2"></i>(591) 3 342-6600</div>
          <div class="small mt-1"><i class="fa-solid fa-envelope me-2"></i>soporte@upds.edu.bo</div>
        </div>
      </div>
    </div>
    <div class="footer-divider mb-3"></div>
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
      <div class="small">© {{ anio }} Universidad Privada Domingo Savio &bull; Materia de Tecnologías Web</div>
      <div class="small d-flex align-items-center gap-2"><i class="fa-solid fa-shield-halved text-success"></i> Hecho con PHP + Vue 3</div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
<script>
  window.__LANDING_STATS__ = <?= json_encode($stats, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  window.__LANDING_MATERIAS__ = <?= json_encode($materiasTop, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;
  window.__LANDING_MAX_MATERIAS = <?= (int)$maxMaterias ?>;
  window.__LANDING_TUTORES__ = <?= json_encode($tutoresTop, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

  const { createApp, ref } = Vue;

  createApp({
    setup() {
      const scrolled = ref(false);
      const menuAbierto = ref(false);
      const anio = ref(new Date().getFullYear());
      const statsPT = ref('2026');
      const statsMT = ref('Formación');

      const animadores = {};
      const animar = (clave) => {
        if (!animadores[clave]) {
          const objetivoFinal = Number(window.__LANDING_STATS__[clave] ?? 0);
          animadores[clave] = ref(0);
          const t0 = performance.now();
          const dur = 900;
          const paso = (ahora) => {
            const p = Math.min((ahora - t0) / dur, 1);
            const e = 1 - Math.pow(1 - p, 3);
            animadores[clave].value = Math.round(objetivoFinal * e);
            if (p < 1) requestAnimationFrame(paso);
          };
          requestAnimationFrame(paso);
        }
        return animadores[clave].value;
      };

      const materias = ref(window.__LANDING_MATERIAS__ || []);
      const tutores = ref(window.__LANDING_TUTORES__ || []);
      const maxMaterias = Number(window.__LANDING_MAX_MATERIAS || 1);
      const anchoMateria = (mat) => Math.round((Number(mat.total_tutorias) / maxMaterias) * 100);
      const inicialesTutor = (n, a) => String(n || '').charAt(0).toUpperCase() + String(a || '').charAt(0).toUpperCase();

      const beneficios = [
        { titulo: 'Solicitud rápida', desc: 'Elige materia, docente y horario en pocos clics, sin trámites.', icono: 'fa-solid fa-calendar-plus', fondo: '#fef3c7', color: '#d97706' },
        { titulo: 'Docentes verificados', desc: 'Tutores con perfil por especialidad y disponibilidad semanal.', icono: 'fa-solid fa-circle-check', fondo: '#eef2ff', color: '#4338ca' },
        { titulo: 'Según tu agenda', desc: 'Tutorías presenciales y en línea que se adaptan a tu tiempo.', icono: 'fa-solid fa-clock', fondo: '#ecfdf5', color: '#059669' },
        { titulo: 'Evaluación continua', desc: 'Califica cada sesión para mejorar la calidad del apoyo recibido.', icono: 'fa-solid fa-star', fondo: '#fff7ed', color: '#ea580c' },
      ];

      const testimonios = [
        { nombre: 'María Fernández', rol: 'Estudiante de Ing. de Sistemas', inicial: 'M', cita: 'Gracias a las tutorías de Base de Datos pasé de estar perdida a sacar mi mejor calificación del semestre.' },
        { nombre: 'Jorge Rojas', rol: 'Estudiante de Psicología', inicial: 'J', cita: 'El sistema es muy fácil de usar. En minutos agendé una sesión con un docente que resolvió todas mis dudas.' },
        { nombre: 'Camila Suárez', rol: 'Estudiante de Redes', inicial: 'C', cita: 'Poder evaluar cada sesión y ver el perfil de los tutores me da mucha confianza. Excelente iniciativa.' },
      ];

      const io = new IntersectionObserver((entries) => {
        entries.forEach((en) => {
          if (en.isIntersecting) { en.target.classList.add('visible'); io.unobserve(en.target); }
        });
      }, { threshold: 0.15 });
      window.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.reveal').forEach((el) => io.observe(el));
      });

      window.addEventListener('scroll', () => {
        scrolled.value = window.scrollY > 60;
      }, { passive: true });

      return { scrolled, menuAbierto, anio, statsPT, statsMT, animar, materias, tutores, anchoMateria, inicialesTutor, beneficios, testimonios };
    },
  }).mount('#app-landing');
</script>
</body>
</html>