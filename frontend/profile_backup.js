/**
 * Gestion de la page profil utilisateur
 */
import { getCurrentUser } from '../common/userProfile.js';
import { API } from '../common/api.js';
import { Auth } from '../common/auth.js';
import { RideService } from '../services/ride-service.js';
import { LocationService } from '../services/location-service.js';

// Chargement des donnÃ©es utilisateur
async function loadUserData() {
  const user = await getCurrentUser();

  if (!user) {
    // Rediriger vers la page de connexion si aucun utilisateur n'est connectÃ©
    window.location.href = './login.html';
    return;
  }

  // Mettre Ã  jour les informations du profil
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

  // Remplir le formulaire avec les donnÃ©es existantes
  if (nameInput) nameInput.value = user.name || '';
  if (emailInput) emailInput.value = user.email || '';
  if (phoneInput) phoneInput.value = user.phone || '';
  if (bioInput) bioInput.value = user.bio || '';

  // Mise Ã  jour des statistiques
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

      // Ajouter la classe active au bouton cliquÃ©
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

      // RÃ©cupÃ©rer les valeurs du formulaire
      const name = document.getElementById('name').value;
      const email = document.getElementById('email').value;
      const phone = document.getElementById('phone').value;
      const bio = document.getElementById('bio').value;

      try {
        // RÃ©cupÃ©rer le token d'authentification
        const token = localStorage.getItem('auth_token');

        if (!token) {
          throw new Error('Utilisateur non authentifiÃ©');
        }

        // Envoyer les donnÃ©es mises Ã  jour Ã  l'API
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
          throw new Error('Erreur lors de la mise Ã  jour du profil');
        }

        // Afficher un message de succÃ¨s
        alert('Profil mis Ã  jour avec succÃ¨s !');

        // Recharger les donnÃ©es utilisateur
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
  // VÃ©rifier que l'utilisateur est connectÃ©
  if (!Auth.isLoggedIn()) {
    window.location.href = './login.html';
    return;
  }

  // Initialiser les onglets
  initTabs();

  // Charger les donnÃ©es du profil
  await loadProfileData();

  // Charger le solde de crÃ©dits
  await loadCreditsBalance();

  // Charger les trajets
  await loadTrips();

  // Charger les informations du vÃ©hicule
  await loadVehicleInfo();

  // Initialiser les Ã©couteurs d'Ã©vÃ©nements
  initEventListeners();

  // Initialiser le modal de crÃ©ation de covoiturage
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

      // Ajouter la classe 'active' au bouton cliquÃ©
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
 * Chargement des donnÃ©es du profil
 */
async function loadProfileData() {
  try {
    const response = await API.get('/api/users/me');

    if (response.error) {
      console.error('Erreur lors du chargement du profil:', response.message);
      return;
    }

    const userData = response.data;

    // Mettre Ã  jour les champs du profil
    document.getElementById('profileName').textContent = userData.name || 'Utilisateur';
    document.getElementById('profileEmail').textContent = userData.email || 'email@exemple.com';

    // Remplir le formulaire
    document.getElementById('name').value = userData.name || '';
    document.getElementById('email').value = userData.email || '';
    document.getElementById('phone').value = userData.phone || '';
    document.getElementById('bio').value = userData.bio || '';

    // Mettre Ã  jour l'avatar si disponible
    if (userData.avatar) {
      document.getElementById('profileImage').src = userData.avatar;
    }
  } catch (error) {
    console.error('Erreur lors du chargement du profil:', error);
  }
}

/**
 * Chargement du solde de crÃ©dits
 */
async function loadCreditsBalance() {
  try {
    const response = await API.get('/api/credits/balance');

    if (response.error) {
      console.error('Erreur lors du chargement du solde de crÃ©dits:', response.message);
      return;
    }

    // Mettre Ã  jour l'affichage du solde de crÃ©dits
    const creditsBalance = response.data.balance || 0;
    document.getElementById('creditsBalance').textContent = creditsBalance.toFixed(0);
  } catch (error) {
    console.error('Erreur lors du chargement du solde de crÃ©dits:', error);
  }
}

/**
 * Chargement des trajets
 */
async function loadTrips() {
  try {
    // RÃ©cupÃ©rer les rÃ©servations
    const bookingsResponse = await API.get('/api/bookings');
    const bookings = bookingsResponse.error ? [] : (bookingsResponse.data.bookings || []);
    
    // RÃ©cupÃ©rer les covoiturages proposÃ©s (en tant que chauffeur)
    const ridesResponse = await API.get('/api/rides/my');
    const myRides = ridesResponse.error ? [] : (ridesResponse.data.rides || []);
    
    // Marquer l'Ã©tat de chaque trajet, utile pour les interactions UI
    myRides.forEach(ride => {
      ride.status = 'active'; // Par dÃ©faut, tous les trajets sont actifs
    });
    
    // Marquer l'Ã©tat de chaque trajet, utile pour les interactions UI
    myRides.forEach(ride => {
      ride.status = 'active'; // Par dÃ©faut, tous les trajets sont actifs
    });
    
    // Combiner les deux types de trajets
    const allTrips = [...bookings, ...myRides];
    
    // Stocker les donnÃ©es en global pour le filtrage
    window.tripsData = {
      bookings,
      myRides,
      allTrips
    };
    
    // Mettre Ã  jour le compteur de trajets
    document.getElementById('tripsCount').textContent = allTrips.length;

    // PrÃ©server le bouton "Proposer un covoiturage"
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
            <button id="filterBookings" class="filter-btn" data-filter="booking">RÃ©servations</button>
            <button id="filterMyRides" class="filter-btn" data-filter="driver">Mes propositions</button>
          </div>
        </div>
        <p class="empty-state">Vous n'avez pas encore effectuÃ© de trajets.</p>
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
          <button id="filterBookings" class="filter-btn" data-filter="booking">RÃ©servations</button>
          <button id="filterMyRides" class="filter-btn" data-filter="driver">Mes propositions</button>
        </div>
      </div>
      <div class="trips-list">
    `;

    // Afficher les rÃ©servations
    bookings.forEach((booking) => {
      tripsHTML += `
        <div class="trip-card booking-ride" data-type="booking">
          <div class="trip-header">
            <span class="trip-date">${new Date(booking.reserved_at).toLocaleDateString()}</span>
            <span class="trip-status booking">${booking.status || 'RÃ©servation'}</span>
          </div>
          <div class="trip-body">
            <h3>Trajet #${booking.ride_id}</h3>
          </div>
        </div>
      `;
    });
    
    // Afficher les covoiturages proposÃ©s
    myRides.forEach((ride) => {
      const rideId = ride.id || ride.covoiturage_id;
      tripsHTML += `
        <div class="trip-card driver-ride" data-type="driver" data-id="${rideId}" data-status="${ride.status}">
          <div class="trip-header">
            <span class="trip-date">${new Date(ride.date_depart).toLocaleDateString()}</span>
            <span class="trip-status driver">Conducteur</span>
          </div>
          <div class="trip-body">
            <h3>De ${ride.departure} Ã  ${ride.destination}</h3>
            <p>Le ${new Date(ride.date_depart).toLocaleDateString()} Ã  ${ride.departureTime}</p>
            <p>${ride.availableSeats}/${ride.totalSeats} places disponibles</p>
            <p>${ride.price}â‚¬ par personne</p>
            <div class="trip-actions">
              <button class="btn-edit-ride" data-id="${rideId}" title="Modifier ce trajet">
                <i class="fa-solid fa-edit"></i> Modifier
              </button>
              <button class="btn-delete-ride" data-id="${rideId}" title="Supprimer ce trajet">
                <i class="fa-solid fa-trash"></i> Supprimer
              </button>
            </div>
          </div>
        </div>
      `;
    });

    tripsHTML += '</div>';
    tripsTab.innerHTML = tripsHTML;
    
    // RÃ©attacher les Ã©vÃ©nements aprÃ¨s avoir recrÃ©Ã© le bouton
    document.getElementById('addRideBtn').addEventListener('click', showRideModal);
    
    // Ajouter les Ã©couteurs d'Ã©vÃ©nements pour les boutons d'Ã©dition et de suppression
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
      filterButtons.forEach(btn => btn.classList.remove('active'));
      
      // Ajouter la classe active au bouton cliquÃ©
      this.classList.add('active');
      
      // Appliquer le filtre
      const filterType = this.getAttribute('data-filter');
      filterTrips(filterType);
    });
  });
}

/**
 * Filtrage des trajets selon le type sÃ©lectionnÃ©
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
 * Chargement des informations du vÃ©hicule
 */
async function loadVehicleInfo() {
  try {
    const response = await API.get('/api/vehicles');

    if (response.error) {
      // Si l'erreur est "Aucun vÃ©hicule trouvÃ©", c'est normal, on affiche juste le formulaire d'ajout
      if (response.message.includes('Aucun vÃ©hicule')) {
        showAddVehicleButton();
        return;
      }
      console.error('Erreur lors du chargement du vÃ©hicule:', response.message);
      return;
    }

    // Si on a bien un vÃ©hicule, on l'affiche
    if (response.data && response.data.vehicle) {
      displayVehicle(response.data.vehicle);
    } else {
      showAddVehicleButton();
    }
  } catch (error) {
    console.error('Erreur lors du chargement du vÃ©hicule:', error);
    showAddVehicleButton();
  }
}

/**
 * Afficher le bouton d'ajout de vÃ©hicule et masquer les dÃ©tails
 */
function showAddVehicleButton() {
  // Afficher le message "pas de vÃ©hicule"
  const noVehicleMessage = document.getElementById('noVehicleMessage');
  if (noVehicleMessage) noVehicleMessage.style.display = 'block';
  
  // Masquer les dÃ©tails du vÃ©hicule
  const vehicleDetails = document.getElementById('vehicleDetails');
  if (vehicleDetails) vehicleDetails.style.display = 'none';
  
  // Afficher le bouton d'ajout
  const addVehicleBtn = document.getElementById('addVehicleBtn');
  if (addVehicleBtn) addVehicleBtn.style.display = 'block';
}

/**
 * Afficher les dÃ©tails du vÃ©hicule et masquer le bouton d'ajout
 */
function displayVehicle(vehicle) {
  // Masquer le message "pas de vÃ©hicule"
  const noVehicleMessage = document.getElementById('noVehicleMessage');
  if (noVehicleMessage) noVehicleMessage.style.display = 'none';
  
  // Remplir et afficher les dÃ©tails du vÃ©hicule
  const vehicleDetails = document.getElementById('vehicleDetails');
  if (vehicleDetails) {
    document.getElementById('vehicleName').textContent = `${vehicle.marque} ${vehicle.modele}`;
    document.getElementById('vehicleYear').textContent = vehicle.annee || 'N/A';
    document.getElementById('vehicleColor').textContent = vehicle.couleur || 'N/A';
    document.getElementById('vehiclePlate').textContent = vehicle.immatriculation || 'N/A';
    
    // Mettre Ã  jour ou ajouter l'information sur le type d'Ã©nergie
    if (!document.getElementById('vehicleDetails4')) {
      const energyInfo = document.createElement('p');
      energyInfo.id = 'vehicleDetails4';
      energyInfo.innerHTML = `<i class="fa-solid fa-gas-pump"></i> <span id="vehicleEnergy">${vehicle.energie_nom || 'Non spÃ©cifiÃ©'}</span>`;
      document.querySelector('.vehicle-info-content').appendChild(energyInfo);
    } else {
      document.getElementById('vehicleEnergy').textContent = vehicle.energie_nom || 'Non spÃ©cifiÃ©';
    }
    
    vehicleDetails.style.display = 'block';
  }
  
  // Masquer le bouton d'ajout
  const addVehicleBtn = document.getElementById('addVehicleBtn');
  if (addVehicleBtn) addVehicleBtn.style.display = 'none';
}

/**
 * CrÃ©ation du modal pour ajouter/modifier un vÃ©hicule
 */
function createVehicleModal() {
  // CrÃ©er le modal s'il n'existe pas dÃ©jÃ 
  if (!document.getElementById('vehicleModal')) {
    const modal = document.createElement('div');
    modal.id = 'vehicleModal';
    modal.className = 'modal';
    modal.innerHTML = `
      <div class="modal-content">
        <div class="modal-header">
          <h2 id="vehicleModalTitle">Ajouter un vÃ©hicule</h2>
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
                <label for="vehicleModel">ModÃ¨le</label>
                <input type="text" id="vehicleModel" name="modele" required placeholder="Ex: Clio" />
              </div>
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="vehicleYear">AnnÃ©e</label>
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
                <label for="vehicleEnergy">Type d'Ã©nergie</label>
                <select id="vehicleEnergy" name="energie_id" required>
                  <option value="">SÃ©lectionner...</option>
                  <!-- Options chargÃ©es dynamiquement -->
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
    
    // Ajouter les Ã©couteurs d'Ã©vÃ©nements
    document.querySelector('#vehicleModal .close-modal').addEventListener('click', hideVehicleModal);
    document.querySelector('.cancel-vehicle-modal').addEventListener('click', hideVehicleModal);
    document.getElementById('vehicleForm').addEventListener('submit', handleVehicleSubmit);
    
    // Charger les types d'Ã©nergie
    loadEnergyTypes();
  }
}

/**
 * Charge les types d'Ã©nergie disponibles depuis l'API
 */
async function loadEnergyTypes() {
  try {
    const response = await API.get('/api/energy-types');
    const vehicleEnergy = document.getElementById('vehicleEnergy');
    
    if (response.error) {
      // En cas d'erreur, ajouter des options par dÃ©faut
      const defaultEnergyTypes = [
        { id: 1, nom: 'Essence' },
        { id: 2, nom: 'Diesel' },
        { id: 3, nom: 'Ã‰lectrique' },
        { id: 4, nom: 'Hybride' },
        { id: 5, nom: 'GPL' }
      ];
      
      defaultEnergyTypes.forEach(type => {
        const option = document.createElement('option');
        option.value = type.id;
        option.textContent = type.nom;
        vehicleEnergy.appendChild(option);
      });
      
      console.error('Erreur lors du chargement des types d\'Ã©nergie:', response.message);
      return;
    }
    
    // Ajouter les options rÃ©cupÃ©rÃ©es de l'API
    const energyTypes = response.data.energyTypes || [];
    energyTypes.forEach(type => {
      const option = document.createElement('option');
      option.value = type.id;
      option.textContent = type.nom;
      vehicleEnergy.appendChild(option);
    });
    
  } catch (error) {
    console.error('Erreur lors du chargement des types d\'Ã©nergie:', error);
  }
}

/**
 * Afficher le modal d'ajout/modification de vÃ©hicule
 */
function showVehicleModal(vehicle = null) {
  createVehicleModal();
  
  const modal = document.getElementById('vehicleModal');
  const title = document.getElementById('vehicleModalTitle');
  
  // Remplir le formulaire si on modifie un vÃ©hicule existant
  if (vehicle) {
    title.textContent = 'Modifier mon vÃ©hicule';
    document.getElementById('vehicleBrand').value = vehicle.marque || '';
    document.getElementById('vehicleModel').value = vehicle.modele || '';
    document.getElementById('vehicleYear').value = vehicle.annee || '';
    document.getElementById('vehicleColor').value = vehicle.couleur || '';
    document.getElementById('vehiclePlate').value = vehicle.immatriculation || '';
    document.getElementById('vehicleSeats').value = vehicle.places || 5;
    document.getElementById('vehicleId').value = vehicle.id || '';
    
    // SÃ©lectionner le type d'Ã©nergie si disponible
    if (vehicle.energie_id) {
      const energySelect = document.getElementById('vehicleEnergy');
      // Attendre un peu que les options soient chargÃ©es
      setTimeout(() => {
        if (energySelect.options.length > 1) {
          energySelect.value = vehicle.energie_id;
        }
      }, 300);
    }
  } else {
    title.textContent = 'Ajouter un vÃ©hicule';
    document.getElementById('vehicleForm').reset();
    document.getElementById('vehicleId').value = '';
  }
  
  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden'; // EmpÃªcher le dÃ©filement
}

/**
 * Masquer le modal d'ajout/modification de vÃ©hicule
 */
function hideVehicleModal() {
  const modal = document.getElementById('vehicleModal');
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = ''; // RÃ©activer le dÃ©filement
  }
}

