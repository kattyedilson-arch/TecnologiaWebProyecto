<?php
// =========================================================
// VISTA: EDICIÓN DE USUARIO (views/usuarios/editar.php)
// ---------------------------------------------------------
// Formulario para modificar una cuenta existente: rol, estado,
// datos personales y (opcionalmente) restablecer contraseña.
// Si se está editando la propia cuenta ($esMiCuenta) el select
// de estado queda bloqueado y se fuerza 'activo'.
// El JS verifica además que las contraseñas coincidan.
// Variables:
//   $usuario_actual (datos precargados), $roles, $errores
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('administrador');
$tituloPagina = 'Editar Usuario - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';
$esMiCuenta = (int)$usuario_actual['id_usuario'] === (int)($_SESSION['id_usuario'] ?? 0);
// Rol con el que se mostrará el formulario (el ya guardado o el recién enviado si hubo errores)
$rolDeVisual = $_POST['id_rol'] ?? $usuario_actual['id_rol'];
// Datos de carrera/semestre a precargar (ficha guardada o valores recién enviados)
$carreraDeVisual = $_POST['id_carrera'] ?? ($estudiante_ficha['id_carrera'] ?? '');
$semestreDeVisual = $_POST['semestre'] ?? ($estudiante_ficha['semestre'] ?? 1);
?>

