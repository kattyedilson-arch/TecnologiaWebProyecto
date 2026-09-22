<?php
// =========================================================
// VISTA: PANTALLA DE INICIO DE SESIÓN (views/login/login.php)
// ---------------------------------------------------------
// Página de acceso rediseñada con identidad visual propia
// (sin look de Bootstrap): CSS puro + interacción con VUE 3.
// La lógica del sistema NO cambia: envía el POST a
// controllers/login_procesar.php con el token CSRF, redirige
// a su panel si ya hay sesión y muestra el error guardado en
// $_SESSION['login_error'].
// =========================================================
require_once __DIR__ . '/../../includes/funciones.php';
iniciarSesion();
// Si ya está logueado, redirigir
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

// Cifras reales para el panel informativo (si la BD responde)
$stats = ['usuarios' => 0, 'materias' => 0, 'tutores' => 0];
try {
    require_once __DIR__ . '/../../config/conexion.php';
    require_once __DIR__ . '/../../models/DashboardModel.php';
    $r = (new DashboardModel($pdo))->obtenerResumenGlobal();
    $stats = [
        'usuarios' => (int)$r['total_usuarios'],
        'materias' => (int)$r['total_materias'],
        'tutores'  => (int)$r['total_tutores'],
    ];
} catch (Throwable $e) {
    error_log('Login stats: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión - Sistema de Tutorías UPDS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    [v-cloak] { display: none !important; }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      min-height: 100vh;
      background:
        linear-gradient(rgba(255,255,255,.82), rgba(242,245,251,.9)),
        url('/assets/img/fondo-semi-borroso.jpg') center / cover no-repeat fixed;
      color: #1e293b;
      overflow-x: hidden;
      display: flex;
    }
    .wrap {
      position: relative; z-index: 1;
      width: 100%; min-height: 100vh;
      display: flex; flex-direction: column;
    }
    /* ---- Barra superior ---- */
    .topbar {
      display: flex; justify-content: space-between; align-items: center;
      padding: 1.25rem 2rem;
      max-width: 1280px; width: 100%; margin: 0 auto;
    }
    .brand { display: flex; align-items: center; gap: .75rem; text-decoration: none; }
    .brand-logo {
      width: 46px; height: 46px; border-radius: 14px;
      background: #fff;
      padding: 3px;
      box-shadow: 0 8px 20px rgba(34,59,135,.18);
    }
    .brand-logo img { width: 100%; height: 100%; object-fit: contain; border-radius: inherit; }
    .brand-name { font-weight: 800; color: #16255c; letter-spacing: -.3px; line-height: 1.1; }
    .brand-sub { font-size: .7rem; color: #64748b; font-weight: 600; letter-spacing: .5px; text-transform: uppercase; }
    .link-home { color: #64748b; text-decoration: none; font-size: .85rem; font-weight: 600; display: flex; align-items: center; gap: .5rem; transition: color .2s; }
    .link-home:hover { color: #223B87; }
    /* ---- Contenido principal ---- */
    .content {
      flex: 1; display: flex; align-items: center; justify-content: center;
      padding: 2rem 1.5rem;
    }
    .login-grid {
      max-width: 1120px; width: 100%;
      display: grid; grid-template-columns: 1.1fr .9fr;
      gap: 3rem; align-items: center;
    }
    /* ---- Panel izquierdo (marca) ---- */
    .brand-panel {
      display: none; flex-direction: column; gap: 1.25rem;
      background: linear-gradient(160deg, #16255c 0%, #223B87 55%, #2f4ba7 100%);
      border-radius: 28px;
      padding: 2.5rem 2.25rem;
      box-shadow: 0 30px 60px rgba(34,59,135,.35);
    }
    @media (min-width: 992px) { .brand-panel { display: flex; } }
    .eyebrow {
      display: inline-flex; align-items: center; gap: .5rem;
      background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.2);
      padding: .45rem 1rem; border-radius: 999px;
      font-size: .78rem; font-weight: 700; color: #e0f2fe; letter-spacing: .3px;
      width: fit-content;
    }
    .eyebrow i { color: #87CEEB; }
    .brand-panel h1 {
      font-size: clamp(2.2rem, 3.6vw, 3rem);
      font-weight: 900; color: #fff; letter-spacing: -1.5px; line-height: 1.15;
    }
    .brand-panel h1 span { color: transparent; background: linear-gradient(90deg,#87CEEB,#48A4E0); -webkit-background-clip: text; background-clip: text; }
    .brand-panel p.lead { color: #c9d4ea; line-height: 1.7; max-width: 480px; font-size: .98rem; }
    .feature-list { display: flex; flex-direction: column; gap: 1rem; margin-top: .75rem; }
    .feature-item { display: flex; gap: .9rem; align-items: flex-start; }
    .feature-ico {
      width: 42px; height: 42px; border-radius: 12px; flex-shrink: 0;
      background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.18);
      display: flex; align-items: center; justify-content: center;
      color: #87CEEB; font-size: 1.05rem;
    }
    .feature-txt b { display: block; font-size: .9rem; color: #fff; }
    .feature-txt span { font-size: .8rem; color: #a9b9de; }
    .stat-strip { display: flex; gap: 2rem; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,.16); }
    .stat-strip div b { display: block; font-size: 1.5rem; font-weight: 900; color: #fff; }
    .stat-strip div span { font-size: .75rem; color: #a9b9de; }
    /* ---- Tarjeta de login ---- */
    .login-card {
      background: #fff;
      border: 1px solid #e6eaf2;
      border-top: 4px solid #48A4E0;
      border-radius: 24px;
      padding: 2.25rem;
      box-shadow: 0 24px 60px rgba(15,23,42,.12);
      animation: rise .6s ease both;
    }
    @keyframes rise { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: none; } }
    .login-card h2 { color: #0f172a; font-weight: 800; letter-spacing: -.5px; margin-bottom: .3rem; }
    .login-card .sub { color: #64748b; font-size: .9rem; margin-bottom: 1.5rem; }
    .form-group { margin-bottom: 1.1rem; position: relative; }
    .form-group label {
      display: block; font-size: .78rem; font-weight: 700; color: #475569;
      margin-bottom: .45rem; letter-spacing: .3px; text-transform: uppercase;
    }
    .input-wrap { position: relative; }
    .input-wrap > i {
      position: absolute; left: .95rem; top: 50%; transform: translateY(-50%);
      color: #94a3b8; font-size: 1.05rem; z-index: 2;
    }
    .input-wrap input {
      width: 100%; padding: .85rem 2.7rem;
      background: #fff;
      border: 1px solid #d7deec;
      border-radius: 12px;
      color: #0f172a; font-size: .95rem;
      outline: none; transition: border-color .15s, box-shadow .15s, background .15s;
    }
    .input-wrap input::placeholder { color: #94a3b8; }
    .input-wrap input:focus {
      border-color: #48A4E0;
      box-shadow: 0 0 0 3px rgba(72,164,224,.18);
      background: #fff;
    }
    .toggle-pass {
      position: absolute; right: .7rem; top: 50%; transform: translateY(-50%);
      background: none; border: none; color: #94a3b8; font-size: 1.05rem;
      cursor: pointer; padding: .35rem; border-radius: 8px; transition: color .15s;
    }
    .toggle-pass:hover { color: #223B87; }
    .error-alert {
      display: flex; gap: .6rem; align-items: flex-start;
      background: #fef2f2; border: 1px solid #fecaca;
      color: #dc2626; padding: .8rem 1rem; border-radius: 12px; font-size: .85rem;
      margin-bottom: 1.1rem; animation: rise .3s ease;
    }
    .error-alert i { font-size: 1rem; margin-top: 1px; }
    .btn-submit {
      width: 100%; margin-top: .4rem;
      background: linear-gradient(135deg, #1a2c6b, #223B87);
      color: #fff; border: none; border-radius: 12px;
      padding: .95rem; font-weight: 800; font-size: .95rem; letter-spacing: .3px;
      cursor: pointer; display: flex; align-items: center; justify-content: center; gap: .6rem;
      transition: transform .15s, box-shadow .15s, filter .15s;
      box-shadow: 0 10px 24px rgba(34,59,135,.35);
    }
    .btn-submit:hover { transform: translateY(-2px); filter: brightness(1.06); }
    .btn-submit:disabled { opacity: .7; cursor: not-allowed; transform: none; }
    .spinner {
      width: 18px; height: 18px; border: 2px solid rgba(255,255,255,.35);
      border-top-color: #fff; border-radius: 50%;
      animation: spin .7s linear infinite;
    }
    @keyframes spin { to { transform: rotate(360deg); } }
    .orc { display: flex; align-items: center; gap: .6rem; margin: 1.4rem 0 .6rem; color: #94a3b8; font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }
    .orc::before, .orc::after { content: ''; flex: 1; height: 1px; background: rgba(15,23,42,.12); }
    .demo-hint { font-size: .78rem; color: #64748b; margin-bottom: .7rem; display: flex; align-items: center; gap: .4rem; }
    .demo-hint i { color: #48A4E0; }
    .demo-btns { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; }
    .demo-btn {
      border: 1px solid rgba(34,59,135,.18); border-radius: 10px; padding: .55rem .4rem;
      background: #f4f7ff; color: #223B87; font-size: .8rem; font-weight: 700;
      cursor: pointer; text-align: center; transition: all .15s; line-height: 1.2;
    }
    .demo-btn small { display: block; font-size: .62rem; font-weight: 400; color: #94a3b8; }
    .demo-btn:hover { background: #e8effc; transform: translateY(-1px); }
    .login-footer { margin-top: 1.6rem; text-align: center; font-size: .78rem; color: #94a3b8; }
    .login-footer a { color: #223B87; text-decoration: none; font-weight: 600; }
    .login-footer a:hover { color: #1a2c6b; }
    ::selection { background: rgba(72,164,224,.3); }
  </style>
</head>
<body id="app-login" v-cloak>
  <div class="wrap">
    <header class="topbar">
      <a class="brand" href="/">
        <div class="brand-logo"><img src="/assets/img/upds-logo.png" alt="Logo UPDS"></div>
        <div>
          <div class="brand-name">Tutorías UPDS</div>
          <div class="brand-sub">Apoyo Académico</div>
        </div>
      </a>
      <a class="link-home" href="/"><i class="bi bi-arrow-left"></i> Volver al inicio</a>
    </header>

    <main class="content">
      <div class="login-grid">
        <!-- Panel de marca (pantallas grandes) -->
        <div class="brand-panel">
          <span class="eyebrow"><i class="bi bi-stars"></i> Plataforma oficial de tutorías</span>
          <h1>El apoyo que necesitas,<br>cuando <span>más lo necesitas.</span></h1>
          <p class="lead">
            Conectamos a estudiantes de la UPDS con docentes tutores especializados
            para reforzar materias, resolver dudas y mejorar tu rendimiento en cada asignatura.
          </p>
          <div class="feature-list">
            <div class="feature-item">
              <div class="feature-ico"><i class="bi bi-calendar-heart"></i></div>
              <div class="feature-txt"><b>Solicitud en segundos</b><span>Elige materia, docente y horario a tu medida.</span></div>
            </div>
            <div class="feature-item">
              <div class="feature-ico"><i class="bi bi-patch-check"></i></div>
              <div class="feature-txt"><b>Docentes verificados</b><span>Perfiles por especialidad y disponibilidad semanal.</span></div>
            </div>
            <div class="feature-item">
              <div class="feature-ico"><i class="bi bi-star"></i></div>
              <div class="feature-txt"><b>Evaluación continua</b><span>Califica cada sesión y mejora la calidad del apoyo.</span></div>
            </div>
          </div>
          <div class="stat-strip">
            <div><b>{{ stats.usuarios }}+</b><span>Estudiantes</span></div>
            <div><b>{{ stats.materias }}+</b><span>Materias</span></div>
            <div><b>{{ stats.tutores }}+</b><span>Tutores</span></div>
          </div>
        </div>

        <!-- Tarjeta de acceso -->
        <div class="login-card">
          <h2>¡Bienvenido(a)! 👋</h2>
          <p class="sub">Ingresa con tu usuario o correo institucional.</p>

          <?php if (isset($_SESSION['login_error'])): ?>
            <div class="error-alert">
              <i class="bi bi-exclamation-triangle-fill"></i>
              <div><?= htmlspecialchars($_SESSION['login_error']) ?></div>
            </div>
            <?php unset($_SESSION['login_error']); ?>
          <?php endif; ?>

          <form action="../../controllers/login_procesar.php" method="POST" autocomplete="off">
            <?= campoCsrf() ?>
            <div class="form-group">
              <label for="usuarioInput">Usuario o correo</label>
              <div class="input-wrap">
                <i class="bi bi-person"></i>
                <input type="text" id="usuarioInput" name="usuario" placeholder="Tu usuario o correo" required autofocus>
              </div>
            </div>

            <div class="form-group">
              <label for="passwordInput">Contraseña</label>
              <div class="input-wrap">
                <i class="bi bi-lock"></i>
                <input :type="mostrarContrasena ? 'text' : 'password'" id="passwordInput" name="contrasena" placeholder="Su contraseña" required>
                <button type="button" class="toggle-pass" :title="mostrarContrasena ? 'Ocultar' : 'Ver'"
                        @click="mostrarContrasena = !mostrarContrasena">
                  <i :class="mostrarContrasena ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                </button>
              </div>
            </div>

            <button type="submit" class="btn-submit" :disabled="cargando">
              <span v-if="cargando" class="spinner"></span>
              <span>{{ cargando ? 'Verificando...' : 'Ingresar al sistema' }}</span>
              <i class="bi bi-arrow-right" v-if="!cargando"></i>
            </button>
          </form>

          <div class="orc">Acceso rápido de prueba</div>
          <div class="demo-hint"><i class="bi bi-key"></i> Cuentas demo (contraseña: <b>password</b>)</div>
          <div class="demo-btns">
            <button class="demo-btn" @click="rellenar('admin')">Administrador<small>admin</small></button>
            <button class="demo-btn" @click="rellenar('tutor1')">Docente<small>tutor1</small></button>
            <button class="demo-btn" @click="rellenar('estudiante1')">Estudiante<small>estudiante1</small></button>
          </div>

          <div class="login-footer">
            Universidad Privada Domingo Savio &bull; Tecnologías Web &copy; <?= date('Y') ?><br>
            <a href="/">← Volver a la página principal</a>
          </div>
        </div>
      </div>
    </main>
  </div>

  <script>
    window.__LOGIN_STATS__ = {
      usuarios: <?= (int)($stats['usuarios'] ?? 0) ?>,
      materias: <?= (int)($stats['materias'] ?? 0) ?>,
      tutores:  <?= (int)($stats['tutores'] ?? 0) ?>,
    };
  </script>

  <script src="https://unpkg.com/vue@3/dist/vue.global.prod.js"></script>
  <script>
    const { createApp, ref, onMounted, reactive } = Vue;

    createApp({
      setup() {
        const mostrarContrasena = ref(false);
        const cargando = ref(false);
        const stats = reactive(window.__LOGIN_STATS__ || { usuarios: 0, materias: 0, tutores: 0 });

        const rellenar = (usuario) => {
          const u = document.getElementById('usuarioInput');
          const p = document.getElementById('passwordInput');
          if (u) { u.value = usuario; u.dispatchEvent(new Event('input', { bubbles: true })); }
          if (p) p.value = 'password';
          u && u.focus();
        };

        // Botón deshabilitado mientras envía el formulario
        onMounted(() => {
          const form = document.querySelector('form');
          if (form) {
            form.addEventListener('submit', () => {
              cargando.value = true;
            });
          }
        });

        return { mostrarContrasena, cargando, stats, rellenar };
      },
    }).mount('#app-login');
  </script>
</body>
</html>