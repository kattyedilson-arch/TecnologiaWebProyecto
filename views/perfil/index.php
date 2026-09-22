<?php
// =========================================================
// VISTA: MI PERFIL (views/perfil/index.php)
// ---------------------------------------------------------
// Muestra la tarjeta de identidad del usuario (avatar con
// iniciales, nombre completo, rol y fecha de registro) junto
// a un formulario de edición de datos personales:
//   - Nombre, apellido, correo y teléfono.
//   - Zona opcional de cambio de contraseña (nueva + confirmación).
// Los datos llegan preparados en $usuario desde el controlador y
// los errores de validación se muestran en $errores. Al volver
// redirige al panel correspondiente según el rol.
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
require_once __DIR__ . '/../../includes/funciones.php';
$tituloPagina = 'Mi Perfil - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-8 col-xl-7">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h3 class="fw-bold mb-0 d-flex align-items-center gap-2">
        <i class="bi bi-person-circle text-primary"></i>
        <span>Mi Perfil</span>
      </h3>
      <a href="<?= $_SESSION['rol'] === 'administrador' ? '/controllers/dashboard.php' : ($_SESSION['rol'] === 'tutor' ? '/views/tutor/panel.php' : '/views/estudiante/panel.php') ?>" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1">
        <i class="bi bi-arrow-left"></i> Volver
      </a>
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

    <div class="row g-4">
      <div class="col-md-4">
        <div class="card card-custom p-4 text-center h-100">
          <?= avatarHTML($usuario['foto_perfil'] ?? '', iniciales($usuario['nombre'], $usuario['apellido']), 'avatar-lg mx-auto mb-3') ?>
          <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?></h5>
          <span class="badge rounded-pill px-3 py-1 text-capitalize mt-2 mb-3 badge-rol badge-<?= htmlspecialchars($usuario['nombre_rol'] ?? '') ?>">
            <?= htmlspecialchars($usuario['nombre_rol'] ?? '') ?>
          </span>
          <div class="small text-muted">
            <div><i class="bi bi-person me-1"></i><?= htmlspecialchars($usuario['usuario']) ?></div>
            <div><i class="bi bi-calendar-week me-1"></i>Desde <?= date('d/m/Y', strtotime($usuario['fecha_registro'])) ?></div>
          </div>

          <div class="mt-3 pt-3 border-top text-start">
            <form method="POST" action="/controllers/perfil_foto.php" enctype="multipart/form-data" class="text-start" id="formFotoPerfil">
              <?= campoCsrf() ?>
              <label class="form-label fw-semibold text-secondary small text-uppercase mb-2"><i class="bi bi-camera me-1"></i>Foto de Perfil</label>
              <div class="d-flex gap-2">
                <input type="file" name="foto_perfil" id="inputFotoPerfil" accept="image/jpeg,image/png,image/webp" class="form-control form-control-sm rounded-3" required onchange="mostrarVistaPreviaFoto(event)">
                <button type="submit" class="btn btn-primary btn-sm rounded-3 flex-shrink-0">Subir</button>
              </div>
              <div class="form-text mt-1">JPG, PNG o WEBP · Máximo 2 MB.</div>
              <img id="vistaPreviaFoto" class="d-none mt-2 rounded-circle border object-fit-cover" style="width:64px; height:64px;" alt="Previsualización">
              <?php if (!empty($usuario['foto_perfil'])): ?>
                <a href="/controllers/perfil_foto.php?accion=quitar&token=<?= tokenCsrfUrl() ?>" class="btn btn-outline-danger btn-sm mt-2 d-inline-flex align-items-center gap-1" onclick="return confirm('¿Quitar tu foto de perfil?');">
                  <i class="bi bi-trash"></i> Quitar foto
                </a>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>

      <div class="col-md-8">
        <div class="card card-custom p-4 p-md-4">
          <h6 class="fw-bold mb-3 text-uppercase small text-secondary"><i class="bi bi-pencil-square me-1"></i>Datos Personales</h6>
          <form method="POST" autocomplete="off" novalidate>
          <?= campoCsrf() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary small text-uppercase">Nombre *</label>
                <input type="text" name="nombre" class="form-control rounded-3 py-2" value="<?= htmlspecialchars($usuario['nombre']) ?>" required pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]+">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary small text-uppercase">Apellido *</label>
                <input type="text" name="apellido" class="form-control rounded-3 py-2" value="<?= htmlspecialchars($usuario['apellido']) ?>" required pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]+">
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary small text-uppercase">Correo Electrónico *</label>
                <input type="email" name="correo" class="form-control rounded-3 py-2" value="<?= htmlspecialchars($usuario['correo']) ?>" required>
              </div>
              <div class="col-md-6">
                <label class="form-label fw-semibold text-secondary small text-uppercase">Teléfono</label>
                <input type="tel" name="telefono" class="form-control rounded-3 py-2" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>" placeholder="Ej: 70000001" pattern="[0-9+\s()\-]{7,20}">
              </div>
            </div>

            <div class="mt-4 pt-3 border-top">
              <h6 class="fw-bold mb-3 text-uppercase small text-secondary"><i class="bi bi-shield-lock me-1"></i>Cambiar Contraseña</h6>
              <div class="row g-3 mb-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small text-uppercase">Nueva Contraseña</label>
                  <input type="password" name="clave_nueva" class="form-control rounded-3 py-2" minlength="8" placeholder="Mínimo 8 caracteres, letras y números">
                  <div class="form-text">Déjalo vacío si no deseas cambiarla.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold text-secondary small text-uppercase">Confirmar Contraseña</label>
                  <input type="password" name="clave_conf" class="form-control rounded-3 py-2" minlength="8" placeholder="Repite la nueva contraseña">
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
              <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
                <i class="bi bi-check2-circle"></i>
                <span>Guardar Cambios</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  function mostrarVistaPreviaFoto(event) {
    const preview = document.getElementById('vistaPreviaFoto');
    const archivo = event.target.files && event.target.files[0];
    if (archivo && archivo.type.startsWith('image/')) {
      preview.src = URL.createObjectURL(archivo);
      preview.classList.remove('d-none');
    } else {
      preview.classList.add('d-none');
    }
  }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>