<div class="row justify-content-center">
  <div class="col-lg-9 col-xl-8">
    <div class="hero-band p-4 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-pencil-square"></i>
            <span>Editar Usuario: <?= htmlspecialchars($usuario_actual['usuario']) ?></span>
          </h3>
          <p class="text-white-50 mb-0">Modifica la información de la cuenta y, si lo deseas, restablece su contraseña.</p>
        </div>
        <a href="usuarios_listar.php" class="btn btn-light d-flex align-items-center gap-1">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>
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

    <div class="card card-custom p-4 p-md-5">
      <form method="POST" autocomplete="off" class="needs-validation" novalidate>
      <?= campoCsrf() ?>
        <input type="hidden" name="id_usuario" value="<?= htmlspecialchars($usuario_actual['id_usuario']) ?>">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Rol del Usuario *</label>
            <select name="id_rol" id="selRol" class="form-select rounded-3 py-2" required>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id_rol'] ?>" <?= $r['id_rol'] == $rolDeVisual ? 'selected' : '' ?>>
                  <?= ucfirst(htmlspecialchars($r['nombre_rol'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-md-6 d-none" id="bloqueCarrera">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Carrera *</label>
            <select name="id_carrera" id="selCarrera" class="form-select rounded-3 py-2">
              <option value="" <?= $carreraDeVisual === '' ? 'selected' : '' ?>>Selecciona la carrera...</option>
              <?php foreach ($carreras as $c): ?>
                <option value="<?= $c['id_carrera'] ?>" <?= $c['id_carrera'] == $carreraDeVisual ? 'selected' : '' ?>>
                  <?= htmlspecialchars($c['nombre_carrera']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">De aquí saldrán las materias que verá el estudiante al solicitar tutorías.</div>
          </div>

          <div class="col-md-6 d-none" id="bloqueSemestre">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Semestre *</label>
            <select name="semestre" id="selSemestre" class="form-select rounded-3 py-2">
              <?php for ($i = 1; $i <= 12; $i++): ?>
                <option value="<?= $i ?>" <?= $semestreDeVisual == $i ? 'selected' : '' ?>><?= $i ?>º</option>
              <?php endfor; ?>
            </select>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Estado de la Cuenta *</label>
            <select name="estado" class="form-select rounded-3 py-2" <?= $esMiCuenta ? 'disabled' : '' ?> required>
              <option value="activo"   <?= $usuario_actual['estado'] === 'activo'   ? 'selected' : '' ?>>Activo</option>
              <option value="inactivo" <?= $usuario_actual['estado'] === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
            </select>
            <?php if ($esMiCuenta): ?>
              <input type="hidden" name="estado" value="activo">
              <div class="form-text">No puedes desactivar tu propia cuenta.</div>
            <?php endif; ?>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Nombre *</label>
            <input type="text" name="nombre" class="form-control rounded-3 py-2"
                   value="<?= htmlspecialchars($usuario_actual['nombre']) ?>"
                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]+" minlength="2" required>
            <div class="invalid-feedback">Ingresa un nombre válido (solo letras).</div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Apellido *</label>
            <input type="text" name="apellido" class="form-control rounded-3 py-2"
                   value="<?= htmlspecialchars($usuario_actual['apellido']) ?>"
                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]+" minlength="2" required>
            <div class="invalid-feedback">Ingresa un apellido válido (solo letras).</div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Correo Electrónico *</label>
            <input type="email" name="correo" class="form-control rounded-3 py-2"
                   value="<?= htmlspecialchars($usuario_actual['correo']) ?>" required>
            <div class="invalid-feedback">Ingresa un correo electrónico válido.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Nombre de Usuario *</label>
            <input type="text" name="usuario" class="form-control rounded-3 py-2"
                   value="<?= htmlspecialchars($usuario_actual['usuario']) ?>"
                   pattern="[a-zA-Z0-9_.]{3,50}" minlength="3" required>
            <div class="invalid-feedback">Mínimo 3 caracteres: letras, números, punto o guión bajo.</div>
          </div>

          <div class="col-md-12">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Teléfono</label>
            <input type="tel" name="telefono" class="form-control rounded-3 py-2"
                   value="<?= htmlspecialchars($usuario_actual['telefono'] ?? '') ?>"
                   pattern="[0-9+\s()\-]{7,20}" placeholder="Ej: 70000001">
            <div class="invalid-feedback">Ingresa un teléfono válido.</div>
          </div>

          <div class="col-12 pt-2">
            <div class="border-top pt-3">
              <h6 class="fw-bold small text-uppercase text-secondary mb-3"><i class="bi bi-shield-lock me-1 text-primary"></i>Restablecer Contraseña (Opcional)</h6>
              <div class="row g-3">
                <div class="col-md-6">
                  <input type="password" name="clave_nueva" class="form-control rounded-3 py-2"
                         minlength="8" pattern="(?=.*[A-Za-z])(?=.*[0-9]).{8,}" placeholder="Nueva contraseña (mín. 8, letras y números)">
                  <div class="invalid-feedback">Mínimo 8 caracteres combinando letras y números.</div>
                  <div class="form-text">Déjalo vacío para conservar la contraseña actual.</div>
                </div>
                <div class="col-md-6">
                  <input type="password" name="clave_conf" class="form-control rounded-3 py-2"
                         minlength="8" placeholder="Confirmar nueva contraseña">
                  <div class="invalid-feedback">Ambas contraseñas deben coincidir.</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
          <a href="usuarios_listar.php" class="btn btn-light px-4 py-2 rounded-3">Cancelar</a>
          <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-arrow-repeat"></i>
            <span>Actualizar Usuario</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
  // Mostrar carrera y semestre solo cuando el rol elegido sea "estudiante"
  (() => {
    const selRol = document.getElementById('selRol');
    const bloqueCarrera = document.getElementById('bloqueCarrera');
    const bloqueSemestre = document.getElementById('bloqueSemestre');
    const idRolEstudiante = <?= json_encode($idRolEstudiante ?? null) ?>;

    function actualizarBloques() {
      const esEstudiante = selRol.value !== '' && idRolEstudiante !== null && Number(selRol.value) === Number(idRolEstudiante);
      bloqueCarrera.classList.toggle('d-none', !esEstudiante);
      bloqueSemestre.classList.toggle('d-none', !esEstudiante);
      const selCarrera = document.getElementById('selCarrera');
      if (esEstudiante) {
        selCarrera.required = true;
      } else {
        selCarrera.required = false;
        selCarrera.value = '';
      }
    }

    selRol.addEventListener('change', actualizarBloques);
    actualizarBloques();
  })();

  (() => {
    const form = document.querySelector('.needs-validation');
    if (!form) return;
    form.addEventListener('submit', (e) => {
      const clave = form.querySelector('[name="clave_nueva"]');
      const conf  = form.querySelector('[name="clave_conf"]');
      if (clave.value && clave.value !== conf.value) {
        conf.setCustomValidity('Las contraseñas no coinciden');
      } else {
        conf.setCustomValidity('');
      }
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  })();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>