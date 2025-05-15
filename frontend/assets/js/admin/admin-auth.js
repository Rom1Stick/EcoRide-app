/**
 * Gestion de l'authentification et de l'autorisation pour les pages d'administration
 * Ce script vérifie si l'utilisateur est authentifié et possède un rôle administrateur
 * Sinon, il redirige vers la page d'accueil
 */

import { getUserInfo } from '../common/api.js';

/**
 * Vérifie si l'utilisateur a le droit d'accéder aux pages d'administration
 * @returns {Promise<boolean>} true si l'accès est autorisé, false sinon
 */
export async function checkAdminAccess() {
  try {
    // Récupérer les informations de l'utilisateur
    const { isAuthenticated, isAdmin } = await getUserInfo();

    // Si l'utilisateur n'est pas authentifié ou n'est pas admin, rediriger vers la page d'accueil
    if (!isAuthenticated || !isAdmin) {
      window.location.href = '/pages/public/index.html';
      return false;
    }

    // L'utilisateur est authentifié et a un rôle admin
    return true;
  } catch (error) {
    window.location.href = '/pages/public/index.html';
    return false;
  }
}

// Exécuter la vérification des droits d'accès immédiatement
document.addEventListener('DOMContentLoaded', async () => {
  await checkAdminAccess();
});
