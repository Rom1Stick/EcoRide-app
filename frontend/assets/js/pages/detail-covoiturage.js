/**
 * Gestion de l'affichage des détails d'un covoiturage
 */

// Éléments DOM
const rideDetailContainer = document.getElementById('ride-detail');
const breadcrumbRoute = document.getElementById('breadcrumb-route');

// Initialisation de la page
document.addEventListener('DOMContentLoaded', () => {
  console.log('Initialisation de la page de détail du covoiturage...');

  // Récupérer l'ID du trajet depuis l'URL
  const urlParams = new URLSearchParams(window.location.search);
  const rideId = urlParams.get('id');

  if (!rideId) {
    displayError('Aucun identifiant de trajet spécifié');
    return;
  }

  // Charger les détails du trajet
  loadRideDetails(rideId);
});

/**
 * Charge les détails d'un trajet spécifique
 * @param {string} rideId - L'identifiant du trajet
 */
function loadRideDetails(rideId) {
  // Simuler un chargement (dans un cas réel, on ferait un appel API)
  setTimeout(() => {
    // Simuler la récupération des détails du trajet
    const rideDetails = getMockRideDetails(rideId);

    if (rideDetails) {
      displayRideDetails(rideDetails);
    } else {
      displayError('Trajet non trouvé');
    }
  }, 1000);
}

/**
 * Affiche les détails du trajet dans l'interface
 * @param {Object} ride - Les détails du trajet
 */
function displayRideDetails(ride) {
  // Mettre à jour le fil d'Ariane
  if (breadcrumbRoute) {
    breadcrumbRoute.textContent = `Trajet ${ride.from} → ${ride.to}`;
  }

  // Mettre à jour le titre de la page
  document.title = `EcoRide – Trajet ${ride.from} → ${ride.to}`;

  if (!rideDetailContainer) return;

  // Calculer l'heure d'arrivée estimée
  const arrivalTime = calculateArrivalTime(ride.time);

  // Formatage du CO2 économisé
  const co2Value = ride.co2Saved ? `${ride.co2Saved}g CO2/km` : '120g CO2/km';

  // Construire l'affichage HTML des détails du trajet
  rideDetailContainer.innerHTML = `
    <div class="ride-detail__header">
      <div class="back-btn">
        <a href="./covoiturages.html" class="btn-back">
          <i class="fa-solid fa-arrow-left"></i> Retour aux résultats
        </a>
      </div>
      <h1>Trajet ${ride.from} → ${ride.to}</h1>
      <p class="ride-date">${ride.date} à ${ride.time}</p>
    </div>
    
    <div class="ride-detail__content">
      <div class="ride-detail__main">
        <div class="ride-detail__card">
          <div class="ride-detail__driver">
            <img src="${ride.driver.image || '../../assets/images/avatar-placeholder.jpg'}" alt="${ride.driver.name}" class="driver-avatar">
            <div class="driver-info">
              <h2>${ride.driver.name}</h2>
              <div class="rating">
                ${buildStarRating(ride.driver.rating)}
                <span>${ride.driver.rating.toFixed(1)} (${ride.driver.trips} trajets)</span>
              </div>
              ${ride.driverVerified ? '<div class="verified"><i class="fa-solid fa-shield-check"></i> Conducteur vérifié</div>' : ''}
            </div>
          </div>
          
          <div class="ride-detail__route">
            <div class="route-map">
              <div class="route-points">
                <div class="route-point departure">
                  <div class="time">${ride.time}</div>
                  <div class="point"></div>
                  <div class="location">${ride.from}</div>
                </div>
                <div class="route-line">
                  <span class="duration">${ride.duration}</span>
                </div>
                <div class="route-point arrival">
                  <div class="time">${arrivalTime}</div>
                  <div class="point"></div>
                  <div class="location">${ride.to}</div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="ride-detail__info">
            <div class="info-item">
              <i class="fa-solid fa-car"></i>
              <span>${ride.vehicleType}</span>
            </div>
            <div class="info-item">
              <i class="fa-solid fa-leaf"></i>
              <span>${co2Value}</span>
            </div>
            ${ride.electricVehicle ? '<div class="info-item"><i class="fa-solid fa-bolt"></i><span>Véhicule électrique</span></div>' : ''}
            ${ride.nonSmoking ? '<div class="info-item"><i class="fa-solid fa-smoking-ban"></i><span>Non-fumeur</span></div>' : ''}
            ${ride.petsAllowed ? '<div class="info-item"><i class="fa-solid fa-paw"></i><span>Animaux acceptés</span></div>' : ''}
          </div>
        </div>
        
        <div class="ride-detail__preferences">
          <h3>Préférences du conducteur</h3>
          <div class="preferences-list">
            <div class="preference-item">
              <i class="fa-solid fa-music"></i>
              <span>Musique modérée</span>
            </div>
            <div class="preference-item">
              <i class="fa-solid fa-comment"></i>
              <span>Conversation occasionnelle</span>
            </div>
          </div>
        </div>
      </div>
      
      <div class="ride-detail__booking">
        <div class="price-box">
          <div class="price">${ride.price}€</div>
          <div class="seats">
            <i class="fa-solid fa-user-group"></i>
            <span>${ride.availableSeats} place${ride.availableSeats > 1 ? 's' : ''} disponible${ride.availableSeats > 1 ? 's' : ''}</span>
          </div>
        </div>
        <button class="book-btn">Réserver ce trajet</button>
        <button class="contact-btn"><i class="fa-solid fa-message"></i> Contacter ${ride.driver.name.split(' ')[0]}</button>
      </div>
    </div>
  `;

  // Ajouter des événements pour les boutons
  const bookBtn = rideDetailContainer.querySelector('.book-btn');
  if (bookBtn) {
    bookBtn.addEventListener('click', function () {
      alert('Fonctionnalité de réservation à implémenter dans la tâche correspondante.');
    });
  }

  const contactBtn = rideDetailContainer.querySelector('.contact-btn');
  if (contactBtn) {
    contactBtn.addEventListener('click', function () {
      alert('Fonctionnalité de messagerie à implémenter dans la tâche correspondante.');
    });
  }
}

