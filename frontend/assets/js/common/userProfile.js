/**
 * Gestion de l'affichage du bouton de profil utilisateur
 * Ce script fonctionne avec auth.js pour déterminer si l'utilisateur est connecté
 * et affiche soit le bouton de connexion, soit le bouton de profil.
 */

// Fonction pour récupérer les informations de l'utilisateur connecté
async function getCurrentUser() {
  // Vérifier si un token d'authentification existe
  const storedToken = localStorage.getItem('auth_token');
  if (!storedToken) {
    return null;
  }

  try {
    // Appeler l'API pour récupérer les informations de l'utilisateur
    const headers = { Authorization: `Bearer ${storedToken}` };
    const response = await fetch('/api/users/me', {
      method: 'GET',
      credentials: 'include',
      headers,
    });

    if (!response.ok) {
      return null;
    }

    const result = await response.json();
    return result.data || null;
  } catch (error) {
    console.error('Erreur lors de la récupération des données utilisateur:', error);
    return null;
  }
}

// Fonction pour mettre à jour l'interface utilisateur selon l'état d'authentification
async function updateUserInterface() {
  const loginBtn = document.getElementById('loginBtn');
  const profileBtn = document.getElementById('profileBtn');
  const userAvatar = document.getElementById('userAvatar');
  const username = document.getElementById('username');

  // Récupérer l'utilisateur actuel
  const currentUser = await getCurrentUser();

  if (currentUser) {
    // Utilisateur connecté : afficher le bouton de profil
    if (loginBtn) loginBtn.style.display = 'none';
    if (profileBtn) profileBtn.style.display = 'flex';

    // Mettre à jour les informations utilisateur
    if (username) {
      username.textContent = currentUser.name || currentUser.email || 'Utilisateur';
    }

    // Mettre à jour l'avatar si disponible
    if (userAvatar && currentUser.photoPath) {
      userAvatar.src = currentUser.photoPath;
    }
  } else {
    // Utilisateur non connecté : afficher le bouton de connexion
    if (loginBtn) loginBtn.style.display = 'block';
    if (profileBtn) profileBtn.style.display = 'none';
  }
}

// Fonction pour gérer la déconnexion
function setupLogout() {
  // Implémentation à ajouter si un bouton de déconnexion est ajouté à la page de profil
  document.addEventListener('click', async (e) => {
    if (e.target && e.target.id === 'logoutBtn') {
      try {
        // Appeler l'API de déconnexion
        await fetch('/api/auth/logout', {
          method: 'POST',
          credentials: 'include',
        });

        // Supprimer le token
        localStorage.removeItem('auth_token');

        // Rediriger vers la page d'accueil
        window.location.href = '/';
      } catch (error) {
        console.error('Erreur lors de la déconnexion:', error);
      }
    }
  });
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
  updateUserInterface();
  setupLogout();
});

// Exporter les fonctions pour une utilisation dans d'autres modules
export { getCurrentUser, updateUserInterface };
