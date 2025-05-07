import { api } from '../common/api.js';

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.form-register');
  const errorContainer = document.querySelector('.form-register__error');

  if (!form) return;

  const errorTexts = form.querySelectorAll('[data-error]');

  // Réinitialiser les messages d'erreur
  function resetErrors() {
    errorContainer.textContent = '';
    errorTexts.forEach((el) => (el.textContent = ''));
  }

  // Valider les données
  function validate(data) {
    const errors = {};

    if (!data.name || data.name.length < 2) {
      errors.name = 'Le nom doit comporter au moins 2 caractères';
    }

    if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
      errors.email = 'Veuillez saisir un email valide';
    }

    if (!data.password || data.password.length < 8) {
      errors.password = 'Le mot de passe doit comporter au moins 8 caractères';
    }

    if (data.password !== data.passwordConfirm) {
      errors.passwordConfirm = 'Les mots de passe ne correspondent pas';
    }

    return errors;
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    resetErrors();

    const data = {
      name: form.elements.name.value,
      email: form.elements.email.value,
      password: form.elements.password.value,
      passwordConfirm: form.elements.passwordConfirm.value,
    };

    // Validation côté client
    const validationErrors = validate(data);

    if (Object.keys(validationErrors).length > 0) {
      // Afficher les erreurs de validation
      Object.entries(validationErrors).forEach(([field, message]) => {
        const errorEl = form.querySelector(`[data-error="${field}"]`);
        if (errorEl) errorEl.textContent = message;
      });
      return;
    }

    try {
      const result = await api.post('/register', data);
      if (result.error) {
        throw new Error(result.error);
      }
      // Redirection après inscription réussie
      window.location.href = 'login.html';
    } catch (err) {
      errorContainer.textContent = err.message || "Une erreur est survenue lors de l'inscription";
    }
  });
});
