<?php
// =========================================================
// VISTA: DOCUMENTO VER/IMPRIMIR (views/mg/documento_ver.php)
// ---------------------------------------------------------
// HU-027. Hoja A4 profesional con el snapshot almacenado del
// documento y botón de impresión. El contenido ya viene
// renderizado y escapado desde la generación.
// Variables: $documento (controllers/mg_documento_ver.php)
// =========================================================
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($documento['numero_correlativo'] ?? $documento['tipo']) ?> · UPDS</title>
  <style>
    * { box-sizing: border-box; }
    body { font-family: 'Segoe UI', Arial, sans-serif; background: #f1f5f9; margin: 0; }
    .toolbar { padding: 14px 20px; background: #fff; border-bottom: 1px solid #e2e8f0; display: flex; gap: 10px; align-items: center; position: sticky; top: 0; z-index: 10; }
    .toolbar .btn { padding: 8px 16px; border-radius: 8px; font-size: .9rem; text-decoration: none; border: 1px solid #e2e8f0; color: #475569; background: #fff; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
    .toolbar .btn-primary { background: #2563eb; border-color: #2563eb; color: #fff; }
    .toolbar .meta { margin-left: auto; font-size: .8rem; color: #64748b; }
    .hoja {
      width: 210mm; min-height: 297mm; margin: 24px auto; background: #fff;
      padding: 25mm 22mm; box-shadow: 0 10px 30px rgba(0,0,0,.12);
      font-size: 13px; line-height: 1.55; color: #111827; position: relative;
    }
    .carta-head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #1e3a8a; padding-bottom: 14px; margin-bottom: 26px; }
    .carta-head .logo b { font-size: 17px; color: #1e3a8a; display: block; }
    .carta-head .logo span { font-size: 11px; color: #475569; }
    .carta-head .correlativo { text-align: right; font-size: 12px; color: #475569; }
    .carta-head .correlativo b { color: #1e3a8a; }
    .sello-provisional { position: absolute; top: 160px; right: -40px; transform: rotate(18deg); border: 3px solid #dc2626; color: #dc2626; padding: 6px 26px; font-weight: 800; font-size: 22px; letter-spacing: 2px; opacity: .22; }
    @media print {
      body { background: #fff; }
      .toolbar { display: none !important; }
      .hoja { box-shadow: none; margin: 0; }
      @page { size: A4; margin: 0; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <a class="btn" href="javascript:window.print()">🖨 Imprimir</a>
    <span class="btn">📄 <?= e($documento['tipo']) ?></span>
    <span class="meta"><?= e($documento['numero_correlativo'] ?: '') ?> · <?= date('d/m/Y H:i', strtotime($documento['fecha_generacion'])) ?></span>
  </div>

  <div class="hoja">
    <div class="sello-provisional">PROVISIONAL</div>
    <div class="carta-head">
      <div class="logo">
        <b>Universidad Privada Domingo Savio</b>
        <span>Dirección de Tecnologías Web · Modalidades de Grado</span>
      </div>
      <div class="correlativo">
        Carta N°<br><b><?= e($documento['numero_correlativo'] ?: '—') ?></b>
      </div>
    </div>
    <?= $documento['contenido_snapshot'] ?>
    <div style="margin-top:34px; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 8px;">
      Documento generado electrónicamente por el sistema de Gestión de Tutorías UPDS.
      El correlativo <?= e($documento['numero_correlativo'] ?: '') ?> queda registrado y puede verificarse en su expediente.
    </div>
  </div>
</body>
</html>