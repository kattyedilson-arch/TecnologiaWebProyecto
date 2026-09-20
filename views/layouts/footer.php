<!--
===========================================================
VISTA PARCIAL: PIE DE PÁGINA (views/layouts/footer.php)
-----------------------------------------------------------
Cierra el <main> abierto en header.php, imprime el footer
con el crédito de la materia y carga el JS de Bootstrap.
Incluye dos utilidades globales en JavaScript:
  1) confirmarEliminacion(url, mensaje): cuadro de diálogo
     SweetAlert2 antes de borrar un registro (la vista llama
     con onclick="confirmarEliminacion('...')").
  2) Auto-ocultamiento del mensaje flash (data-flash-toast)
     tras 3.5 segundos.
===========================================================
-->
</main>

<footer class="bg-white border-top py-4 mt-auto">
  <div class="container">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 text-muted" style="font-size: 0.85rem;">
      <div>
        <i class="bi bi-mortarboard-fill text-primary me-1"></i>
        <strong>Sistema Web de Apoyo Académico para Tutorías</strong> &bull; &copy; <?= date('Y') ?> UPDS
      </div>
      <div class="small">Materia de Tecnologías Web &bull; <i class="bi bi-shield-check me-1"></i>Hecho con PHP + Bootstrap</div>
    </div>
  </div>
</footer>

<!-- Bootstrap 5 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
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
  document.addEventListener('DOMContentLoaded', function() {
    const banner = document.querySelector('[data-flash-toast]');
    if (banner) {
      setTimeout(() => { banner.style.transition = 'opacity .4s'; banner.style.opacity = '0'; }, 3500);
    }
  });
</script>
</body>
</html>