/**
 * Gestion de la soumission du formulaire de vÃ©hicule
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
      // Mise Ã  jour d'un vÃ©hicule existant
      response = await API.put(`/api/vehicles/${vehicleId}`, vehicleData);
    } else {
      // CrÃ©ation d'un nouveau vÃ©hicule
      response = await API.post('/api/vehicles', vehicleData);
    }
    
    if (response.error) {
      throw new Error(response.message || 'Erreur lors de l\'enregistrement du vÃ©hicule');
    }
    
    // Si tout s'est bien passÃ©, fermer le modal et recharger les infos
    hideVehicleModal();
    await loadVehicleInfo();
    
    // Actualiser l'onglet "Mes trajets" pour afficher le bouton de proposition de covoiturage
    await loadTrips();
    
    // Si c'Ã©tait un nouvel ajout, un rÃ´le chauffeur a peut-Ãªtre Ã©tÃ© attribuÃ©
    // Afficher un message de succÃ¨s
    const message = vehicleId ? 'VÃ©hicule mis Ã  jour avec succÃ¨s' : 'VÃ©hicule ajoutÃ© avec succÃ¨s';
    alert(message);
    
    // Recharger la page pour actualiser les donnÃ©es utilisateur, y compris le rÃ´le chauffeur
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
 * Initialisation des Ã©couteurs d'Ã©vÃ©nements
 */
