<?php
// =========================================================
// VISTA: PANTALLA DE INICIO DE SESIÓN (views/login/login.php)
// ---------------------------------------------------------
// Página de acceso al sistema con diseño de dos columnas:
//   - Izquierda: panel de identidad UPDS con las ventajas de
//     la plataforma (solicitar tutorías, docentes y
//     retroalimentación) visible en pantallas grandes.
//   - Derecha : formulario de acceso (usuario o correo +
//     contraseña) que envía a controllers/login_procesar.php.
// Si el usuario ya tiene sesión activa, se redirige a su
// panel según el rol. Muestra también las cuentas demo y el
// mensaje de error guardado en $_SESSION['login_error'].
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión - Sistema de Tutorías UPDS</title>
  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <!-- Bootstrap 5.3 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    body {
      font-family: 'Inter', system-ui, -apple-system, sans-serif;
      min-height: 100vh;
      display: flex;
      align-items: stretch;
      background: #f1f5f9;
    }
    /* Panel izquierdo con la identidad de la plataforma */
    .brand-panel {
      background: linear-gradient(135deg, #1e1b4b 0%, #3730a3 45%, #4f46e5 100%);
      color: #fff;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 3.5rem;
    }
    .brand-panel::before, .brand-panel::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      background: rgba(255,255,255,0.05);
    }
    .brand-panel::before { width: 340px; height: 340px; top: -90px; right: -90px; }
    .brand-panel::after  { width: 260px; height: 260px; bottom: -70px; left: -70px; }
    .brand-logo {
      width: 62px; height: 62px;
      border-radius: 18px;
      background: linear-gradient(135deg, #f59e0b, #f97316);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.9rem;
      box-shadow: 0 10px 24px rgba(0,0,0,0.3);
    }
    .feature-item { display: flex; gap: .9rem; align-items: flex-start; }
    .feature-ico {
      width: 40px; height: 40px; border-radius: 12px;
      background: rgba(255,255,255,0.12);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.1rem; flex-shrink: 0;
    }
    .login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 2rem; }
    .login-card { max-width: 440px; width: 100%; }
    .btn-login {
      background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
      border: none;
      font-weight: 700;
      letter-spacing: .3px;
      box-shadow: 0 8px 20px rgba(79, 70, 229, 0.35);
    }
    .btn-login:hover { background: linear-gradient(135deg, #3730a3 0%, #312e81 100%); }
    .demo-badge { background: #eef2ff; border: 1px dashed #c7d2fe; }
    .text-indigo { color: #4338ca !important; }
    .badge-rol { font-size: .75rem; letter-spacing: .4px; padding: .4em .9em; }
    .badge-admin { background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-tutor { background-color: #e0e7ff; color: #4338ca; border: 1px solid #c7d2fe; }
    .badge-estudiante { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
  </style>
</head>
<body>

<!-- Columna izquierda: identidad y beneficios -->
<div class="col-lg-6 brand-panel d-none d-lg-flex">
  <div class="position-relative" style="z-index: 1;">
    <div class="brand-logo mb-4"><i class="bi bi-mortarboard-fill"></i></div>
    <h1 class="fw-black mb-2" style="font-size: 2.4rem; letter-spacing: -.5px;">Sistema de Apoyo<br>Académico</h1>
    <p class="text-white-50 mb-4" style="max-width: 420px;">Conectamos a estudiantes con docentes tutores para reforzar materias, resolver dudas y mejorar tu rendimiento académico.</p>

    <div class="d-flex flex-column gap-3">
      <div class="feature-item">
        <div class="feature-ico"><i class="bi bi-calendar-plus"></i></div>
        <div>
          <div class="fw-bold small">Solicita tutorías en segundos</div>
          <div class="text-white-50 small">Elige materia, docente y horario que mejor se adapte a ti.</div>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-ico"><i class="bi bi-people"></i></div>
        <div>
          <div class="fw-bold small">Docentes especializados</div>
          <div class="text-white-50 small">Tutores con perfil por especialidad y disponibilidad semanal.</div>
        </div>
      </div>
      <div class="feature-item">
        <div class="feature-ico"><i class="bi bi-star"></i></div>
        <div>
          <div class="fw-bold small">Retroalimentación continua</div>
          <div class="text-white-50 small">Califica cada sesión y ayuda a mejorar la calidad del apoyo.</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Columna derecha: formulario de acceso -->
<div class="col-lg-6 login-wrap">
  <div class="login-card">
    <div class="text-center mb-4 d-lg-none">
      <div class="brand-logo mx-auto mb-3" style="background:linear-gradient(135deg,#f59e0b,#f97316);"><i class="bi bi-mortarboard-fill"></i></div>
      <h3 class="fw-black text-dark mb-1">Sistema de Tutorías</h3>
      <p class="text-muted small">Universidad Privada Domingo Savio</p>
    </div>

    <div class="card border-0 rounded-4 shadow-lg" style="box-shadow: 0 20px 45px rgba(30,27,75,.15) !important;">
      <div class="card-body p-4 p-md-5">
        <h4 class="fw-bold text-dark mb-1">¡Bienvenido(a)! 👋</h4>
        <p class="text-muted small mb-4">Ingresa con tu usuario o correo para acceder al portal.</p>

        <?php if (isset($_SESSION['login_error'])): ?>
          <div class="alert alert-danger d-flex align-items-center gap-2 py-2 px-3 rounded-3" role="alert" style="font-size: 0.9rem;">
            <i class="bi bi-exclamation-triangle-fill fs-5 flex-shrink-0"></i>
            <div><?= htmlspecialchars($_SESSION['login_error']) ?></div>
          </div>
          <?php unset($_SESSION['login_error']); ?>
        <?php endif; ?>

        <form action="../../controllers/login_procesar.php" method="POST" autocomplete="off">
          <?= campoCsrf() ?>
          <div class="form-floating mb-3">
            <input type="text" class="form-control rounded-3" id="usuarioInput" name="usuario" placeholder="Usuario o Correo" required autofocus>
            <label for="usuarioInput"><i class="bi bi-person me-1"></i>Usuario o Correo</label>
          </div>

          <div class="form-floating mb-4">
            <input type="password" class="form-control rounded-3" id="passwordInput" name="contrasena" placeholder="Contraseña" required>
            <label for="passwordInput"><i class="bi bi-lock me-1"></i>Contraseña</label>
          </div>

          <button type="submit" class="btn btn-login btn-primary w-100 py-3 rounded-3 d-flex align-items-center justify-content-center gap-2">
            <span>Ingresar al Sistema</span>
            <i class="bi bi-arrow-right"></i>
          </button>
        </form>

        <div class="mt-4 p-3 demo-badge rounded-3">
          <div class="small fw-bold text-indigo mb-2"><i class="bi bi-key me-1"></i>Cuentas de prueba (clave: password)</div>
          <div class="d-flex flex-wrap gap-2 small text-muted">
            <span class="badge badge-rol badge-admin">admin</span>
            <span class="badge badge-rol badge-tutor">tutor1</span>
            <span class="badge badge-rol badge-estudiante">estudiante1</span>
          </div>
        </div>
      </div>
    </div>

    <div class="text-center mt-4 small text-muted">
      Universidad Privada Domingo Savio &bull; Tecnologías Web &copy; <?= date('Y') ?>
    </div>
  </div>
</div>

</body>
</html>