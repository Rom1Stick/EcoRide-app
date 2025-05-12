export async function initAuthUI() {
  // Ne pas interroger l'API si pas de token JWT en localStorage
  const storedToken = localStorage.getItem('auth_token');
  if (!storedToken) {
    return false;
  }
  try {
    // Préparer l'en-tête Authorization
    const headers = { Authorization: `Bearer ${storedToken}` };
    const response = await fetch('/api/users/me', {
      method: 'GET',
      credentials: 'include',
      headers,
    });
    // Redirection si pas authentifié
    if (response.status === 401) {
      localStorage.removeItem('auth_token');
      window.location.href = '/pages/public/login.html';
      return false;
    }
    const result = await response.json();
    if (!response.ok || result.error || !result.data) {
      localStorage.removeItem('auth_token');
      window.location.href = '/pages/public/login.html';
      return false;
    }

    // Afficher la navigation utilisateur
    const navRegister = document.getElementById('nav-register');
    const navLogin = document.getElementById('nav-login');
    if (navRegister) navRegister.style.display = 'none';
    if (navLogin) navLogin.style.display = 'none';
    const navUser = document.getElementById('nav-user');
    const navLogout = document.getElementById('nav-logout');
    if (navUser && navLogout) {
      const greeting = document.getElementById('user-greeting');
      // Utiliser l'email retourné
      greeting.textContent = `Bonjour, ${result.data.email || ''}`;
      navUser.style.display = '';
      navLogout.style.display = '';
    }
  } catch (err) {
    console.warn('User not authenticated', err);
    localStorage.removeItem('auth_token');
    window.location.href = '/pages/public/login.html';
    return false;
  }

  // Gestion de la déconnexion
  const btn = document.getElementById('logout-btn');
  if (btn) {
    btn.addEventListener('click', async () => {
      await fetch('/api/auth/logout', {
        method: 'POST',
        credentials: 'include',
      });
      // Effacer le token JWT local
      localStorage.removeItem('auth_token');
      window.location.reload();
    });
  }
  return true;
}

// Initialisation au chargement de la page
window.addEventListener('DOMContentLoaded', () => {
  initAuthUI();
});