function initEventListeners() {
  // DÃ©connexion
  const logoutBtn = document.getElementById('logoutBtn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', () => {
      if (confirm('ÃŠtes-vous sÃ»r de vouloir vous dÃ©connecter ?')) {
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
          alert('Erreur lors de la mise Ã  jour du profil: ' + response.message);
          return;
        }

        alert('Profil mis Ã  jour avec succÃ¨s !');
        await loadProfileData();
      } catch (error) {
        console.error('Erreur lors de la mise Ã  jour du profil:', error);
        alert('Une erreur est survenue lors de la mise Ã  jour du profil.');
      }
    });
  }
  
  // Bouton d'ajout de vÃ©hicule
  const addVehicleBtn = document.getElementById('addVehicleBtn');
  if (addVehicleBtn) {
    addVehicleBtn.addEventListener('click', () => showVehicleModal());
  }
  
  // Bouton de modification de vÃ©hicule
  const editVehicleBtn = document.getElementById('editVehicleBtn');
  if (editVehicleBtn) {
    editVehicleBtn.addEventListener('click', async () => {
      try {
        const response = await API.get('/api/vehicles');
        if (!response.error && response.data && response.data.vehicle) {
          showVehicleModal(response.data.vehicle);
        } else {
          throw new Error('Impossible de rÃ©cupÃ©rer les informations du vÃ©hicule');
        }
      } catch (error) {
        console.error('Erreur:', error);
        alert(`Erreur: ${error.message}`);
      }
    });
  }
}

