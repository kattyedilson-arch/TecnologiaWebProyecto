<?php
// =========================================================
// VISTA: ERROR 403 - ACCESO DENEGADO (views/error/403.php)
// ---------------------------------------------------------
// Página autocontenida de "no autorizado". Se muestra cuando
// requerirPermiso() (includes/permisos.php) detecta que el rol
// de la sesión no tiene permiso para la acción solicitada.
// =========================================================
require_once __DIR__ . '/../../includes/rol_panel.php';
$destino = destinoPanelRol();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso denegado - UPDS</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    * { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
    body {
      margin: 0; min-height: 100vh;
      background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 100%);
      display: flex; align-items: center; justify-content: center; padding: 2rem;
    }
    .caja-403 {
      max-width: 460px; width: 100%;
      background: #fff; border: 1px solid #e8edf5; border-radius: 22px;
      padding: 3rem 2.5rem; text-align: center;
      box-shadow: 0 24px 60px rgba(15,23,42,.12);
    }
    .codigo {
      font-size: 5.2rem; font-weight: 900; line-height: 1;
      background: linear-gradient(120deg, #1e3a8a, #3b82f6);
      -webkit-background-clip: text; background-clip: text; color: transparent;
    }
    h1 { font-size: 1.4rem; font-weight: 800; color: #0f172a; margin: 1rem 0 .5rem; }
    p { color: #64748b; font-size: .92rem; line-height: 1.6; margin: 0 0 1.5rem; }
    .icono {
      width: 64px; height: 64px; margin: 0 auto 1.2rem; border-radius: 18px;
      background: linear-gradient(135deg, #fecaca, #fee2e2); color: #dc2626;
      display: flex; align-items: center; justify-content: center; font-size: 1.8rem;
    }
    a.boton {
      display: inline-flex; align-items: center; gap: .5rem;
      background: #1e40af; color: #fff; text-decoration: none; font-weight: 600;
      padding: .7rem 1.4rem; border-radius: 12px; font-size: .9rem;
      box-shadow: 0 8px 20px rgba(30,64,175,.28);
    }
    a.boton:hover { background: #1e3a8a; }
  </style>
</head>
<body>
  <div class="caja-403">
    <div class="icono"><i class="bi bi-shield-lock-fill"></i></div>
    <div class="codigo">403</div>
    <h1>Acceso denegado</h1>
    <p>
      Tu perfil no tiene permisos para realizar esta acción en el módulo
      <strong>Modalidades de Grado</strong>. Si crees que es un error,
      contacta al administrador del sistema.
    </p>
    <a class="boton" href="<?= htmlspecialchars($destino) ?>">
      <span>Volver a mi panel</span>
    </a>
  </div>
</body>
</html>