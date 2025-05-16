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

// Classe Auth pour centraliser la gestion de l'authentification
export class Auth {
  /**
   * Vérifie si l'utilisateur est connecté
   * @returns {boolean} True si l'utilisateur est connecté
   */
  static isLoggedIn() {
    return localStorage.getItem('auth_token') !== null;
  }

  /**
   * Connecte l'utilisateur
   * @param {Object} credentials - Informations de connexion
   * @returns {Promise<Object>} Données utilisateur
   */
  static async login(credentials) {
    try {
      const response = await fetch('/api/auth/login', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(credentials),
      });

      const result = await response.json();

      if (!response.ok || result.error) {
        throw new Error(result.message || 'Échec de connexion');
      }

      // Stocker le token JWT
      if (result.data && result.data.token) {
        localStorage.setItem('auth_token', result.data.token);
      }

      return result.data;
    } catch (error) {
      console.error('Erreur de connexion:', error);
      throw error;
    }
  }

  /**
   * Déconnecte l'utilisateur
   */
  static async logout() {
    try {
      await fetch('/api/auth/logout', {
        method: 'POST',
        credentials: 'include',
        headers: {
          Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
      });
    } catch (error) {
      console.error('Erreur lors de la déconnexion:', error);
    } finally {
      localStorage.removeItem('auth_token');
    }
  }

  /**
   * Récupère les informations de l'utilisateur connecté
   * @returns {Promise<Object>} Données utilisateur
   */
  static async getCurrentUser() {
    if (!this.isLoggedIn()) {
      return null;
    }

    try {
      const response = await fetch('/api/users/me', {
        method: 'GET',
        credentials: 'include',
        headers: {
          Authorization: `Bearer ${localStorage.getItem('auth_token')}`,
        },
      });

      if (!response.ok) {
        if (response.status === 401) {
          localStorage.removeItem('auth_token');
        }
        return null;
      }

      const result = await response.json();
      return result.data;
    } catch (error) {
      console.error('Erreur lors de la récupération des informations utilisateur:', error);
      return null;
    }
  }
}
