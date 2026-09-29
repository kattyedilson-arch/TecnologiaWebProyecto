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
              <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-primary btn-sm rounded-3 d-flex align-items-center justify-content-center gap-2" onclick="abrirGaleria(event)">
                  <i class="bi bi-images"></i> Subir de la galería
                </button>
                <button type="button" class="btn btn-primary btn-sm rounded-3 d-flex align-items-center justify-content-center gap-2" onclick="abrirCamara()">
                  <i class="bi bi-camera-video"></i> Tomar foto con la cámara
                </button>
              </div>
              <input type="file" name="foto_perfil" id="inputFotoPerfil" accept="image/jpeg,image/png,image/webp" class="d-none" onchange="mostrarVistaPreviaFoto(event)">
              <img id="vistaPreviaFoto" class="d-none mt-2 rounded-circle border object-fit-cover" style="width:64px; height:64px;" alt="Previsualización">
              <div class="form-text mt-1" id="infoFoto">JPG, PNG o WEBP · Máximo 2 MB.</div>
              <div class="d-flex flex-wrap gap-2 mt-2">
                <button type="submit" id="btnGuardarFoto" class="btn btn-success btn-sm rounded-3 d-none flex-shrink-0">
                  <i class="bi bi-check-lg me-1"></i> Guardar foto
                </button>
                <?php if (!empty($usuario['foto_perfil'])): ?>
                  <a href="/controllers/perfil_foto.php?accion=quitar&token=<?= tokenCsrfUrl() ?>" class="btn btn-outline-danger btn-sm rounded-3 d-inline-flex align-items-center gap-1" onclick="return confirmarEnlace(this.href, {titulo:'Quitar foto de perfil', texto:'¿Quitar tu foto de perfil?', icono:'warning', textoConfirmar:'Sí, quitar', color:'#dc2626'});">
                    <i class="bi bi-trash"></i> Quitar foto
                  </a>
                <?php endif; ?>
              </div>
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

