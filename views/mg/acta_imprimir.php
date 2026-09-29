<?php
// =========================================================
// VISTA PRINTS: ACTA DE CALIFICACIÓN (views/mg/acta_imprimir.php)
// ---------------------------------------------------------
// Documento oficial imprimible (HTML plano + CSS de impresión).
// Si el acta no está firmada se superpone marca de BORRADOR.
// Variables: $declaracion, $acta, $notasProc, $jurado.
// =========================================================
$firmada = ($acta['estado'] ?? '') === 'firmada';
$resultadoTexto = ['aprobado' => 'APROBADO', 'reprobado' => 'REPROBADO', 'pendiente' => 'EN EVALUACIÓN'][$acta['resultado']] ?? 'EN EVALUACIÓN';
$hoy = date('d/m/Y');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acta de calificación — UPDS</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  body { font-family: 'Times New Roman', Times, serif; color: #111; }
  .hoja { max-width: 900px; margin: 0 auto; padding: 40px 48px; }
  .encabezado { border-bottom: 2px solid #111; padding-bottom: 14px; margin-bottom: 24px; }
  .titulo-doc { font-size: 1.4rem; font-weight: bold; letter-spacing: 1px; }
  .fila-dato b { display: inline-block; min-width: 190px; }
  .fila-dato { margin-bottom: 4px; }
  .tabla-acta th { background: #f2f2f2; text-align: center; border: 1px solid #111; }
  .tabla-acta td { border: 1px solid #111; }
  .tabla-acta th, .tabla-acta td { padding: 8px 12px; font-size: .95rem; }
  .nota-final { font-size: 1.15rem; }
  .marca-borrador {
    position: fixed; inset: 30% 10% auto;
    transform: rotate(-18deg); text-align: center;
    font-size: 4.5rem; font-weight: bold; letter-spacing: 6px;
    color: rgba(220,53,69,.18); border: 8px solid rgba(220,53,69,.35);
    padding: 30px 20px; pointer-events: none; z-index: 5;
  }
  .firmas td { height: 110px; width: 50%; text-align: center; vertical-align: bottom; padding: 0 30px 6px; }
  .firma-linea { border-top: 1px solid #444; display: block; margin-top: 54px; padding-top: 6px; font-size: .85rem; }
  @media print {
    .no-print { display: none !important; }
    body { background: #fff; }
  }
</style>
</head>
<body>
  <?php if (!$firmada): ?><div class="marca-borrador no-print">BORRADOR</div><?php endif; ?>

  <div class="hoja">
    <div class="d-flex justify-content-end mb-2 no-print">
      <a href="/controllers/mg_acta.php?id=<?= (int)$declaracion['id_declaracion'] ?>" class="btn btn-light btn-sm border me-2"><i class="bi bi-arrow-left"></i> Volver</a>
      <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
    </div>

    <div class="encabezado text-center">
      <div class="fw-bold">UNIVERSIDAD PRIVADA DOMINGO SAVIO</div>
      <div>GERENCIA DE MODALIDADES DE GRADUACIÓN (MG)</div>
      <div class="titulo-doc mt-2">ACTA DE CALIFICACIÓN DE DEFENSA</div>
      <div class="small">Código: AC-MG-<?= str_pad((string)(int)$declaracion['id_declaracion'], 3, '0', STR_PAD_LEFT) ?> • Fecha de emisión: <?= $hoy ?></div>
    </div>

    <table class="fila-dato"><tr><td><b>Estudiante:</b></td><td><?= e(trim($declaracion['nombre'] . ' ' . $declaracion['apellido'])) ?></td></tr></table>
    <table class="fila-dato"><tr><td><b>Carrera:</b></td><td><?= e($declaracion['carrera'] ?? '—') ?></td></tr></table>
    <table class="fila-dato"><tr><td><b>Modalidad:</b></td><td><?= e($declaracion['modalidad_codigo'] . ' — ' . $declaracion['modalidad_nombre']) ?></td></tr></table>
    <table class="fila-dato"><tr><td><b>Título / tema:</b></td><td><?= e($declaracion['titulo_proyecto'] ?: '—') ?></td></tr></table>
    <table class="fila-dato"><tr><td><b>Empresa / organización:</b></td><td><?= e($declaracion['empresa_org'] ?: '—') ?></td></tr></table>
    <table class="fila-dato"><tr><td><b>Tutor facultativo:</b></td><td><?= e($declaracion['tutor_facultativo'] ?: '—') ?></td></tr></table>
    <table class="fila-dato"><tr><td><b>Periodo:</b></td><td><?= e($declaracion['periodo_nombre']) ?></td></tr></table>
    <?php if (!empty($acta['fecha_defensa'])): ?>
      <table class="fila-dato"><tr><td><b>Fecha de la defensa:</b></td><td><?= date('d/m/Y H:i', strtotime($acta['fecha_defensa'])) ?></td></tr></table>
    <?php endif; ?>
    <?php if (!empty($acta['lugar'])): ?>
      <table class="fila-dato"><tr><td><b>Lugar:</b></td><td><?= e($acta['lugar']) ?></td></tr></table>
    <?php endif; ?>

    <h6 class="mt-4 mb-2 text-center fw-bold">TRIBUNAL EVALUADOR</h6>
    <table class="table table-bordered tabla-acta">
      <thead>
        <tr>
          <th>N.º</th><th>Jurado</th><th>Rol</th><th style="width:120px;">Nota (0-<?= (int)$notaMax ?>)</th><th>Comentario</th>
        </tr>
      </thead>
      <tbody>
        <?php $idx = 1; ?>
        <?php foreach ($notasProc as $n): ?>
          <tr>
            <td class="text-center"><?= $idx++ ?></td>
            <td><?= e($n['nombre_completo']) ?></td>
            <td class="text-center text-uppercase"><?= e($n['rol_jurado']) ?></td>
            <td class="text-center fw-semibold"><?= number_format((float)$n['nota'], 2) ?></td>
            <td><?= e($n['comentario'] ?: '—') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="row mt-3">
      <div class="col-6">
        <small class="text-muted">Observaciones:</small>
        <p class="small mb-0 mt-1" style="min-height:32px;"><?= e($acta['observaciones'] ?: '—') ?></p>
      </div>
      <div class="col-6 text-center px-4 pt-2">
        <div class="d-flex justify-content-center align-items-center gap-2">
          <span class="fw-semibold">Nota final:</span>
          <span class="nota-final fw-bold"><?= number_format((float)$acta['nota_final'], 2) ?></span>
        </div>
        <div class="badge <?= $acta['resultado'] === 'aprobado' ? 'text-bg-success' : 'text-bg-danger' ?> mt-1 px-3"><?= $resultadoTexto ?></div>
      </div>
    </div>

    <?php
    $nombreCoordinador = trim(($acta['firmante_nombre'] ?? '') . ' ' . ($acta['firmante_apellido'] ?? ''));
    $nombrePresidente = trim((string)($acta['presidente'] ?? ''));
    ?>
    <div class="row mt-5">
      <div class="col-4">
        <span class="firma-linea text-center"><?= e($nombrePresidente) ?: '__________________________' ?><br>PRESIDENTE DEL TRIBUNAL</span>
      </div>
      <div class="col-4">
        <span class="firma-linea text-center"><?= e($nombreCoordinador) ?: '__________________________' ?><br>COORDINADOR DE MG</span>
      </div>
      <div class="col-4">
        <span class="firma-linea text-center">__________________________<br>FIRMA / RÚBRICA</span>
      </div>
    </div>

    <div class="mt-4 pt-3 small text-muted text-center" style="border-top:1px solid #ddd;">
      Documento generado por el Sistema de Gestión de Modalidades de Grado — UPDS
      <?php if ($firmada): ?> • Firmado el <?= date('d/m/Y H:i', strtotime($acta['fecha_firma'])) ?> por <?= e(ucfirst($acta['firmante_nombre'] ?? '') . ' ' . ucfirst($acta['firmante_apellido'] ?? '')) ?>.<?php endif; ?>
    </div>
  </div>
</body>
</html>