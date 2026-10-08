/* Native dialog preserves Escape, focus containment, and focus return. */
(() => {
  let opener = null;
  document.querySelectorAll('[data-bdqn-open]').forEach(button => {
    button.addEventListener('click', () => {
      const dialog = document.getElementById(button.dataset.bdqnOpen);
      if (!dialog || typeof dialog.showModal !== 'function') return;
      opener = button;
      dialog.showModal();
      dialog.querySelector('[data-bdqn-close]').focus();
    });
  });
  document.querySelectorAll('#bdqn-history').forEach(dialog => {
    dialog.querySelector('[data-bdqn-close]').addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => { if (opener) opener.focus(); });
  });
})();
