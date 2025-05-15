/**
 * Gestion de la page profil utilisateur
 */
import { getCurrentUser } from '../common/userProfile.js';

// Chargement des données utilisateur
async function loadUserData() {
  const user = await getCurrentUser();

  if (!user) {
    // Rediriger vers la page de connexion si aucun utilisateur n'est connecté
    window.location.href = './login.html';
    return;
  }

  // Mettre à jour les informations du profil
  const profileName = document.getElementById('profileName');
  const profileEmail = document.getElementById('profileEmail');
  const profileImage = document.getElementById('profileImage');
  const nameInput = document.getElementById('name');
  const emailInput = document.getElementById('email');
  const phoneInput = document.getElementById('phone');
  const bioInput = document.getElementById('bio');

  if (profileName) profileName.textContent = user.name || 'Utilisateur';
  if (profileEmail) profileEmail.textContent = user.email || '';
  if (profileImage && user.photoPath) profileImage.src = user.photoPath;

  // Remplir le formulaire avec les données existantes
  if (nameInput) nameInput.value = user.name || '';
  if (emailInput) emailInput.value = user.email || '';
  if (phoneInput) phoneInput.value = user.phone || '';
  if (bioInput) bioInput.value = user.bio || '';

  // Mise à jour des statistiques
  const tripsCount = document.getElementById('tripsCount');
  const co2Saved = document.getElementById('co2Saved');

  if (tripsCount) tripsCount.textContent = user.trips_count || '0';
  if (co2Saved) co2Saved.textContent = user.co2_saved ? `${user.co2_saved}kg` : '0kg';
}

// Gestion des onglets
function setupTabs() {
  const tabButtons = document.querySelectorAll('.tab-button');
  const tabContents = document.querySelectorAll('.tab-content');

  tabButtons.forEach((button) => {
    button.addEventListener('click', () => {
      // Retirer la classe active de tous les boutons
      tabButtons.forEach((btn) => btn.classList.remove('active'));

      // Ajouter la classe active au bouton cliqué
      button.classList.add('active');

      // Masquer tous les contenus d'onglets
      tabContents.forEach((content) => content.classList.add('hidden'));

      // Afficher le contenu de l'onglet correspondant
      const tabId = button.getAttribute('data-tab');
      const tabContent = document.getElementById(`${tabId}-tab`);
      if (tabContent) tabContent.classList.remove('hidden');
    });
  });
}

// Gestion du formulaire de profil
function setupProfileForm() {
  const profileForm = document.querySelector('.profile-form');

  if (profileForm) {
    profileForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      // Récupérer les valeurs du formulaire
      const name = document.getElementById('name').value;
      const email = document.getElementById('email').value;
      const phone = document.getElementById('phone').value;
      const bio = document.getElementById('bio').value;

      try {
        // Récupérer le token d'authentification
        const token = localStorage.getItem('auth_token');

        if (!token) {
          throw new Error('Utilisateur non authentifié');
        }

        // Envoyer les données mises à jour à l'API
        const response = await fetch('/api/users/profile', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            Authorization: `Bearer ${token}`,
          },
          body: JSON.stringify({
            name,
            email,
            phone,
            bio,
          }),
        });

        if (!response.ok) {
          throw new Error('Erreur lors de la mise à jour du profil');
        }

        // Afficher un message de succès
        alert('Profil mis à jour avec succès !');

        // Recharger les données utilisateur
        loadUserData();
      } catch (error) {
        console.error('Erreur:', error);
        alert(`Erreur: ${error.message}`);
      }
    });
  }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
  loadUserData();
  setupTabs();
  setupProfileForm();

  // Gestion des boutons spécifiques de la page profil
  const changePasswordBtn = document.getElementById('changePasswordBtn');
  const deleteAccountBtn = document.getElementById('deleteAccountBtn');

  if (changePasswordBtn) {
    changePasswordBtn.addEventListener('click', () => {
      alert('Fonctionnalité de changement de mot de passe à implémenter');
      // TODO: Implémenter la fonctionnalité de changement de mot de passe
    });
  }

  if (deleteAccountBtn) {
    deleteAccountBtn.addEventListener('click', () => {
      const confirmation = confirm(
        'Êtes-vous sûr de vouloir supprimer votre compte ? Cette action est irréversible.'
      );

      if (confirmation) {
        // TODO: Implémenter la fonctionnalité de suppression de compte
        alert('Fonctionnalité de suppression de compte à implémenter');
      }
    });
  }
});
