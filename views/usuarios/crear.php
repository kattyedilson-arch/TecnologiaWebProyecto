<?php
// =========================================================
// VISTA: REGISTRO DE USUARIO (views/usuarios/crear.php)
// ---------------------------------------------------------
// Formulario de alta de cuentas (administrador, tutor o
// estudiante). Incluye validación en el cliente con los
// atributos HTML5 (pattern, minlength, required) y la clase
// needs-validation de Bootstrap. Al fallar la validación en
// servidor, los errores llegan en $errores y se muestran como
// alerta; los valores enviados se re-cargar en los inputs.
// Variables:
//   $roles (select de roles) y $errores (lista de errores)
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
requerirRol('administrador');
$tituloPagina = 'Nuevo Usuario - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';
?>

<div class="row justify-content-center">
  <div class="col-lg-9 col-xl-8">
    <!-- Banda de cabecera con migas de referencia académica -->
    <nav aria-label="breadcrumb mb-2">
      <ol class="breadcrumb mb-2 small fw-semibold">
        <li class="breadcrumb-item"><a href="/controllers/usuarios_listar.php" class="text-decoration-none">Usuarios</a></li>
        <li class="breadcrumb-item active">Nuevo Usuario</li>
      </ol>
    </nav>
    <div class="hero-band p-4 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge text-bg-light text-dark border px-3 py-1" style="font-size:.68rem; letter-spacing:.6px; text-transform:uppercase;">
              <i class="bi bi-journal-plus me-1"></i> Alta de Cuenta
            </span>
          </div>
          <h3 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-person-plus-fill"></i>
            <span>Registrar Nuevo Usuario</span>
          </h3>
          <p class="text-white-50 mb-0">Crea la cuenta de un administrador, tutor o estudiante de la comunidad académica.</p>
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

    <!-- Sección: información general -->
    <div class="card card-custom p-4 p-md-5 mb-4">
      <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
        <span class="stat-ico bg-indigo text-indigo" style="width:38px; height:38px; font-size:1rem;"><i class="bi bi-person-vcard"></i></span>
        <div>
          <h5 class="fw-bold mb-0">Datos del Usuario</h5>
          <small class="text-muted">Información básica y asignación de rol en el sistema.</small>
        </div>
      </div>
      <form method="POST" autocomplete="off" class="needs-validation" novalidate>
      <?= campoCsrf() ?>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Rol del Usuario *</label>
            <select name="id_rol" id="selRol" class="form-select rounded-3 py-2" required>
              <option value="" disabled <?= !isset($_POST['id_rol']) || $_POST['id_rol'] === '' ? 'selected' : '' ?>>Selecciona un rol...</option>
              <?php foreach ($roles as $r): ?>
                <option value="<?= $r['id_rol'] ?>" <?= (isset($_POST['id_rol']) && $_POST['id_rol'] == $r['id_rol']) ? 'selected' : '' ?>>
                  <?= ucfirst(htmlspecialchars($r['nombre_rol'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Debes seleccionar un rol.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Nombre *</label>
            <input type="text" name="nombre" class="form-control rounded-3 py-2"
                   value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]+" minlength="2" placeholder="Ej: Juan" required>
            <div class="invalid-feedback">Ingresa un nombre válido (solo letras).</div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Apellido *</label>
            <input type="text" name="apellido" class="form-control rounded-3 py-2"
                   value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>"
                   pattern="[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s'\-]+" minlength="2" placeholder="Ej: Pérez García" required>
            <div class="invalid-feedback">Ingresa un apellido válido (solo letras).</div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Correo Electrónico *</label>
            <input type="email" name="correo" class="form-control rounded-3 py-2"
                   value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>"
                   placeholder="ejemplo@upds.edu.bo" required>
            <div class="invalid-feedback">Ingresa un correo electrónico válido.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Nombre de Usuario *</label>
            <input type="text" name="usuario" class="form-control rounded-3 py-2"
                   value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
                   pattern="[a-zA-Z0-9_.]{3,50}" minlength="3" placeholder="usuario123" required>
            <div class="invalid-feedback">Mínimo 3 caracteres: letras, números, punto o guión bajo.</div>
          </div>

          <div class="col-md-6">
            <label class="form-label fw-semibold text-secondary small text-uppercase">Teléfono</label>
            <input type="tel" name="telefono" class="form-control rounded-3 py-2"
                   value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
                   pattern="[0-9+\s()\-]{7,20}" placeholder="Ej: 70000001">
            <div class="invalid-feedback">Ingresa un teléfono válido.</div>
          </div>
        </div>
    </div>

    <!-- Sección: datos académicos (solo estudiante) -->
    <div class="card card-custom p-4 p-md-5 mb-4">
      <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
        <span class="stat-ico text-warning" style="width:38px; height:38px; font-size:1rem; background:#fef3c7;"><i class="bi bi-mortarboard"></i></span>
        <div>
          <h5 class="fw-bold mb-0">Datos Académicos</h5>
          <small class="text-muted">Carrera y semestre. Solo aplica si el rol seleccionado es <b>Estudiante</b>.</small>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-6 d-none" id="bloqueCarrera">
          <label class="form-label fw-semibold text-secondary small text-uppercase">Carrera *</label>
          <select name="id_carrera" id="selCarrera" class="form-select rounded-3 py-2">
            <option value="" <?= empty($_POST['id_carrera']) ? 'selected' : '' ?>>Selecciona la carrera...</option>
            <?php foreach ($carreras as $c): ?>
              <option value="<?= $c['id_carrera'] ?>" <?= (isset($_POST['id_carrera']) && $_POST['id_carrera'] == $c['id_carrera']) ? 'selected' : '' ?>>
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
              <option value="<?= $i ?>" <?= ($_POST['semestre'] ?? 1) == $i ? 'selected' : '' ?>><?= $i ?>º</option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
    </div>

    <!-- Sección: contraseña -->
    <div class="card card-custom p-4 p-md-5">
      <div class="d-flex align-items-center gap-2 mb-4 pb-2 border-bottom">
        <span class="stat-ico bg-success bg-opacity-10 text-success" style="width:38px; height:38px; font-size:1rem;"><i class="bi bi-shield-lock"></i></span>
        <div>
          <h5 class="fw-bold mb-0">Seguridad de la Cuenta</h5>
          <small class="text-muted">La contraseña se almacenará cifrada con Bcrypt.</small>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-12">
          <label class="form-label fw-semibold text-secondary small text-uppercase">Contraseña Inicial *</label>
          <div class="input-group">
            <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-lock"></i></span>
            <input type="password" id="clave" name="clave" class="form-control rounded-end-3 py-2"
                   minlength="8" pattern="(?=.*[A-Za-z])(?=.*[0-9]).{8,}" placeholder="Mínimo 8 caracteres con letras y números" required>
          </div>
          <div class="invalid-feedback">La contraseña debe tener al menos 8 caracteres y combinar letras y números.</div>
          <div class="form-text">Se guardará encriptada con Bcrypt de forma segura.</div>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
        <a href="usuarios_listar.php" class="btn btn-light px-4 py-2 rounded-3">Cancelar</a>
        <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
          <i class="bi bi-save"></i>
          <span>Guardar Usuario</span>
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

  // Validación visual de Bootstrap en el cliente
  (() => {
    const form = document.querySelector('.needs-validation');
    if (!form) return;
    form.addEventListener('submit', (e) => {
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  })();
</script>

<?php include __DIR__ . '/../layouts/footer.php'; ?>