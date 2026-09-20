<?php
// =========================================================
// VISTA: EVALUAR TUTORÍA (views/tutorias/evaluar.php)
// ---------------------------------------------------------
// Formulario para que el estudiante califique una sesión ya
// realizada. Muestra un resumen de la tutoría (materia,
// docente, fecha y horario) y permite elegir de 1 a 5
// estrellas y dejar un comentario opcional.
// El formulario hace POST al propio controlador
// (controllers/tutorias_evaluar.php con id_tutoria y
// calificacion). Si hubo errores de validación llegan en
// $errores. La selección de estrellas se resalta con CSS puro.
// Variables del controlador: $tutoria y $errores
// =========================================================
require_once __DIR__ . '/../../includes/verificar_sesion.php';
require_once __DIR__ . '/../../includes/funciones.php';
$tituloPagina = 'Evaluar Tutoría - Sistema de Tutorías';
include __DIR__ . '/../layouts/header.php';

// Valores actuales (precargados si ya existía evaluación o hubo error)
$califActual = (int)($tutoria['calificacion'] ?? 0);
$comentarioActual = $tutoria['ev_comentario'] ?? '';
?>

<div class="row justify-content-center">
  <div class="col-lg-7 col-xl-6">
    <div class="hero-band p-4 mb-4">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
          <h3 class="fw-bold text-white mb-1 d-flex align-items-center gap-2">
            <i class="bi bi-star-fill"></i>
            <span>Evaluar Sesión de Tutoría</span>
          </h3>
          <p class="text-white-50 mb-0">Tu opinión ayuda a mejorar la calidad del apoyo académico.</p>
        </div>
        <a href="../views/estudiante/panel.php" class="btn btn-light d-flex align-items-center gap-1">
          <i class="bi bi-arrow-left"></i> Volver
        </a>
      </div>
    </div>

    <?php if (!empty($errores)): ?>
      <div class="alert alert-danger py-2 px-3 rounded-3 shadow-sm mb-4">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Corrige lo siguiente:</div>
        <ul class="mb-0 ps-3 small">
          <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <!-- Resumen de la tutoría -->
    <div class="card card-custom p-4 mb-4">
      <h6 class="fw-bold text-uppercase small text-secondary mb-3"><i class="bi bi-calendar-check me-1"></i>Datos de la Sesión</h6>
      <div class="row g-3 small">
        <div class="col-md-6">
          <div class="text-muted">Materia</div>
          <div class="fw-semibold text-primary"><?= htmlspecialchars($tutoria['nombre_materia']) ?></div>
        </div>
        <div class="col-md-6">
          <div class="text-muted">Docente Tutor</div>
          <div class="fw-semibold text-dark">Prof. <?= htmlspecialchars($tutoria['tut_nombre'] . ' ' . $tutoria['tut_apellido']) ?></div>
        </div>
        <div class="col-md-6">
          <div class="text-muted">Fecha</div>
          <div class="fw-semibold text-dark"><?= date('d/m/Y', strtotime($tutoria['fecha'])) ?></div>
        </div>
        <div class="col-md-6">
          <div class="text-muted">Horario</div>
          <div class="fw-semibold text-dark"><?= substr($tutoria['hora_inicio'], 0, 5) ?> - <?= substr($tutoria['hora_fin'], 0, 5) ?></div>
        </div>
      </div>
    </div>

    <!-- Formulario de evaluación -->
    <div class="card card-custom p-4 p-md-5">
      <form method="POST" class="needs-validation" novalidate>
      <?= campoCsrf() ?>
        <input type="hidden" name="id_tutoria" value="<?= (int)$tutoria['id_tutoria'] ?>">

        <div class="text-center mb-4">
          <label class="form-label fw-semibold text-secondary small text-uppercase d-block mb-2">Calificación *</label>
          <div class="star-rating">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" name="calificacion" id="star<?= $i ?>" value="<?= $i ?>"
                     <?= $califActual === $i ? 'checked' : '' ?> required>
              <label for="star<?= $i ?>" title="<?= $i ?> estrella(s)"><i class="bi bi-star-fill"></i></label>
            <?php endfor; ?>
          </div>
          <div class="invalid-feedback d-block">Selecciona una calificación de 1 a 5 estrellas.</div>
        </div>

        <div class="mb-4">
          <label class="form-label fw-semibold text-secondary small text-uppercase">Comentario (Opcional)</label>
          <textarea name="comentario" class="form-control rounded-3" rows="3" maxlength="500"
                    placeholder="Cuéntanos cómo fue la sesión, si resolvió tus dudas, etc."><?= htmlspecialchars($comentarioActual) ?></textarea>
          <div class="form-text">Máximo 500 caracteres.</div>
        </div>

        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
          <a href="../views/estudiante/panel.php" class="btn btn-light px-4 py-2 rounded-3">Cancelar</a>
          <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 shadow-sm d-flex align-items-center gap-2">
            <i class="bi bi-send-fill"></i>
            <span>Enviar Evaluación</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<style>
  /* Selector de estrellas: se generan de 5 a 1 y se invierten con row-reverse
     para que el resaltado por ~ funcione en orden visual correcto. */
  .star-rating {
    display: inline-flex;
    flex-direction: row-reverse;
    gap: .25rem;
  }
  .star-rating input { display: none; }
  .star-rating label {
    font-size: 2.2rem;
    color: #cbd5e1;
    cursor: pointer;
    transition: color .15s ease, transform .1s ease;
  }
  .star-rating label:hover { transform: scale(1.1); }
  .star-rating input:checked ~ label,
  .star-rating label:hover,
  .star-rating label:hover ~ label {
    color: #f59e0b;
  }
</style>

<script>
  // Validación visual de Bootstrap
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
