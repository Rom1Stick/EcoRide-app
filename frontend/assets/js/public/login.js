import { api } from '../common/api.js';

document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('.form-login');
  const errorContainer = document.querySelector('.form-login__error');

  if (!form) return;

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    errorContainer.textContent = '';

    // Récupération des données du formulaire
    const data = {
      email: form.elements.email.value,
      password: form.elements.password.value,
      remember: form.elements.remember?.checked || false,
    };

    try {
      const result = await api.post('/login', data);
      if (result.error) {
        throw new Error(result.error);
      }
      // Redirection vers le dashboard
      window.location.href = './dashboard.html';
    } catch (err) {
      errorContainer.textContent = err.message || 'Erreur de connexion. Veuillez réessayer.';
    }
  });
});
