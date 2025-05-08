import {
  validateEmail,
  validatePassword,
  validateConfirmPassword,
  validateTerms,
  validateName,
} from './common/validation.js';
import { registerUser } from './common/api.js';
import { showOverlay, hideOverlay } from './common/spinner.js';

// Nettoie les anciens messages d'erreur
function clearErrors(form) {
  form.querySelectorAll('.register-form__error-message').forEach((el) => el.remove());
  form
    .querySelectorAll('input, button, .register-form__checkbox input')
    .forEach((el) => el.removeAttribute('aria-invalid'));
}

// Affiche un message d'erreur inline ou global
function displayError(target, message) {
  const isForm = target instanceof HTMLFormElement;
  const container = isForm
    ? target.querySelector('.register-form__global-message')
    : target.parentElement;
  const errorEl = document.createElement('div');
  errorEl.className = 'register-form__error-message';
  errorEl.textContent = message;

  if (!isForm) {
    target.setAttribute('aria-invalid', 'true');
    const id = `${target.id}-error`;
    errorEl.id = id;
    target.setAttribute('aria-describedby', id);
  }

  container.appendChild(errorEl);
}

// Déplace le focus sur le premier champ invalide
function focusFirstInvalid(form) {
  const first = form.querySelector('[aria-invalid="true"]');
  if (first) first.focus();
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.register-form');
  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearErrors(form);

    const fullnameInput = form.querySelector('#fullname');
    const emailInput = form.querySelector('#email');
    const passwordInput = form.querySelector('#password');
    const confirmInput = form.querySelector('#confirm-password');
    const termsInput = form.querySelector('#terms');

    const name = fullnameInput.value.trim();
    const email = emailInput.value.trim();
    const password = passwordInput.value;
    const confirmPassword = confirmInput.value;
    const termsAccepted = termsInput.checked;

    let hasError = false;

    // Validation du nom
    if (!validateName(name)) {
      displayError(
        fullnameInput,
        'Le nom doit contenir entre 3 et 15 lettres, sans caractères spéciaux'
      );
      hasError = true;
    }
    if (!validateEmail(email)) {
      displayError(emailInput, 'Adresse email invalide');
      hasError = true;
    }
    if (!validatePassword(password)) {
      displayError(
        passwordInput,
        'Le mot de passe doit contenir au moins 8 caractères, dont une majuscule, une minuscule, un chiffre et un caractère spécial'
      );
      hasError = true;
    }
    if (!validateConfirmPassword(password, confirmPassword)) {
      displayError(confirmInput, 'Les mots de passe ne correspondent pas');
      hasError = true;
    }
    if (!validateTerms(termsAccepted)) {
      displayError(form, "Vous devez accepter les conditions d'utilisation");
      hasError = true;
    }

    if (hasError) {
      focusFirstInvalid(form);
      return;
    }

    showOverlay(form);
    try {
      const result = await registerUser({ name, email, password });
      const globalMsg = form.querySelector('.register-form__global-message');
      globalMsg.textContent = result.message;
      globalMsg.classList.add('register-form__success-message');
      globalMsg.setAttribute('aria-live', 'polite');
      // Persistance du token JWT et redirection
      localStorage.setItem('token', result.data.token);
      window.location.href = '/';
    } catch (err) {
      if (err.errors) {
        Object.entries(err.errors).forEach(([field, msg]) => {
          const input = form.querySelector(`#${field}`) || form;
          displayError(input, msg);
        });
      } else if (err.message) {
        displayError(form, err.message);
      } else {
        displayError(form, 'Une erreur est survenue. Veuillez réessayer.');
      }
      focusFirstInvalid(form);
    } finally {
      hideOverlay(form);
    }
  });
});