/**
 * GÃ¨re l'Ã©dition d'un covoiturage
 * @param {Event} e - L'Ã©vÃ©nement de clic
 */
async function handleEditRide(e) {
  const rideId = e.currentTarget.getAttribute('data-id');
  if (!rideId) return;
  
  try {
    // Afficher un indicateur de chargement
    e.currentTarget.disabled = true;
    e.currentTarget.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Chargement...';
    
    // VÃ©rifier d'abord si le trajet existe toujours avec une requÃªte API directe
    const checkResponse = await API.get(`/api/rides/${rideId}`);
    
    if (checkResponse.error) {
      throw new Error(checkResponse.message || "Ce trajet n'existe plus ou a Ã©tÃ© supprimÃ©");
    }
    
    // RÃ©cupÃ©rer les dÃ©tails complets du trajet
    const response = await RideService.getRideDetails(rideId);
    
    // VÃ©rifier si la rÃ©ponse contient une erreur
    if (response.error) {
      throw new Error(response.message || 'Erreur lors de la rÃ©cupÃ©ration des dÃ©tails du covoiturage');
    }
    
    const ride = response.ride || response; // CompatibilitÃ© avec diffÃ©rents formats de rÃ©ponse
    
    // PrÃ©parer le modal d'Ã©dition
    showRideModal();
    
    // Remplir le formulaire avec les donnÃ©es existantes
    const form = document.getElementById('addRideForm');
    form.setAttribute('data-edit-mode', 'true');
    form.setAttribute('data-ride-id', rideId);
    
    // Remplir les champs
    document.getElementById('departure').value = ride.departure || '';
    document.getElementById('destination').value = ride.destination || '';
    
    // Formater la date (YYYY-MM-DD)
    const dateParts = new Date(ride.date_depart).toISOString().split('T')[0];
    document.getElementById('date').value = dateParts;
    
    document.getElementById('departureTime').value = ride.departureTime || '';
    document.getElementById('price').value = ride.price || '';
    document.getElementById('seats').value = ride.totalSeats || 4;
    document.getElementById('description').value = ride.description || '';
    
    // Mettre Ã  jour les prÃ©fÃ©rences si disponibles
    if (ride.preferences) {
      document.getElementById('smoking').checked = ride.preferences.smoking || false;
      document.getElementById('pets').checked = ride.preferences.pets || false;
      document.getElementById('music').checked = ride.preferences.music || false;
    }
    
    // Changer le titre du modal
    const modalTitle = document.querySelector('#addRideModal .modal-header h2');
    if (modalTitle) {
      modalTitle.textContent = 'Modifier le covoiturage';
    }
    
    // Changer le texte du bouton de soumission
    const submitButton = form.querySelector('button[type="submit"]');
    if (submitButton) {
      submitButton.textContent = 'Mettre Ã  jour';
    }
  } catch (error) {
    console.error('Erreur lors de la prÃ©paration du formulaire d\'Ã©dition:', error);
    alert(`Erreur: ${error.message}`);
    
    // Recharger la liste des trajets pour s'assurer qu'elle est Ã  jour
    await loadTrips();
  } finally {
    // RÃ©initialiser le bouton, qu'il y ait eu une erreur ou non
    if (!e.currentTarget.disabled) return; // Le bouton a peut-Ãªtre Ã©tÃ© supprimÃ© lors du rechargement
    e.currentTarget.disabled = false;
    e.currentTarget.innerHTML = '<i class="fa-solid fa-edit"></i> Modifier';
  }
}

