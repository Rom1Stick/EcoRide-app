/**
 * Gestion de la page profil utilisateur
 */
import { getCurrentUser } from '../common/userProfile.js';
import { API } from '../common/api.js';
import { Auth } from '../common/auth.js';
import { RideService } from '../services/ride-service.js';
import { LocationService } from '../services/location-service.js';

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

  // Charger les informations du véhicule
  await loadVehicleInfo();

  // Initialiser les écouteurs d'événements
  initEventListeners();

  // Initialiser le modal de création de covoiturage
  initRideCreationModal();
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
    // Récupérer les réservations
    const bookingsResponse = await API.get('/api/bookings');
    const bookings = bookingsResponse.error ? [] : (bookingsResponse.data.bookings || []);
    
    // Récupérer les covoiturages proposés (en tant que chauffeur)
    const ridesResponse = await API.get('/api/rides/my');
    const myRides = ridesResponse.error ? [] : (ridesResponse.data.rides || []);
    
    // Combiner les deux types de trajets
    const allTrips = [...bookings, ...myRides];
    
    // Stocker les données en global pour le filtrage
    window.tripsData = {
      bookings,
      myRides,
      allTrips
    };
    
    // Mettre à jour le compteur de trajets
    document.getElementById('tripsCount').textContent = allTrips.length;

    // Préserver le bouton "Proposer un covoiturage"
    const tripsTab = document.getElementById('trips-tab');
    
    // Si aucun trajet, on affiche un message
    if (allTrips.length === 0) {
      tripsTab.innerHTML = `
        <div class="trips-actions">
          <button id="addRideBtn" class="btn-primary">
            <i class="fa-solid fa-plus"></i>
            Proposer un covoiturage
          </button>
          <div class="filter-buttons">
            <button id="filterAllTrips" class="filter-btn active" data-filter="all">Tous</button>
            <button id="filterBookings" class="filter-btn" data-filter="booking">Réservations</button>
            <button id="filterMyRides" class="filter-btn" data-filter="driver">Mes propositions</button>
          </div>
        </div>
        <p class="empty-state">Vous n'avez pas encore effectué de trajets.</p>
      `;
      document.getElementById('addRideBtn').addEventListener('click', showRideModal);
      setupTripFilters();
      return;
    }

    // Sinon, on affiche la liste des trajets
    let tripsHTML = `
      <div class="trips-actions">
        <button id="addRideBtn" class="btn-primary">
          <i class="fa-solid fa-plus"></i>
          Proposer un covoiturage
        </button>
        <div class="filter-buttons">
          <button id="filterAllTrips" class="filter-btn active" data-filter="all">Tous</button>
          <button id="filterBookings" class="filter-btn" data-filter="booking">Réservations</button>
          <button id="filterMyRides" class="filter-btn" data-filter="driver">Mes propositions</button>
        </div>
      </div>
      <div class="trips-list">
    `;

    // Afficher les réservations
    bookings.forEach((booking) => {
      tripsHTML += `
        <div class="trip-card booking-ride" data-type="booking">
          <div class="trip-header">
            <span class="trip-date">${new Date(booking.reserved_at).toLocaleDateString()}</span>
            <span class="trip-status booking">${booking.status || 'Réservation'}</span>
          </div>
          <div class="trip-body">
            <h3>Trajet #${booking.ride_id}</h3>
          </div>
        </div>
      `;
    });
    
    // Afficher les covoiturages proposés
    myRides.forEach((ride) => {
      tripsHTML += `
        <div class="trip-card driver-ride" data-type="driver" data-id="${ride.id || ride.covoiturage_id}">
          <div class="trip-header">
            <span class="trip-date">${new Date(ride.date_depart).toLocaleDateString()}</span>
            <span class="trip-status driver">Conducteur</span>
          </div>
          <div class="trip-body">
            <h3>De ${ride.departure} à ${ride.destination}</h3>
            <p>Le ${new Date(ride.date_depart).toLocaleDateString()} à ${ride.departureTime}</p>
            <p>${ride.availableSeats}/${ride.totalSeats} places disponibles</p>
            <p>${ride.price}€ par personne</p>
            <div class="trip-actions">
              <button class="btn-edit-ride" data-id="${ride.id || ride.covoiturage_id}" title="Modifier ce trajet">
                <i class="fa-solid fa-edit"></i> Modifier
              </button>
              <button class="btn-delete-ride" data-id="${ride.id || ride.covoiturage_id}" title="Supprimer ce trajet">
                <i class="fa-solid fa-trash"></i> Supprimer
              </button>
            </div>
          </div>
        </div>
      `;
    });

    tripsHTML += '</div>';
    tripsTab.innerHTML = tripsHTML;
    
    // Réattacher les événements après avoir recréé le bouton
    document.getElementById('addRideBtn').addEventListener('click', showRideModal);
    
    // Ajouter les écouteurs d'événements pour les boutons d'édition et de suppression
    document.querySelectorAll('.btn-edit-ride').forEach(button => {
      button.addEventListener('click', handleEditRide);
    });
    
    document.querySelectorAll('.btn-delete-ride').forEach(button => {
      button.addEventListener('click', handleDeleteRide);
    });
    
    // Initialiser les filtres
    setupTripFilters();
  } catch (error) {
    console.error('Erreur lors du chargement des trajets:', error);
  }
}

