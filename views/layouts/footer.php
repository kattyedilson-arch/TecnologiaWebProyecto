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

<script>
  // Alternar el sidebar (escritorio: colapsa a iconos; móvil: off-canvas)
  (function () {
    var sidebar = document.getElementById('appSidebar');
    var topbar = document.getElementById('appTopbar');
    var mainEl = document.getElementById('appMain');
    var btn = document.getElementById('btnSidebarToggle');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!sidebar || !btn) return;

    function isMobile() { return window.innerWidth <= 991.98; }

    btn.addEventListener('click', function () {
      if (isMobile()) {
        sidebar.classList.toggle('open');
        backdrop.classList.toggle('show');
      } else {
        sidebar.classList.toggle('collapsed');
        topbar && topbar.classList.toggle('collapsed-sidebar');
        mainEl && mainEl.classList.toggle('collapsed-sidebar');
      }
    });
    backdrop && backdrop.addEventListener('click', function () {
      sidebar.classList.remove('open');
      backdrop.classList.remove('show');
    });
  })();

  // Helper para confirmación de eliminación con SweetAlert2
  function confirmarEliminacion(url, mensaje = '¿Estás seguro de eliminar este registro?') {
    Swal.fire({
      title: '¿Confirmar eliminación?',
      text: mensaje,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc2626',
      cancelButtonColor: '#64748b',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      customClass: { popup: 'rounded-4' }
    }).then((result) => {
      if (result.isConfirmed) {
        window.location.href = url;
      }
    });
  }

  // Mensaje flash mostrado como notificación flotante
  document.addEventListener('DOMContentLoaded', function () {
    const banner = document.querySelector('[data-flash-toast]');
    if (banner) {
      setTimeout(() => { banner.style.transition = 'opacity .4s'; banner.style.opacity = '0'; }, 3500);
    }
  });
</script>
</body>
</html>