/**
 * Gestion de la suppression d'un covoiturage
 * @param {Event} e - L'Ã©vÃ©nement de clic
 */
async function handleDeleteRide(e) {
  const rideId = e.currentTarget.getAttribute('data-id');
  if (!rideId) return;
  
  try {
    // Afficher un indicateur de chargement
    e.currentTarget.disabled = true;
    e.currentTarget.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Suppression...';
    
    // Supprimer le trajet via l'API
    const response = await API.delete(`/api/rides/${rideId}`);
    
    if (response.error) {
      throw new Error(response.message || 'Erreur lors de la suppression du trajet');
    }
    
    // Recharger la liste des trajets
    await loadTrips();
  } catch (error) {
    console.error('Erreur lors de la suppression du trajet:', error);
    alert(`Erreur: ${error.message}`);
  } finally {
    // RÃ©initialiser le bouton, qu'il y ait eu une erreur ou non
    if (!e.currentTarget.disabled) return; // Le bouton a peut-Ãªtre Ã©tÃ© supprimÃ© lors du rechargement
    e.currentTarget.disabled = false;
    e.currentTarget.innerHTML = '<i class="fa-solid fa-trash"></i> Supprimer';
  }
}

/**
 * Initialisation du modal de crÃ©ation de covoiturage
 */
function initRideCreationModal() {
  // ImplÃ©mentation de la crÃ©ation du modal de crÃ©ation de covoiturage
}

/**
 * Initialisation du modal de rÃ©servation
 */
function initRideReservationModal() {
  // ImplÃ©mentation de la crÃ©ation du modal de rÃ©servation
}
