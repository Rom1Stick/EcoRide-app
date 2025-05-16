/**
 * Gestion de la page profil utilisateur
 */
import { getCurrentUser } from '../common/userProfile.js';
import { API } from '../common/api.js';
import { Auth } from '../common/auth.js';

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
document.addEventListener('DOMContentLoaded', async () => {
  // Vérifier que l'utilisateur est connecté
  if (!Auth.isLoggedIn()) {
    window.location.href = './login.html';
    return;
  }

  // Initialiser les onglets
  initTabs();

  // Charger les données du profil
  await loadProfileData();

  // Charger le solde de crédits
  await loadCreditsBalance();

  // Charger les trajets
  await loadTrips();

  // Initialiser les écouteurs d'événements
  initEventListeners();
});

/**
 * Initialisation des onglets
 */
function initTabs() {
  const tabButtons = document.querySelectorAll('.tab-button');
  const tabContents = document.querySelectorAll('.tab-content');

  tabButtons.forEach((button) => {
    button.addEventListener('click', () => {
      // Retirer la classe 'active' de tous les boutons
      tabButtons.forEach((btn) => btn.classList.remove('active'));

      // Ajouter la classe 'active' au bouton cliqué
      button.classList.add('active');

      // Cacher tous les contenus d'onglet
      tabContents.forEach((content) => content.classList.add('hidden'));

      // Afficher le contenu de l'onglet correspondant
      const tabId = button.getAttribute('data-tab');
      document.getElementById(`${tabId}-tab`).classList.remove('hidden');
    });
  });
}

/**
 * Chargement des données du profil
 */
async function loadProfileData() {
  try {
    const response = await API.get('/api/users/me');

    if (response.error) {
      console.error('Erreur lors du chargement du profil:', response.message);
      return;
    }

    const userData = response.data;

    // Mettre à jour les champs du profil
    document.getElementById('profileName').textContent = userData.name || 'Utilisateur';
    document.getElementById('profileEmail').textContent = userData.email || 'email@exemple.com';

    // Remplir le formulaire
    document.getElementById('name').value = userData.name || '';
    document.getElementById('email').value = userData.email || '';
    document.getElementById('phone').value = userData.phone || '';
    document.getElementById('bio').value = userData.bio || '';

    // Mettre à jour l'avatar si disponible
    if (userData.avatar) {
      document.getElementById('profileImage').src = userData.avatar;
    }
  } catch (error) {
    console.error('Erreur lors du chargement du profil:', error);
  }
}

/**
 * Chargement du solde de crédits
 */
async function loadCreditsBalance() {
  try {
    const response = await API.get('/api/credits/balance');

    if (response.error) {
      console.error('Erreur lors du chargement du solde de crédits:', response.message);
      return;
    }

    // Mettre à jour l'affichage du solde de crédits
    const creditsBalance = response.data.balance || 0;
    document.getElementById('creditsBalance').textContent = creditsBalance.toFixed(0);
  } catch (error) {
    console.error('Erreur lors du chargement du solde de crédits:', error);
  }
}

/**
 * Chargement des trajets
 */
async function loadTrips() {
  try {
    const response = await API.get('/api/bookings');

    if (response.error) {
      console.error('Erreur lors du chargement des trajets:', response.message);
      return;
    }

    const bookings = response.data.bookings || [];

    // Mettre à jour le compteur de trajets
    document.getElementById('tripsCount').textContent = bookings.length;

    // Si aucun trajet, on affiche un message
    const tripsTab = document.getElementById('trips-tab');

    if (bookings.length === 0) {
      tripsTab.innerHTML =
        '<p class="empty-state">Vous n\'avez pas encore effectué de trajets.</p>';
      return;
    }

    // Sinon, on affiche la liste des trajets
    let tripsHTML = '<div class="trips-list">';

    bookings.forEach((booking) => {
      tripsHTML += `
        <div class="trip-card">
          <div class="trip-header">
            <span class="trip-date">${new Date(booking.reserved_at).toLocaleDateString()}</span>
            <span class="trip-status ${booking.status}">${booking.status}</span>
          </div>
          <div class="trip-body">
            <h3>Trajet #${booking.ride_id}</h3>
          </div>
        </div>
      `;
    });

    tripsHTML += '</div>';
    tripsTab.innerHTML = tripsHTML;
  } catch (error) {
    console.error('Erreur lors du chargement des trajets:', error);
  }
}

/**
 * Initialisation des écouteurs d'événements
 */
function initEventListeners() {
  // Déconnexion
  document.getElementById('logoutBtn').addEventListener('click', () => {
    Auth.logout();
    window.location.href = './index.html';
  });

  // Enregistrement du profil
  document.querySelector('.profile-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const userData = {
      name: document.getElementById('name').value,
      email: document.getElementById('email').value,
      phone: document.getElementById('phone').value,
      bio: document.getElementById('bio').value,
    };

    try {
      const response = await API.put('/api/users/me', userData);

      if (response.error) {
        alert('Erreur lors de la mise à jour du profil: ' + response.message);
        return;
      }

      alert('Profil mis à jour avec succès !');
      await loadProfileData();
    } catch (error) {
      console.error('Erreur lors de la mise à jour du profil:', error);
      alert('Une erreur est survenue lors de la mise à jour du profil.');
    }
  });
}