/**
 * Configuration des filtres pour les trajets
 */
function setupTripFilters() {
  const filterButtons = document.querySelectorAll('.filter-btn');
  
  filterButtons.forEach(button => {
    button.addEventListener('click', function() {
      // Retirer la classe active de tous les boutons
      filterButtons.forEach(btn => {
        btn.classList.remove('active');
        btn.setAttribute('aria-pressed', 'false');
      });
      
      // Ajouter la classe active au bouton cliqué
      this.classList.add('active');
      this.setAttribute('aria-pressed', 'true');
      
      // Appliquer le filtre
      const filterType = this.getAttribute('data-filter');
      filterTrips(filterType);
    });
  });
}

/**
 * Filtrage des trajets selon le type sélectionné
 */
function filterTrips(filterType) {
  const tripCards = document.querySelectorAll('.trip-card');
  
  if (!tripCards.length) return;
  
  tripCards.forEach(card => {
    const cardType = card.getAttribute('data-type');
    
    if (filterType === 'all' || cardType === filterType) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}

/**
 * Chargement des informations du véhicule
 */
async function loadVehicleInfo() {
  try {
    const response = await API.get('/api/vehicles');

    if (response.error) {
      // Si l'erreur est "Aucun véhicule trouvé", c'est normal, on affiche juste le formulaire d'ajout
      if (response.message.includes('Aucun véhicule')) {
        showAddVehicleButton();
        return;
      }
      console.error('Erreur lors du chargement du véhicule:', response.message);
      return;
    }

    // Si on a bien un véhicule, on l'affiche
    if (response.data && response.data.vehicle) {
      displayVehicle(response.data.vehicle);
    } else {
      showAddVehicleButton();
    }
  } catch (error) {
    console.error('Erreur lors du chargement du véhicule:', error);
    showAddVehicleButton();
  }
}

/**
 * Afficher le bouton d'ajout de véhicule et masquer les détails
 */
function showAddVehicleButton() {
  // Afficher le message "pas de véhicule"
  const noVehicleMessage = document.getElementById('noVehicleMessage');
  if (noVehicleMessage) noVehicleMessage.style.display = 'block';
  
  // Masquer les détails du véhicule
  const vehicleDetails = document.getElementById('vehicleDetails');
  if (vehicleDetails) vehicleDetails.style.display = 'none';
  
  // Afficher le bouton d'ajout
  const addVehicleBtn = document.getElementById('addVehicleBtn');
  if (addVehicleBtn) addVehicleBtn.style.display = 'block';
}

/**
 * Afficher les détails du véhicule et masquer le bouton d'ajout
 */
function displayVehicle(vehicle) {
  // Masquer le message "pas de véhicule"
  const noVehicleMessage = document.getElementById('noVehicleMessage');
  if (noVehicleMessage) noVehicleMessage.style.display = 'none';
  
  // Remplir et afficher les détails du véhicule
  const vehicleDetails = document.getElementById('vehicleDetails');
  if (vehicleDetails) {
    document.getElementById('vehicleName').textContent = `${vehicle.marque} ${vehicle.modele}`;
    document.getElementById('vehicleYear').textContent = vehicle.annee || 'N/A';
    document.getElementById('vehicleColor').textContent = vehicle.couleur || 'N/A';
    document.getElementById('vehiclePlate').textContent = vehicle.immatriculation || 'N/A';
    
    // Mettre à jour ou ajouter l'information sur le type d'énergie
    if (!document.getElementById('vehicleDetails4')) {
      const energyInfo = document.createElement('p');
      energyInfo.id = 'vehicleDetails4';
      energyInfo.innerHTML = `<i class="fa-solid fa-gas-pump"></i> <span id="vehicleEnergy">${vehicle.energie_nom || 'Non spécifié'}</span>`;
      document.querySelector('.vehicle-info-content').appendChild(energyInfo);
    } else {
      document.getElementById('vehicleEnergy').textContent = vehicle.energie_nom || 'Non spécifié';
    }
    
    vehicleDetails.style.display = 'block';
  }
  
  // Masquer le bouton d'ajout
  const addVehicleBtn = document.getElementById('addVehicleBtn');
  if (addVehicleBtn) addVehicleBtn.style.display = 'none';
}

/**
 * Création du modal pour ajouter/modifier un véhicule
 */
function createVehicleModal() {
  // Créer le modal s'il n'existe pas déjà
  if (!document.getElementById('vehicleModal')) {
    const modal = document.createElement('div');
    modal.id = 'vehicleModal';
    modal.className = 'modal';
    modal.innerHTML = `
      <div class="modal-content">
        <div class="modal-header">
          <h2 id="vehicleModalTitle">Ajouter un véhicule</h2>
          <button class="close-modal" aria-label="Fermer">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="modal-body">
          <form id="vehicleForm">
            <div class="form-row">
              <div class="form-group">
                <label for="vehicleBrand">Marque</label>
                <input type="text" id="vehicleBrand" name="marque" required placeholder="Ex: Renault" />
              </div>
              <div class="form-group">
                <label for="vehicleModel">Modèle</label>
                <input type="text" id="vehicleModel" name="modele" required placeholder="Ex: Clio" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="vehicleYear">Année</label>
                <input type="number" id="vehicleYear" name="annee" required min="1900" max="2099" placeholder="Ex: 2020" />
              </div>
              <div class="form-group">
                <label for="vehicleColor">Couleur</label>
                <input type="text" id="vehicleColor" name="couleur" required placeholder="Ex: Bleu" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="vehiclePlate">Immatriculation</label>
                <input type="text" id="vehiclePlate" name="immatriculation" required placeholder="Ex: AB-123-CD" />
              </div>
              <div class="form-group">
                <label for="vehicleEnergy">Type d'énergie</label>
                <select id="vehicleEnergy" name="energie_id" required>
                  <option value="">Sélectionner...</option>
                  <!-- Options chargées dynamiquement -->
                </select>
              </div>
            </div>
            <div class="form-group">
              <label for="vehicleSeats">Nombre de places</label>
              <input type="number" id="vehicleSeats" name="places" required min="1" max="9" value="5" />
            </div>
            <input type="hidden" id="vehicleId" name="id" value="" />
            <div class="form-buttons">
              <button type="button" class="btn-secondary cancel-vehicle-modal">Annuler</button>
              <button type="submit" class="btn-primary">Enregistrer</button>
            </div>
          </form>
        </div>
      </div>
    `;
    document.body.appendChild(modal);
    
    // Ajouter les écouteurs d'événements
    document.querySelector('#vehicleModal .close-modal').addEventListener('click', hideVehicleModal);
    document.querySelector('.cancel-vehicle-modal').addEventListener('click', hideVehicleModal);
    document.getElementById('vehicleForm').addEventListener('submit', handleVehicleSubmit);
    
    // Charger les types d'énergie
    loadEnergyTypes();
  }
}

/**
 * Charge les types d'énergie disponibles depuis l'API
 */
async function loadEnergyTypes() {
  try {
    const response = await API.get('/api/energy-types');
    const vehicleEnergy = document.getElementById('vehicleEnergy');
    
    if (response.error) {
      // En cas d'erreur, ajouter des options par défaut
      const defaultEnergyTypes = [
        { id: 1, nom: 'Essence' },
        { id: 2, nom: 'Diesel' },
        { id: 3, nom: 'Électrique' },
        { id: 4, nom: 'Hybride' },
        { id: 5, nom: 'GPL' }
      ];
      
      defaultEnergyTypes.forEach(type => {
        const option = document.createElement('option');
        option.value = type.id;
        option.textContent = type.nom;
        vehicleEnergy.appendChild(option);
      });
      
      console.error('Erreur lors du chargement des types d\'énergie:', response.message);
      return;
    }
    
    // Ajouter les options récupérées de l'API
    const energyTypes = response.data.energyTypes || [];
    energyTypes.forEach(type => {
      const option = document.createElement('option');
      option.value = type.id;
      option.textContent = type.nom;
      vehicleEnergy.appendChild(option);
    });
    
  } catch (error) {
    console.error('Erreur lors du chargement des types d\'énergie:', error);
  }
}

/**
 * Afficher le modal d'ajout/modification de véhicule
 */
function showVehicleModal(vehicle = null) {
  createVehicleModal();
  
  const modal = document.getElementById('vehicleModal');
  const title = document.getElementById('vehicleModalTitle');
  
  // Remplir le formulaire si on modifie un véhicule existant
  if (vehicle) {
    title.textContent = 'Modifier mon véhicule';
    document.getElementById('vehicleBrand').value = vehicle.marque || '';
    document.getElementById('vehicleModel').value = vehicle.modele || '';
    document.getElementById('vehicleYear').value = vehicle.annee || '';
    document.getElementById('vehicleColor').value = vehicle.couleur || '';
    document.getElementById('vehiclePlate').value = vehicle.immatriculation || '';
    document.getElementById('vehicleSeats').value = vehicle.places || 5;
    document.getElementById('vehicleId').value = vehicle.id || '';
    
    // Sélectionner le type d'énergie si disponible
    if (vehicle.energie_id) {
      const energySelect = document.getElementById('vehicleEnergy');
      // Attendre un peu que les options soient chargées
      setTimeout(() => {
        if (energySelect.options.length > 1) {
          energySelect.value = vehicle.energie_id;
        }
      }, 300);
    }
  } else {
    title.textContent = 'Ajouter un véhicule';
    document.getElementById('vehicleForm').reset();
    document.getElementById('vehicleId').value = '';
  }
  
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden'; // Empêcher le défilement
}

/**
 * Masquer le modal d'ajout/modification de véhicule
 */
function hideVehicleModal() {
  const modal = document.getElementById('vehicleModal');
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = ''; // Réactiver le défilement
  }
}

/**
 * Gestion de la soumission du formulaire de véhicule
 */
async function handleVehicleSubmit(e) {
  e.preventDefault();
  
  const formData = new FormData(e.target);
  const vehicleData = {
    marque: formData.get('marque'),
    modele: formData.get('modele'),
    annee: formData.get('annee'),
    couleur: formData.get('couleur'),
    immatriculation: formData.get('immatriculation'),
    places: formData.get('places'),
    energie_id: formData.get('energie_id')
  };
  
  const vehicleId = formData.get('id');
  
  try {
    let response;
    
    if (vehicleId) {
      // Mise à jour d'un véhicule existant
      response = await API.put(`/api/vehicles/${vehicleId}`, vehicleData);
    } else {
      // Création d'un nouveau véhicule
      response = await API.post('/api/vehicles', vehicleData);
    }
    
    if (response.error) {
      throw new Error(response.message || 'Erreur lors de l\'enregistrement du véhicule');
    }
    
    // Si tout s'est bien passé, fermer le modal et recharger les infos
    hideVehicleModal();
    await loadVehicleInfo();
    
    // Actualiser l'onglet "Mes trajets" pour afficher le bouton de proposition de covoiturage
    await loadTrips();
    
    // Si c'était un nouvel ajout, un rôle chauffeur a peut-être été attribué
    // Afficher un message de succès
    const message = vehicleId ? 'Véhicule mis à jour avec succès' : 'Véhicule ajouté avec succès';
    alert(message);
    
    // Recharger la page pour actualiser les données utilisateur, y compris le rôle chauffeur
    if (!vehicleId) { // Seulement si c'est un nouvel ajout
      setTimeout(() => {
        window.location.reload();
      }, 1000); // Attendre 1 seconde pour que l'utilisateur puisse voir le message
    }
  } catch (error) {
    console.error('Erreur:', error);
    alert(`Erreur: ${error.message}`);
  }
}

/**
 * Initialisation des écouteurs d'événements
 */
function initEventListeners() {
  // Déconnexion
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
      if (confirm('Êtes-vous sûr de vouloir vous déconnecter ?')) {
        Auth.logout();
        window.location.href = './index.html';
      }
    });
  }

  // Enregistrement du profil
  const profileForm = document.querySelector('.profile-form');
  if (profileForm) {
    profileForm.addEventListener('submit', async (e) => {
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
  
  // Bouton d'ajout de véhicule
  const addVehicleBtn = document.getElementById('addVehicleBtn');
  if (addVehicleBtn) {
    addVehicleBtn.addEventListener('click', () => showVehicleModal());
  }
  
  // Bouton de modification de véhicule
  const editVehicleBtn = document.getElementById('editVehicleBtn');
  if (editVehicleBtn) {
    editVehicleBtn.addEventListener('click', async () => {
      try {
        const response = await API.get('/api/vehicles');
        if (!response.error && response.data && response.data.vehicle) {
          showVehicleModal(response.data.vehicle);
        } else {
          throw new Error('Impossible de récupérer les informations du véhicule');
        }
      } catch (error) {
        console.error('Erreur:', error);
        alert(`Erreur: ${error.message}`);
      }
    });
  }
}

/**
 * Initialisation du modal de création de covoiturage
 */
function initRideCreationModal() {
  // Bouton d'ouverture du modal
  const addRideBtn = document.getElementById('addRideBtn');
  if (addRideBtn) {
    addRideBtn.addEventListener('click', showRideModal);
  }

  // Boutons de fermeture du modal
  const closeModalBtn = document.querySelector('.close-modal');
  const cancelModalBtn = document.querySelector('.cancel-modal');
  
  if (closeModalBtn) {
    closeModalBtn.addEventListener('click', hideRideModal);
  }
  
  if (cancelModalBtn) {
    cancelModalBtn.addEventListener('click', hideRideModal);
  }

  // Soumission du formulaire
  const addRideForm = document.getElementById('addRideForm');
  if (addRideForm) {
    addRideForm.addEventListener('submit', handleRideSubmit);
  }

  // Configuration de la date minimale (jour suivant)
  const dateInput = document.getElementById('date');
  if (dateInput) {
    // Définir demain comme date minimale
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1); // Ajouter un jour
    const tomorrowStr = tomorrow.toISOString().split('T')[0];
    dateInput.setAttribute('min', tomorrowStr);
    
    // Définir demain comme date par défaut
    dateInput.value = tomorrowStr;
  }
  
  // Chargement des véhicules dans le formulaire
  loadUserVehicles();
  
  // Initialisation de l'autocomplétion pour les champs de lieu
  const departureInput = document.getElementById('departure');
  const destinationInput = document.getElementById('destination');
  const departureContainer = document.getElementById('departure-autocomplete');
  const destinationContainer = document.getElementById('destination-autocomplete');
  
  if (departureInput && departureContainer) {
    // S'assurer que le conteneur d'autocomplétion a le bon style pour l'affichage des suggestions
    departureContainer.style.position = 'absolute';
    departureContainer.style.top = '100%';
    departureContainer.style.left = '0';
    departureContainer.style.right = '0';
    departureContainer.style.zIndex = '100';
    departureContainer.style.backgroundColor = '#4e342e';
    departureContainer.style.borderRadius = '0 0 0.75rem 0.75rem';
    
    // Initialiser l'autocomplétion de départ avec l'API nationale
    LocationService.initAutocomplete(departureInput, (city) => {
      console.log('Ville de départ sélectionnée:', city);
      // Stocker les données complètes de la ville sélectionnée
      departureInput.dataset.cityData = JSON.stringify(city);
    });
  }
  
  if (destinationInput && destinationContainer) {
    // S'assurer que le conteneur d'autocomplétion a le bon style pour l'affichage des suggestions
    destinationContainer.style.position = 'absolute';
    destinationContainer.style.top = '100%';
    destinationContainer.style.left = '0';
    destinationContainer.style.right = '0';
    destinationContainer.style.zIndex = '100';
    destinationContainer.style.backgroundColor = '#4e342e';
    destinationContainer.style.borderRadius = '0 0 0.75rem 0.75rem';
    
    // Initialiser l'autocomplétion de destination avec l'API nationale
    LocationService.initAutocomplete(destinationInput, (city) => {
      console.log('Ville d\'arrivée sélectionnée:', city);
      // Stocker les données complètes de la ville sélectionnée
      destinationInput.dataset.cityData = JSON.stringify(city);
    });
  }
}

/**
 * Charge les véhicules de l'utilisateur pour le formulaire de covoiturage
 */
async function loadUserVehicles() {
  try {
    const vehicleSelect = document.getElementById('vehicle');
    if (!vehicleSelect) return;
    
    // Vider le select avant de charger les options
    while (vehicleSelect.options.length > 1) {
      vehicleSelect.remove(1);
    }
    
    // Charger les véhicules de l'utilisateur
    const response = await API.get('/api/vehicles');
    
    if (response.error) {
      console.error('Erreur lors du chargement des véhicules:', response.message);
      
      // Ajouter une option par défaut si aucun véhicule n'est disponible
      const defaultOption = document.createElement('option');
      defaultOption.value = "default";
      defaultOption.textContent = "Aucun véhicule disponible";
      vehicleSelect.appendChild(defaultOption);
      
      return;
    }
    
    // Si on a un seul véhicule, l'ajouter directement
    if (response.data && response.data.vehicle) {
      const vehicle = response.data.vehicle;
      const option = document.createElement('option');
      option.value = vehicle.id;
      option.textContent = `${vehicle.marque} ${vehicle.modele} (${vehicle.places} places)`;
      option.selected = true;
      vehicleSelect.appendChild(option);
    } 
    // Si on a plusieurs véhicules, les ajouter tous
    else if (response.data && Array.isArray(response.data.vehicles)) {
      response.data.vehicles.forEach(vehicle => {
        const option = document.createElement('option');
        option.value = vehicle.id;
        option.textContent = `${vehicle.marque} ${vehicle.modele} (${vehicle.places} places)`;
        vehicleSelect.appendChild(option);
      });
      
      // Sélectionner le premier véhicule par défaut
      if (vehicleSelect.options.length > 1) {
        vehicleSelect.options[1].selected = true;
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement des véhicules:', error);
  }
}

/**
 * Affichage du modal de création de covoiturage
 */
function showRideModal() {
  const modal = document.getElementById('addRideModal');
  if (modal) {
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden'; // Empêcher le défilement
  }
}

/**
 * Masquage du modal de création de covoiturage
 */
function hideRideModal() {
  const modal = document.getElementById('addRideModal');
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = ''; // Réactiver le défilement
    
    // Réinitialiser le formulaire
    const form = document.getElementById('addRideForm');
    if (form) {
      form.reset();
      form.removeAttribute('data-edit-mode');
      form.removeAttribute('data-ride-id');
      
      // Réinitialiser le titre du modal
      const modalTitle = document.querySelector('#addRideModal .modal-header h2');
      if (modalTitle) {
        modalTitle.textContent = 'Proposer un covoiturage';
      }
      
      // Réinitialiser le texte du bouton de soumission
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton) {
        submitButton.textContent = 'Publier le covoiturage';
      }
    }
  }
}

/**
 * Gestion de la soumission du formulaire de covoiturage (création/modification)
 * @param {Event} e - L'événement de soumission
 */
async function handleRideSubmit(e) {
  e.preventDefault();
  
  try {
    // Validation des champs de ville
    const form = e.target;
    const cityFields = ['departure', 'destination'];
    
    // Valider les champs de ville avec le service de localisation
    const citiesValid = LocationService.validateCityFields(form, cityFields);
    
    if (!citiesValid) {
      // Afficher un message d'erreur global si nécessaire
      console.error('Veuillez sélectionner des villes valides parmi les suggestions proposées');
      return;
    }
    
    // Récupération des valeurs du formulaire
    const isEditMode = form.getAttribute('data-edit-mode') === 'true';
    const rideId = form.getAttribute('data-ride-id');
    
    // Utiliser les IDs corrects correspondant à ceux définis dans le HTML
    const dateInput = form.querySelector('#date');
    const timeInput = form.querySelector('#departureTime');
    const priceInput = form.querySelector('#price');
    const seatsInput = form.querySelector('#seats');
    const vehicleSelect = form.querySelector('#vehicle');
    const departureInput = form.querySelector('#departure');
    const destinationInput = form.querySelector('#destination');
    const descriptionInput = form.querySelector('#description');
    const stopoverInput = form.querySelector('#stopover');
    const smokingInput = form.querySelector('#smoking');
    const petsInput = form.querySelector('#pets');
    const musicInput = form.querySelector('#music');
    
    // Validation supplémentaire des champs obligatoires
    if (!dateInput?.value || !timeInput?.value || !priceInput?.value || !seatsInput?.value) {
      alert('Veuillez remplir tous les champs obligatoires');
      return;
    }
    
    // Construction des données du trajet au format exactement attendu par l'API
    const rideData = {
      departure: departureInput.value,
      destination: destinationInput.value,
      date: dateInput.value,
      departureTime: timeInput.value,
      price: parseFloat(priceInput.value),
      totalSeats: parseInt(seatsInput.value, 10),
      availableSeats: parseInt(seatsInput.value, 10),
      vehicle_id: vehicleSelect ? vehicleSelect.value : null,
      description: descriptionInput ? descriptionInput.value : null,
      stopover: stopoverInput ? stopoverInput.value : null,
      preferences: {
        smoking: smokingInput ? smokingInput.checked : false,
        pets: petsInput ? petsInput.checked : false,
        music: musicInput ? musicInput.checked : true
      }
    };
    
    console.log('Données du trajet avant soumission:', rideData);
    
    // Sauvegarde locale pour faciliter la recherche
    saveRideLocally(rideData, rideId);
    
    let response;
    if (isEditMode && rideId) {
      // Mettre à jour un trajet existant
      response = await RideService.updateRide(rideId, rideData);
    } else {
      // Créer un nouveau trajet
      response = await RideService.createRide(rideData);
    }
    
    console.log('Réponse de l\'opération:', response);
    
    if (response && !response.error) {
      // Mise à jour réussie
      const message = isEditMode 
        ? 'Le trajet a été mis à jour avec succès' 
        : 'Le trajet a été publié avec succès';
      
      alert(message);
      hideRideModal();
      
      // Si un ID a été retourné par l'API, mettre à jour les données locales
      if (response.data && response.data.id) {
        updateRideLocally(response.data.id, rideData);
      }
      
      // Recharger la liste des trajets après une courte période
      setTimeout(() => {
        loadTrips();
      }, 500);
    } else {
      // Afficher les détails des erreurs si disponibles
      let errorMessage = response?.message || 'Une erreur est survenue lors de l\'opération';
      
      if (response && response.errors) {
        try {
          let errorDetails = '';
          
          // Si errors est un objet, formater les erreurs par champ
          if (typeof response.errors === 'object' && response.errors !== null) {
            errorDetails = '\n\nDétails:\n';
            for (const [field, message] of Object.entries(response.errors)) {
              errorDetails += `- ${field}: ${message}\n`;
            }
          }
          
          errorMessage += errorDetails;
        } catch (e) {
          console.error('Erreur lors du formatage des erreurs:', e);
        }
      }
      
      alert(errorMessage);
    }
  } catch (error) {
    console.error('Erreur lors de l\'opération sur le covoiturage:', error);
    alert('Une erreur est survenue lors de l\'opération');
  }
}

/**
 * Sauvegarde un trajet dans le stockage local
 * @param {Object} rideData - Les données du trajet
 * @param {string} rideId - ID du trajet (optionnel, si modification)
 */
function saveRideLocally(rideData, rideId = null) {
  try {
    // Récupérer les trajets existants
    let rides = [];
    const storedRides = localStorage.getItem('user_rides');
    
    if (storedRides) {
      rides = JSON.parse(storedRides);
      if (!Array.isArray(rides)) rides = [];
    }
    
    // Copie des données avec ajout de l'ID de l'utilisateur actuel
    const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
    
    // Restructurer les données pour correspondre au format de RideService.searchRides
    const rideWithUserId = {
      ...rideData,
      id: rideId || `local-${Date.now()}`,
      user_id: userData.id || 'current_user',
      created_at: new Date().toISOString(),
      
      // Assurer la compatibilité entre les différentes clés
      departure_city: rideData.departure,
      arrival_city: rideData.destination,
      departure_date: rideData.date,
      departure_time: rideData.departureTime,
      available_seats: rideData.availableSeats,
      total_seats: rideData.totalSeats,
      
      // Pour la recherche et l'affichage
      date_depart: rideData.date,
      departureTime: rideData.departureTime,
      availableSeats: rideData.availableSeats,
      totalSeats: rideData.totalSeats
    };
    
    // Si c'est une modification, mettre à jour le trajet existant
    if (rideId) {
      const existingIndex = rides.findIndex(ride => ride.id === rideId);
      if (existingIndex !== -1) {
        rides[existingIndex] = { ...rides[existingIndex], ...rideWithUserId };
      } else {
        rides.push(rideWithUserId);
      }
    } else {
      // Sinon, ajouter le nouveau trajet
      rides.push(rideWithUserId);
    }
    
    // Sauvegarder dans le localStorage
    localStorage.setItem('user_rides', JSON.stringify(rides));
    console.log('Trajet sauvegardé localement:', rideWithUserId);
  } catch (error) {
    console.error('Erreur lors de la sauvegarde locale du trajet:', error);
  }
}

/**
 * Met à jour un trajet local avec l'ID retourné par l'API
 * @param {string} apiId - ID retourné par l'API
 * @param {Object} rideData - Données du trajet
 */
function updateRideLocally(apiId, rideData) {
  try {
    // Récupérer les trajets existants
    const storedRides = localStorage.getItem('user_rides');
    if (!storedRides) return;
    
    let rides = JSON.parse(storedRides);
    if (!Array.isArray(rides)) return;
    
    // Chercher le dernier trajet ajouté (probablement celui qu'on vient de créer)
    // ou un trajet avec un ID temporaire commençant par "local-"
    const lastRideIndex = rides.findIndex(ride => 
      ride.id && typeof ride.id === 'string' && ride.id.startsWith('local-')
    );
    
    if (lastRideIndex !== -1) {
      // Mettre à jour l'ID avec celui retourné par l'API
      rides[lastRideIndex].id = apiId;
      localStorage.setItem('user_rides', JSON.stringify(rides));
      console.log('ID du trajet mis à jour localement:', apiId);
    }
  } catch (error) {
    console.error('Erreur lors de la mise à jour locale de l\'ID du trajet:', error);
  }
}

/**
 * Gère l'édition d'un covoiturage
 * @param {Event} e - L'événement de clic
 */
async function handleEditRide(e) {
  const rideId = e.currentTarget.getAttribute('data-id');
  if (!rideId) return;
  
  try {
    // Récupérer les données du trajet directement depuis les données en mémoire
    // au lieu de faire une requête API qui retourne une 404
    const ride = window.tripsData.myRides.find(r => 
      (r.id && r.id.toString() === rideId.toString()) || 
      (r.covoiturage_id && r.covoiturage_id.toString() === rideId.toString())
    );
    
    if (!ride) {
      throw new Error('Trajet non trouvé dans les données locales');
    }
    
    // Préparer le modal d'édition
    showRideModal();
    
    // Remplir le formulaire avec les données existantes
    const form = document.getElementById('addRideForm');
    form.setAttribute('data-edit-mode', 'true');
    form.setAttribute('data-ride-id', rideId);
    
    // Références aux champs
    const departureInput = document.getElementById('departure');
    const destinationInput = document.getElementById('destination');
    
    // Remplir les champs - tenir compte des différentes structures de nommage possibles
    departureInput.value = ride.departure || ride.departure_city || '';
    destinationInput.value = ride.destination || ride.arrival_city || '';
    
    // Stocker les données complètes des villes si disponibles
    if (ride.departureData) {
      try {
        departureInput.dataset.cityData = JSON.stringify({
          id: ride.departureData.id || '',
          nom: departureInput.value,
          codePostal: ride.departureData.postcode || '',
          region: ride.departureData.region || '',
          coordinates: ride.departureData.coordinates || []
        });
      } catch (error) {
        console.warn('Impossible de stocker les données de ville de départ:', error);
      }
    }
    
    if (ride.destinationData) {
      try {
        destinationInput.dataset.cityData = JSON.stringify({
          id: ride.destinationData.id || '',
          nom: destinationInput.value,
          codePostal: ride.destinationData.postcode || '',
          region: ride.destinationData.region || '',
          coordinates: ride.destinationData.coordinates || []
        });
      } catch (error) {
        console.warn('Impossible de stocker les données de ville d\'arrivée:', error);
      }
    }
    
    // Formater la date (YYYY-MM-DD) avec priorité sur les différents formats
    const dateStr = ride.date || ride.departure_date || ride.date_depart;
    const dateParts = dateStr ? new Date(dateStr).toISOString().split('T')[0] : '';
    document.getElementById('date').value = dateParts;
    
    // Tenir compte des différentes structures de nommage pour les différents champs
    document.getElementById('departureTime').value = ride.departureTime || ride.departure_time || '';
    document.getElementById('price').value = ride.price || '';
    document.getElementById('seats').value = ride.totalSeats || ride.total_seats || ride.availableSeats || ride.available_seats || 4;
    document.getElementById('description').value = ride.description || '';
    
    // Sélectionner le véhicule si disponible
    if (ride.vehicle_id) {
      const vehicleSelect = document.getElementById('vehicle');
      if (vehicleSelect) {
        // Chercher l'option correspondante
        const options = Array.from(vehicleSelect.options);
        const vehicleOption = options.find(option => option.value === ride.vehicle_id.toString());
        
        if (vehicleOption) {
          vehicleOption.selected = true;
        }
      }
    }
    
    // Mettre à jour les préférences si disponibles
    if (ride.preferences) {
      document.getElementById('smoking').checked = ride.preferences.smoking || false;
      document.getElementById('pets').checked = ride.preferences.pets || false;
      document.getElementById('music').checked = ride.preferences.music !== false; // Considérer comme vrai par défaut
    }
    
    // Changer le titre du modal
    const modalTitle = document.querySelector('#addRideModal .modal-header h2');
    if (modalTitle) {
      modalTitle.textContent = 'Modifier le covoiturage';
    }
    
    // Changer le texte du bouton de soumission
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
      submitButton.textContent = 'Mettre à jour';
    }
  } catch (error) {
    console.error('Erreur lors de la préparation du formulaire d\'édition:', error);
    alert(`Erreur: ${error.message}`);
  }
}

/**
 * Gère la suppression d'un covoiturage
 * @param {Event} e - L'événement de clic
 */
async function handleDeleteRide(e) {
  const rideId = e.currentTarget.getAttribute('data-id');
  if (!rideId) return;
  
  // Demander confirmation avant de supprimer
  if (confirm('Êtes-vous sûr de vouloir supprimer ce covoiturage ? Cette action est irréversible.')) {
    try {
      const response = await RideService.deleteRide(rideId);
      
      if (response.error) {
        throw new Error(response.message || 'Erreur lors de la suppression du covoiturage');
      }
      
      // Afficher un message de succès
      alert('Le covoiturage a été supprimé avec succès.');
      
      // Recharger la liste des trajets
      await loadTrips();
    } catch (error) {
      console.error('Erreur lors de la suppression du covoiturage:', error);
      alert(`Erreur: ${error.message}`);
    }
  }
}