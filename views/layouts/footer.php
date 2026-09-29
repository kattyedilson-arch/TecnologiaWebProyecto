<!--
===========================================================
VISTA PARCIAL: PIE DE PÁGINA (views/layouts/footer.php)
-----------------------------------------------------------
Cierra la estructura del layout profesional (div.app-content
y main.app-main), imprime el footer con el crédito de la
materia y carga el JS (Bootstrap, alternar sidebar y los
helpers: confirmarEliminacion con SweetAlert2 + auto-ocultado
del mensaje flash).
===========================================================
-->

<?php if (isset($_SESSION['id_usuario'])): ?>
    </div><!-- /.app-content -->

    <footer class="py-3 flex-shrink-0" style="border-top:1px solid #e6eaf2; background:#fff;">
      <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 px-4 text-muted" style="font-size:.82rem;">
        <div>
          <i class="bi bi-mortarboard-fill text-primary me-1"></i>
          <strong>Sistema Web de Apoyo Académico para Tutorías</strong> &bull; &copy; <?= date('Y') ?> UPDS
        </div>
        <div class="small">Materia de Tecnologías Web &bull; <i class="bi bi-shield-check me-1"></i>Hecho con PHP + Vue 3</div>
      </div>
    </footer>
  </main><!-- /.app-main -->
<?php else: ?>
  </main>
<?php endif; ?>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- JS compartido (sidebar, confirmaciones, flash) -->
<script src="/assets/js/app.js"></script>
</body>
</html>