<!-- Modal: captura con la cámara en tiempo real -->
<div class="modal fade" id="modalCamara" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 420px;">
    <div class="modal-content rounded-4 border-0 shadow">
      <div class="modal-header border-0 py-3">
        <h6 class="fw-bold mb-0"><i class="bi bi-camera-video me-2 text-primary"></i>Capturar foto con la cámara</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body pt-0">
        <div id="cajaVideo">
          <video id="camaraVideo" autoplay playsinline muted class="w-100 rounded-3" style="aspect-ratio:1/1; object-fit:cover; background:#0f172a;"></video>
        </div>
        <div id="cajaErrorCamara" class="d-none text-center p-3">
          <i class="bi bi-camera-video-off fs-3 d-block mb-2 text-danger"></i>
          <p class="mb-1 small"></p>
          <p class="small text-muted">Alternativa: usa la cámara nativa del dispositivo.</p>
        </div>
        <div id="cajaFallbackNativa" class="d-none mt-2">
          <label class="btn btn-outline-primary w-100 rounded-3">
            <i class="bi bi-camera me-1"></i> Usar cámara del dispositivo
            <input type="file" id="inputCamaraNativa" accept="image/*" capture="user" class="d-none" onchange="usarCamaraNativa(event)">
          </label>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 d-flex gap-2">
        <button type="button" class="btn btn-light flex-fill" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary flex-fill" id="btnCapturar" onclick="capturarFoto()">
          <i class="bi bi-camera me-1"></i> Capturar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
  let streamCamara = null;
  let modalCamara = null;
  const inputFoto = document.getElementById('inputFotoPerfil');

  // ---------- Opción A: galería ----------
  function abrirGaleria(event) {
    if (event) event.preventDefault();
    inputFoto.click();
  }

  function mostrarVistaPreviaFoto(event) {
    const archivo = event.target.files && event.target.files[0];
    const preview = document.getElementById('vistaPreviaFoto');
    const guardar = document.getElementById('btnGuardarFoto');
    const info = document.getElementById('infoFoto');

    if (!archivo) return;

    if (!archivo.type.startsWith('image/')) {
      info.textContent = 'El archivo no es una imagen (JPG, PNG o WEBP).';
      info.className = 'form-text text-danger mt-1';
      preview.classList.add('d-none');
      guardar.classList.add('d-none');
      if (event.target.id === 'inputFotoPerfil') event.target.value = '';
      return;
    }
    if (archivo.size > 2 * 1024 * 1024) {
      info.textContent = 'La imagen supera el tamaño máximo de 2 MB.';
      info.className = 'form-text text-danger mt-1';
      preview.classList.add('d-none');
      guardar.classList.add('d-none');
      if (event.target.id === 'inputFotoPerfil') event.target.value = '';
      return;
    }

    preview.src = URL.createObjectURL(archivo);
    preview.classList.remove('d-none');
    guardar.classList.remove('d-none');
    info.textContent = 'Imagen lista para guardar.';
    info.className = 'form-text text-success mt-1';
  }

  // ---------- Opción B: cámara en tiempo real (MediaDevices) ----------
  function abrirCamara() {
    modalCamara = modalCamara || new bootstrap.Modal(document.getElementById('modalCamara'));
    document.getElementById('cajaVideo').classList.remove('d-none');
    document.getElementById('cajaErrorCamara').classList.add('d-none');
    document.getElementById('cajaFallbackNativa').classList.add('d-none');
    document.getElementById('btnCapturar').disabled = false;
    modalCamara.show();
    iniciarCamara();
  }

  function iniciarCamara() {
    cerrarStream();
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      camaraNoDisponible('La cámara no está disponible en este navegador.');
      return;
    }
    navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 1280 } },
      audio: false
    }).then((stream) => {
      streamCamara = stream;
      const video = document.getElementById('camaraVideo');
      video.srcObject = stream;
      video.play().catch(() => {});
    }).catch((err) => {
      const msg = (err && err.name === 'NotAllowedError')
        ? 'Bloqueaste el permiso de la cámara. Habilítalo en el navegador.'
        : 'No se pudo acceder a la cámara de este dispositivo.';
      camaraNoDisponible(msg);
    });
  }

  function camaraNoDisponible(mensaje) {
    document.getElementById('cajaVideo').classList.add('d-none');
    document.getElementById('cajaErrorCamara').classList.remove('d-none');
    document.getElementById('cajaFallbackNativa').classList.remove('d-none');
    document.getElementById('btnCapturar').disabled = true;
    document.querySelector('#cajaErrorCamara p.mb-1').textContent = mensaje;
  }

  function cerrarStream() {
    if (streamCamara) {
      streamCamara.getTracks().forEach((track) => track.stop());
      streamCamara = null;
    }
    const video = document.getElementById('camaraVideo');
    if (video) video.srcObject = null;
  }

  document.getElementById('modalCamara').addEventListener('hidden.bs.modal', cerrarStream);

  function capturarFoto() {
    const video = document.getElementById('camaraVideo');
    if (!video.videoWidth) return;

    // Recorte cuadrado centrado del video
    const lado = Math.min(video.videoWidth, video.videoHeight);
    const sx = (video.videoWidth - lado) / 2;
    const sy = (video.videoHeight - lado) / 2;
    const canvas = document.createElement('canvas');
    canvas.width = 480;
    canvas.height = 480;
    canvas.getContext('2d').drawImage(video, sx, sy, lado, lado, 0, 0, 480, 480);

    canvas.toBlob((blob) => {
      if (!blob) return;
      const archivo = new File([blob], 'foto_camara_' + Date.now() + '.jpg', { type: 'image/jpeg' });
      const dt = new DataTransfer();
      dt.items.add(archivo);
      inputFoto.files = dt.files;

      if (modalCamara) modalCamara.hide(); // 'hidden.bs.modal' detiene la cámara
      mostrarVistaPreviaFoto({ target: inputFoto });
    }, 'image/jpeg', 0.92);
  }

  // Fallback: foto tomada con la cámara nativa del dispositivo (móvil)
  function usarCamaraNativa(event) {
    const archivo = event.target.files && event.target.files[0];
    if (!archivo) return;
    const dt = new DataTransfer();
    dt.items.add(archivo);
    inputFoto.files = dt.files;
    if (modalCamara) modalCamara.hide();
    mostrarVistaPreviaFoto({ target: inputFoto });
  }
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>