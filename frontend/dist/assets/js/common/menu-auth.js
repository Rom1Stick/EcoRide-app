/**
 * Gestion de l'affichage du menu en fonction de l'état d'authentification et du rôle de l'utilisateur
 */
import { getUserInfo } from './api.js';

document.addEventListener('DOMContentLoaded', async function () {
  // Récupération des informations utilisateur
  const { isAuthenticated, isAdmin, user } = await getUserInfo();

  // Référence aux éléments du menu
  const loginBtn = document.getElementById('loginBtn');
  const profileBtn = document.getElementById('profileBtn');
  const userAvatar = document.getElementById('userAvatar');
  const username = document.getElementById('username');
  const headerNav = document.querySelector('.header__nav');

  // Si l'utilisateur est authentifié, mettre à jour l'interface
  if (isAuthenticated && user) {
    // Masquer le bouton de connexion
    if (loginBtn) loginBtn.style.display = 'none';

    // Afficher le bouton de profil
    if (profileBtn) {
      profileBtn.style.display = 'flex';

      // Mettre à jour l'avatar si disponible
      if (userAvatar && user.profilePicture) {
        userAvatar.src = user.profilePicture;
      }

      // Mettre à jour le nom d'utilisateur
      if (username) {
        username.textContent = user.name || user.email || 'Utilisateur';
      }
    }

    // Si l'utilisateur est administrateur, ajouter un lien vers le dashboard admin
    if (isAdmin && headerNav) {
      // Vérifier si le lien d'administration existe déjà
      const adminLinkExists = Array.from(headerNav.querySelectorAll('a')).some((link) =>
        link.getAttribute('href').includes('admin/dashboard.html')
      );

      // Ajouter le lien d'administration s'il n'existe pas déjà
      if (!adminLinkExists) {
        // Créer le lien d'administration
        const adminLink = document.createElement('a');
        adminLink.href = '../../pages/admin/dashboard.html';
        adminLink.innerHTML = '<i class="fa-solid fa-user-shield"></i> Administration';
        adminLink.classList.add('admin-link');

        // Insérer le lien avant le conteneur d'authentification
        const authContainer = headerNav.querySelector('.auth-container');
        if (authContainer) {
          headerNav.insertBefore(adminLink, authContainer);
        } else {
          // Si le conteneur d'authentification n'existe pas, ajouter à la fin
          headerNav.appendChild(adminLink);
        }
      }
    }
  } else {
    // Si l'utilisateur n'est pas authentifié, s'assurer que le bouton de profil est masqué
    if (profileBtn) profileBtn.style.display = 'none';
    if (loginBtn) loginBtn.style.display = 'flex';

    // Supprimer tout lien d'administration existant
    const adminLink = headerNav?.querySelector('.admin-link');
    if (adminLink) {
      adminLink.remove();
    }
  }
});
