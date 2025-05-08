export function showOverlay(form) {
  const overlay = form.querySelector('.register-form__overlay');
  if (overlay) {
    overlay.classList.remove('hidden');
    overlay.setAttribute('aria-hidden', 'false');
  }
}

export function hideOverlay(form) {
  const overlay = form.querySelector('.register-form__overlay');
  if (overlay) {
    overlay.classList.add('hidden');
    overlay.setAttribute('aria-hidden', 'true');
  }
}
