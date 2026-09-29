/* =========================================================
   JS COMPARTIDO DEL SISTEMA (app.js)
   ---------------------------------------------------------
   - Alternar el sidebar (escritorio: colapsa a iconos; móvil: off-canvas)
   - Confirmaciones con SweetAlert2 (confirmarEliminacion,
     mostrarConfirmacion, confirmarEnlace y formularios data-confirm)
   - Auto-ocultado del mensaje flash
   - Campanita: "Marcar leídas" por AJAX sin recargar la página
   Se carga desde views/layouts/footer.php en las páginas internas.
   ========================================================= */

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

// Confirmación estilizada reutilizable (SweetAlert2, paleta UPDS)
function mostrarConfirmacion(opciones) {
  const o = Object.assign({
    titulo: '¿Confirmar acción?',
    texto: '¿Estás seguro de realizar esta acción?',
    icono: 'question',
    textoConfirmar: 'Sí, continuar',
    color: '#1e40af'
  }, opciones || {});

  return Swal.fire({
    title: o.titulo,
    html: o.texto,
    icon: o.icono,
    showCancelButton: true,
    confirmButtonColor: o.color,
    cancelButtonColor: '#64748b',
    confirmButtonText: o.textoConfirmar,
    cancelButtonText: 'Cancelar',
    customClass: { popup: 'rounded-4' }
  }).then((result) => ({ confirmado: result.isConfirmed }));
}

// Confirmación para enlaces (navega solo si el usuario confirma)
function confirmarEnlace(url, opciones) {
  mostrarConfirmacion(opciones).then(({ confirmado }) => {
    if (confirmado) window.location.assign(url);
  });
  return false;
}

// Intercepta envíos de formularios marcados con data-confirm
// y los confirma con un modal estilizado en vez del confirm() nativo.
document.addEventListener('submit', function (e) {
  const form = e.target;
  if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-confirm')) return;

  e.preventDefault();
  mostrarConfirmacion({
    titulo: form.getAttribute('data-confirm-title') || '¿Confirmar acción?',
    texto: form.getAttribute('data-confirm') || '¿Estás seguro de realizar esta acción?',
    icono: form.getAttribute('data-confirm-icon') || 'question',
    textoConfirmar: form.getAttribute('data-confirm-text') || 'Sí, continuar',
    color: form.getAttribute('data-confirm-color') || '#1e40af'
  }).then(({ confirmado }) => {
    if (confirmado) form.submit();
  });
});

// Mensaje flash mostrado como notificación flotante
document.addEventListener('DOMContentLoaded', function () {
  const banner = document.querySelector('[data-flash-toast]');
  if (banner) {
    setTimeout(() => { banner.style.transition = 'opacity .4s'; banner.style.opacity = '0'; }, 3500);
  }
});

// Campanita de notificaciones: "Marcar leídas" se envía por AJAX para no
// recargar la página. El endpoint responde JSON con el contador de no
// leídas, que se usa para refrescar el badge y la lista del dropdown.
(function () {
  const form = document.getElementById('notifFormMarcarTodas');
  if (!form) return;

  function refrescarCampanita(noLeidas) {
    const badge = document.getElementById('notifBadge');
    if (badge) {
      badge.textContent = noLeidas > 99 ? '99+' : String(noLeidas);
      badge.hidden = noLeidas === 0;
    }
    if (noLeidas === 0) {
      // Se retiran los puntos azules de aviso pendiente
      document.querySelectorAll('#notifAcciones ~ * .badge.rounded-circle, .dropdown-menu .badge.rounded-circle')
        .forEach(function (punto) { punto.remove(); });
    }
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    const boton = form.querySelector('button[type="submit"]');
    if (boton) boton.disabled = true;

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin',
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
    })
      .then(function (r) {
        return r.json().then(function (datos) { return { ok: r.ok, datos: datos }; });
      })
      .then(function (res) {
        if (!res.ok) throw new Error((res.datos && res.datos.mensaje) || 'No se pudieron marcar las notificaciones.');
        refrescarCampanita(res.datos.noLeidas || 0);
        form.remove();
      })
      .catch(function (err) {
        if (boton) boton.disabled = false;
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            title: 'No se pudo completar',
            text: err.message,
            icon: 'error',
            customClass: { popup: 'rounded-4' }
          });
        }
      });
  });
})();