/**
 * Affiche un message d'erreur
 * @param {string} message - Le message d'erreur
 */
function displayError(message) {
  if (!rideDetailContainer) return;

  rideDetailContainer.innerHTML = `
    <div class="error-message">
      <i class="fa-solid fa-circle-exclamation"></i>
      <h2>Erreur</h2>
      <p>${message}</p>
      <a href="./covoiturages.html" class="btn-back">Retour à la recherche</a>
    </div>
  `;
}

/**
 * Récupère les détails fictifs d'un trajet
 * @param {string} rideId - L'identifiant du trajet
 * @returns {Object|null} - Les détails du trajet ou null si non trouvé
 */
function getMockRideDetails(rideId) {
  // Dans un cas réel, ces données viendraient d'une API
  // Pour la démo, on simule un trajet avec l'ID donné

  return {
    id: rideId,
    from: 'Paris',
    to: 'Lyon',
    date: 'Lundi 10 juin',
    time: '08h30',
    price: 25,
    driver: {
      name: 'Sophie D.',
      rating: 4.8,
      trips: 47,
      image: '../../assets/images/profile_Sophie.svg',
    },
    vehicleType: 'Citadine',
    co2Saved: 15,
    availableSeats: 3,
    duration: '3h45',
    electricVehicle: true,
    nonSmoking: true,
    petsAllowed: false,
    driverVerified: true,
  };
}

/**
 * Calcule l'heure d'arrivée en fonction de l'heure de départ
 * @param {string} departureTime - L'heure de départ (format HHhMM)
 * @returns {string} - L'heure d'arrivée (format HHhMM)
 */
function calculateArrivalTime(departureTime) {
  // Parse the departure time
  const parts = departureTime.split('h');
  let hours = parseInt(parts[0], 10);
  let minutes = parseInt(parts[1] || '0', 10);

  // Add a random duration between 30 minutes and 3 hours
  const durationMinutes = Math.floor(Math.random() * 150) + 30;

  // Calculate arrival time
  minutes += durationMinutes;
  hours += Math.floor(minutes / 60);
  minutes = minutes % 60;
  hours = hours % 24;

  return `${hours.toString().padStart(2, '0')}h${minutes.toString().padStart(2, '0')}`;
}

/**
 * Construit le HTML pour afficher la notation en étoiles
 * @param {number} rating - La note du conducteur
 * @returns {string} - Le HTML des étoiles
 */
function buildStarRating(rating) {
  if (!rating) return '';

  const fullStars = Math.floor(rating);
  const halfStar = rating % 1 >= 0.5;
  const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

  let starsHTML = '';

  // Ajouter les étoiles pleines
  for (let i = 0; i < fullStars; i++) {
    starsHTML += '<i class="fa-solid fa-star"></i>';
  }

  // Ajouter une demi-étoile si nécessaire
  if (halfStar) {
    starsHTML += '<i class="fa-solid fa-star-half-stroke"></i>';
  }

  // Ajouter les étoiles vides
  for (let i = 0; i < emptyStars; i++) {
    starsHTML += '<i class="fa-regular fa-star"></i>';
  }

  return starsHTML;
}
