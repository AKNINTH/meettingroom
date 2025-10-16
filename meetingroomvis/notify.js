// Simple notification module
(function(window){
  function createOverlay(type, title, message) {
    const overlay = document.createElement('div');
    overlay.className = 'notify-overlay';
    overlay.innerHTML = `
      <div class="notify-box ${type || 'info'}" role="dialog" aria-live="assertive">
        <button class="notify-close" aria-label="close">&times;</button>
        <div class="notify-title">${title || ''}</div>
        <div class="notify-message">${message || ''}</div>
        <div class="notify-actions"><button class="notify-ok">ตกลง</button></div>
      </div>
    `;
    overlay.querySelector('.notify-close').addEventListener('click', () => overlay.remove());
    overlay.querySelector('.notify-ok').addEventListener('click', () => overlay.remove());
    overlay.addEventListener('click', (e) => { if (e.target === overlay) overlay.remove(); });
    return overlay;
  }

  function showNotification({type = 'info', title = '', message = '', toast = false, timeout = 0} = {}) {
    if (toast) {
      // create toast container if not present
      let container = document.querySelector('.notify-toast-container');
      if (!container) {
        container = document.createElement('div');
        container.className = 'notify-toast-container';
        document.body.appendChild(container);
      }
      const toast = document.createElement('div');
      toast.className = `notify-toast ${type}`;
      toast.innerHTML = `<div class="notify-title">${title}</div><div class="notify-message">${message}</div>`;
      container.appendChild(toast);
      if (timeout > 0) setTimeout(() => toast.remove(), timeout);
      return;
    }
    const overlay = createOverlay(type, title, message);
    document.body.appendChild(overlay);
  }

  // read ?msg & ?type from query string and show an inline modal if present
  function checkQueryAndShow() {
    const params = new URLSearchParams(window.location.search);
    const msg = params.get('msg');
    const type = params.get('type') || 'info';
    const title = params.get('title') || '';
    if (msg) {
      try {
        // decode + replace plus
        const decoded = decodeURIComponent(msg.replace(/\+/g,' '));
        showNotification({type, title, message: decoded});
        // remove params from URL without reloading
        params.delete('msg'); params.delete('type'); params.delete('title');
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        window.history.replaceState({}, document.title, newUrl);
      } catch (err) {
        // ignore
      }
    }
  }

  window.notify = { showNotification, checkQueryAndShow };
  document.addEventListener('DOMContentLoaded', checkQueryAndShow);
})(window);
