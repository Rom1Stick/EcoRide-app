/**
 * Script pour la page détail d'un covoiturage
 * Implémentation des fonctionnalités dynamiques pour la page de détail de covoiturage
 */
import { API } from '../common/api.js';
import { Auth } from '../common/auth.js';
import { RideService } from '../services/ride-service.js';

// Cache pour améliorer les performances
const cache = {
  ride: new Map(),
  reviews: new Map(),
  driver: new Map(),
};

// Configuration de la pagination des avis
const REVIEWS_PER_PAGE = 3;
let currentReviewPage = 1;
let totalReviewPages = 1;

/**
 * Récupère l'ID du covoiturage depuis l'URL
 * @returns {string|null} L'ID du covoiturage ou null si non trouvé
 */
function getRideId() {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('id');
}

/**
 * Récupère les détails d'un trajet depuis l'API
 * @param {string} rideId - ID du trajet
 * @returns {Promise<object>} Détails du trajet
 */
async function fetchRideDetails(rideId) {
  // Vérifier si les données sont dans le cache
  if (cache.ride.has(rideId)) {
    console.log('Données trouvées dans le cache local');
    return cache.ride.get(rideId);
  }

  // Vérifier si les données sont dans le sessionStorage
  const sessionData = RideService.getFromSession(rideId);
  if (sessionData) {
    console.log('Données trouvées dans le sessionStorage');
    // Stocker dans le cache
    cache.ride.set(rideId, sessionData);
    return sessionData;
  }

  // Afficher l'état de chargement
  showLoadingState();

  try {
    // Utiliser le service pour récupérer les détails
    console.log('Récupération des données depuis l\'API');
    const rideData = await RideService.getRideDetails(rideId);
    
    // Stocker dans le cache
    cache.ride.set(rideId, rideData);
    
    return rideData;
  } catch (error) {
    showErrorMessage('Impossible de charger les détails du trajet');
    console.error('Erreur lors de la récupération du trajet:', error);
    throw error;
  } finally {
    hideLoadingState();
  }
}

/**
 * Récupère les avis sur un conducteur
 * @param {string} driverId - ID du conducteur
 * @param {number} page - Numéro de la page
 * @param {number} limit - Nombre d'avis par page
 * @returns {Promise<object>} Liste des avis
 */
async function fetchDriverReviews(driverId, page = 1, limit = REVIEWS_PER_PAGE) {
  // Clé de cache
  const cacheKey = `${driverId}-${page}-${limit}`;
  
  // Vérifier si les données sont dans le cache
  if (cache.reviews.has(cacheKey)) {
    return cache.reviews.get(cacheKey);
  }
  
  try {
    // Utiliser le service pour récupérer les avis
    const reviewsData = await RideService.getDriverReviews(driverId, page, limit);
    
    // Stocker dans le cache
    cache.reviews.set(cacheKey, reviewsData);
    
    return reviewsData;
  } catch (error) {
    console.error('Erreur lors de la récupération des avis:', error);
    return { reviews: [], total: 0, pages: 1 };
  }
}

/**
 * Récupère les détails d'un conducteur
 * @param {string} driverId - ID du conducteur
 * @returns {Promise<object>} Détails du conducteur
 */
async function fetchDriverDetails(driverId) {
  // Vérifier si les données sont dans le cache
  if (cache.driver.has(driverId)) {
    return cache.driver.get(driverId);
  }
  
  try {
    // Pour l'instant, nous utilisons l'API directement car le service n'a pas encore cette méthode
    const response = await API.get(`/api/users/${driverId}`);
    
    if (response.error) {
      throw new Error(response.message || 'Erreur lors de la récupération des informations du conducteur');
    }
    
    // Stocker dans le cache
    cache.driver.set(driverId, response.data);
    
    return response.data;
  } catch (error) {
    console.error('Erreur lors de la récupération des informations du conducteur:', error);
    return null;
  }
}

/**
 * Affiche un message d'erreur
 * @param {string} message - Message d'erreur à afficher
 */
function showErrorMessage(message) {
  // Créer ou mettre à jour l'élément de message d'erreur
  let errorContainer = document.querySelector('.error-message');
  
  if (!errorContainer) {
    errorContainer = document.createElement('div');
    errorContainer.className = 'error-message';
    document.querySelector('.trip-content').prepend(errorContainer);
  }
  
  errorContainer.textContent = message;
  errorContainer.style.display = 'block';
  
  // Ajouter une classe pour l'animation
  errorContainer.classList.add('error-message--show');
  
  // Cacher le message après 5 secondes
  setTimeout(() => {
    errorContainer.classList.remove('error-message--show');
    setTimeout(() => {
      errorContainer.style.display = 'none';
    }, 300);
  }, 5000);
}

/**
 * Affiche l'état de chargement
 */
function showLoadingState() {
  // Créer l'élément de chargement s'il n'existe pas
  let loadingElement = document.querySelector('.loading-spinner');
  
  if (!loadingElement) {
    loadingElement = document.createElement('div');
    loadingElement.className = 'loading-spinner';
    loadingElement.innerHTML = '<div class="spinner"></div>';
    document.querySelector('.trip-content').prepend(loadingElement);
  }
  
  loadingElement.style.display = 'flex';
}

/**
 * Cache l'état de chargement
 */
function hideLoadingState() {
  const loadingElement = document.querySelector('.loading-spinner');
  
  if (loadingElement) {
    loadingElement.style.display = 'none';
  }
}

/**
 * Met à jour l'interface avec les détails du trajet
 * @param {object} ride - Données du trajet
 */
function updateRideDetails(ride) {
  // Mettre à jour les informations du trajet (version desktop)
  document.getElementById('trip-date').textContent = formatDate(ride.date);
  document.getElementById('trip-route').textContent = `${ride.departure} → ${ride.destination}`;
  document.getElementById('trip-price').textContent = `${ride.price}€`;
  
  // Mettre à jour les statistiques (version desktop)
  document.getElementById('trip-times').textContent = 
    `Départ à ${formatTime(ride.departureTime)} • Arrivée à ${formatTime(ride.arrivalTime)}`;
  document.getElementById('trip-duration').textContent = 
    `${calculateDuration(ride.departureTime, ride.arrivalTime)} de trajet`;
  
  document.getElementById('seats-available').textContent = 
    `${ride.availableSeats} place${ride.availableSeats > 1 ? 's' : ''} disponible${ride.availableSeats > 1 ? 's' : ''}`;
  
  document.getElementById('co2-info').textContent = 
    `${ride.co2Emission}g CO₂/km`;
  document.getElementById('environmental-impact').textContent = 
    getEnvironmentalImpactLabel(ride.co2Emission);
  
  // Mettre à jour les informations du véhicule (version desktop)
  document.getElementById('vehicle-model').textContent = ride.vehicle.model;
  document.getElementById('vehicle-specs').textContent = 
    `${ride.vehicle.year} • ${ride.vehicle.fuelType}`;
  
  // Mettre à jour les tags du véhicule (version desktop)
  const tagsContainer = document.getElementById('vehicle-tags');
  tagsContainer.innerHTML = '';
  
  ride.vehicle.tags.forEach(tag => {
    const tagElement = document.createElement('span');
    tagElement.textContent = tag;
    tagsContainer.appendChild(tagElement);
  });
  
  // Ajouter un badge pour les véhicules électriques (si energie_id = 1)
  if (ride.vehicle.energyId === 1 || ride.vehicle.energy === 'Électrique') {
    // Création du badge électrique pour desktop
    const electricBadge = document.createElement('div');
    electricBadge.className = 'electric-badge';
    electricBadge.innerHTML = '<i class="fa-solid fa-bolt"></i> Véhicule électrique';
    
    const vehicleSection = document.querySelector('.vehicle-section');
    // Vérifier si le badge existe déjà pour éviter les doublons
    if (!vehicleSection.querySelector('.electric-badge')) {
      vehicleSection.appendChild(electricBadge);
    }
    
    // Création du badge électrique pour mobile
    const mobileElectricBadge = document.createElement('div');
    mobileElectricBadge.className = 'electric-badge';
    mobileElectricBadge.innerHTML = '<i class="fa-solid fa-bolt"></i> Véhicule électrique';
    
    const mobileVehicleSection = document.querySelector('.mobile-vehicle');
    // Vérifier si le badge existe déjà pour éviter les doublons
    if (mobileVehicleSection && !mobileVehicleSection.querySelector('.electric-badge')) {
      mobileVehicleSection.appendChild(mobileElectricBadge);
    }
  }
  
  // Mettre à jour les préférences
  updatePreferences(ride.preferences);
  
  // Mettre à jour le prix total 
  document.getElementById('total-price').textContent = `${ride.price}€`;
  
  // Mettre à jour la version mobile
  document.getElementById('mobile-trip-date').textContent = formatDate(ride.date);
  document.getElementById('mobile-trip-route').textContent = `${ride.departure} → ${ride.destination}`;
  document.getElementById('mobile-price').textContent = `${ride.price}€`;
  document.getElementById('mobile-total-price').textContent = `${ride.price}€`;
  
  document.getElementById('mobile-trip-times').textContent = 
    `Départ à ${formatTime(ride.departureTime)} • Arrivée à ${formatTime(ride.arrivalTime)}`;
  document.getElementById('mobile-trip-duration').textContent = 
    `${calculateDuration(ride.departureTime, ride.arrivalTime)} de trajet`;
  
  document.getElementById('mobile-seats-available').textContent = 
    `${ride.availableSeats} place${ride.availableSeats > 1 ? 's' : ''} disponible${ride.availableSeats > 1 ? 's' : ''}`;
  
  document.getElementById('mobile-co2-info').textContent = 
    `${ride.co2Emission}g CO₂/km`;
  document.getElementById('mobile-environmental-impact').textContent = 
    getEnvironmentalImpactLabel(ride.co2Emission);
  
  document.getElementById('mobile-vehicle-model').textContent = ride.vehicle.model;
  document.getElementById('mobile-vehicle-specs').textContent = 
    `${ride.vehicle.year} • ${ride.vehicle.fuelType}`;
  
  // Mettre à jour les tags du véhicule (version mobile)
  const mobileTagsContainer = document.getElementById('mobile-vehicle-tags');
  mobileTagsContainer.innerHTML = '';
  
  ride.vehicle.tags.forEach(tag => {
    const tagElement = document.createElement('span');
    tagElement.className = 'mobile-vehicle__tag';
    tagElement.textContent = tag;
    mobileTagsContainer.appendChild(tagElement);
  });
  
  // Vérifier si des places sont disponibles
  updateBookingButton(ride.availableSeats);
  
  // Ajouter une animation de transition
  document.querySelector('.trip-content').classList.add('fade-in');
}

/**
 * Met à jour l'interface avec les informations du conducteur
 * @param {object} driver - Données du conducteur
 */
function updateDriverDetails(driver) {
  // Mettre à jour les informations du conducteur (version desktop)
  const driverName = `${driver.firstName} ${driver.lastName.charAt(0)}.`;
  document.getElementById('driver-name').textContent = driverName;
  document.getElementById('driver-rating').textContent = driver.rating;
  document.getElementById('driver-trips').textContent = `${driver.ridesCount || 0} trajets`;
  
  // Mettre à jour l'image du conducteur (version desktop)
  const driverAvatar = document.getElementById('driver-avatar');
  driverAvatar.src = driver.avatar;
  driverAvatar.alt = driverName;
  
  // Afficher le badge vérifié si applicable
  const verifiedBadge = document.getElementById('verified-badge');
  verifiedBadge.style.display = driver.verified ? 'block' : 'none';
  
  // Mettre à jour le taux et délai de réponse
  document.getElementById('response-rate').textContent = `${driver.responseRate || 0}%`;
  document.getElementById('response-time').textContent = driver.responseTime || '< 24 heures';
  
  // Mettre à jour le texte du bouton de contact
  document.getElementById('contact-btn').textContent = `Contacter ${driver.firstName}`;
  
  // Mettre à jour la version mobile
  document.getElementById('mobile-driver-name').textContent = driverName;
  document.getElementById('mobile-driver-rating').textContent = driver.rating;
  document.getElementById('mobile-driver-trips').textContent = `${driver.ridesCount || 0} trajets`;
  
  const mobileAvatar = document.getElementById('mobile-driver-avatar');
  mobileAvatar.src = driver.avatar;
  mobileAvatar.alt = driverName;
  
  document.getElementById('mobile-verified-badge').style.display = driver.verified ? 'block' : 'none';
  document.getElementById('mobile-response-rate').textContent = `${driver.responseRate || 0}%`;
  document.getElementById('mobile-response-time').textContent = driver.responseTime || '< 24 heures';
  document.getElementById('mobile-contact-btn').textContent = `Contacter ${driver.firstName}`;
}

/**
 * Met à jour l'affichage des avis
 * @param {object} reviewsData - Données des avis
 * @param {boolean} showAll - Afficher tous les avis ou seulement les 3 premiers
 */
function updateReviews(reviewsData, showAll = false) {
  // Calcul de la note moyenne si non fournie
  const averageRating = reviewsData.averageRating || (reviewsData.reviews.length > 0 
    ? (reviewsData.reviews.reduce((sum, review) => sum + review.rating, 0) / reviewsData.reviews.length).toFixed(1)
    : '0.0');

  // Déterminer les avis à afficher
  const reviewsToShow = showAll ? reviewsData.reviews : reviewsData.reviews.slice(0, 3);

  // Le nombre d'avis affiché entre parenthèses doit toujours être le total
  const countToDisplay = reviewsData.total || reviewsData.reviews.length;

  // Mettre à jour la note moyenne et le nombre d'avis (desktop)
  document.getElementById('reviews-rating').textContent = averageRating;
  document.getElementById('reviews-count').textContent = `(${countToDisplay})`;

  // Mettre à jour la note moyenne et le nombre d'avis (mobile)
  document.getElementById('mobile-reviews-rating').textContent = averageRating;
  document.getElementById('mobile-reviews-count').textContent = `(${countToDisplay})`;

  // Mettre à jour la liste des avis (desktop)
  const reviewsList = document.getElementById('reviews-list');
  reviewsList.innerHTML = '';
  if (reviewsToShow.length === 0) {
    const emptyReview = document.createElement('div');
    emptyReview.className = 'empty-reviews';
    emptyReview.textContent = 'Aucun avis pour le moment';
    reviewsList.appendChild(emptyReview);
  } else {
    reviewsToShow.forEach(review => {
      const reviewElement = document.createElement('div');
      reviewElement.className = 'review';
      reviewElement.innerHTML = `
        <div class=\"review-header\">
          <img src=\"${review.authorAvatar}\" alt=\"${review.authorName}\" />
          <div class=\"info\">
            <div class=\"author\">${review.authorName}</div>
            <div class=\"date\">${formatReviewDate(review.date)}</div>
          </div>
          <div class=\"score\">
            <i class=\"fa-solid fa-star\"></i>
            <span>${typeof review.rating === 'number' ? review.rating.toFixed(1) : review.rating}</span>
          </div>
        </div>
        <p>${review.comment}</p>
      `;
      reviewsList.appendChild(reviewElement);
    });
  }

  // Mettre à jour la liste des avis (mobile)
  const mobileReviewsList = document.getElementById('mobile-reviews-list');
  mobileReviewsList.innerHTML = '';
  if (reviewsToShow.length === 0) {
    const emptyReview = document.createElement('div');
    emptyReview.className = 'empty-reviews';
    emptyReview.textContent = 'Aucun avis pour le moment';
    mobileReviewsList.appendChild(emptyReview);
  } else {
    reviewsToShow.forEach(review => {
      const reviewElement = document.createElement('div');
      reviewElement.className = 'mobile-review';
      reviewElement.innerHTML = `
        <div class=\"mobile-review__header\">
          <img src=\"${review.authorAvatar}\" class=\"mobile-review__avatar\" alt=\"${review.authorName}\" />
          <div class=\"mobile-review__info\">
            <div class=\"mobile-review__author\">${review.authorName}</div>
            <div class=\"mobile-review__date\">${formatReviewDate(review.date)}</div>
          </div>
          <div class=\"mobile-review__score\">
            <i class=\"fa-solid fa-star\"></i>
            <span>${typeof review.rating === 'number' ? review.rating.toFixed(1) : review.rating}</span>
          </div>
        </div>
        <p class=\"mobile-review__text\">${review.comment}</p>
      `;
      mobileReviewsList.appendChild(reviewElement);
    });
  }

  // Animation
  const reviewElements = document.querySelectorAll('.review, .mobile-review');
  reviewElements.forEach((element, index) => {
    element.style.opacity = '0';
    element.style.transform = 'translateY(10px)';
    setTimeout(() => {
      element.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
      element.style.opacity = '1';
      element.style.transform = 'translateY(0)';
    }, 100 + index * 100);
  });

  // Mettre à jour la pagination des avis
  updatePaginationControls(reviewsData.total, reviewsData.reviews.length, showAll);

  // Stocker l'état courant pour le bouton "voir plus/moins"
  updateReviews._lastData = reviewsData;
  updateReviews._showAll = showAll;
}

/**
 * Met à jour les contrôles de pagination
 * @param {number} totalReviews - Nombre total d'avis
 * @param {number} reviewsLength - Nombre d'avis générés
 * @param {boolean} showAll - Affiche-t-on tout ?
 */
function updatePaginationControls(totalReviews, reviewsLength, showAll) {
  // Montrer ou cacher le bouton "Voir tous les avis" selon le nombre d'avis
  const viewAllButtons = document.querySelectorAll('.view-all, .mobile-reviews__view-all');
  viewAllButtons.forEach(button => {
    if (reviewsLength <= 3) {
      button.style.display = 'none';
    } else {
      button.style.display = 'block';
      button.textContent = showAll ? 'Voir moins d\'avis' : 'Voir tous les avis';
    }
  });
}

/**
 * Met à jour les préférences du conducteur
 * @param {object} preferences - Préférences du conducteur
 */
function updatePreferences(preferences) {
  // Préparation des préférences à afficher
  const prefsList = [
    {
      id: 'smoking',
      icon: preferences.smoking ? 'fa-smoking' : 'fa-smoking-ban',
      text: preferences.smoking ? 'Fumeur accepté' : 'Non-fumeur'
    },
    {
      id: 'pets',
      icon: 'fa-paw',
      text: preferences.pets ? 'Animaux acceptés' : 'Animaux non acceptés'
    },
    {
      id: 'music',
      icon: 'fa-music',
      text: preferences.music ? 'Musique ok' : 'Pas de musique'
    },
    {
      id: 'talking',
      icon: 'fa-message',
      text: preferences.talking ? 'Discussion ok' : 'Préfère le calme'
    }
  ];
  
  // Mise à jour des préférences (version desktop)
  const prefsContainer = document.getElementById('prefs-list');
  prefsContainer.innerHTML = '';
  
  prefsList.forEach(pref => {
    if (preferences[pref.id] !== undefined) {
      const prefItem = document.createElement('div');
      prefItem.className = 'pref-item';
      prefItem.innerHTML = `
        <i class="fa-solid ${pref.icon}"></i>
        <span>${pref.text}</span>
      `;
      prefsContainer.appendChild(prefItem);
    }
  });
  
  // Mise à jour des préférences (version mobile)
  const mobilePrefsList = document.getElementById('mobile-prefs-list');
  mobilePrefsList.innerHTML = '';
  
  prefsList.forEach(pref => {
    if (preferences[pref.id] !== undefined) {
      const prefItem = document.createElement('div');
      prefItem.className = 'mobile-prefs__item';
      prefItem.innerHTML = `
        <i class="fa-solid ${pref.icon}"></i>
        <span>${pref.text}</span>
      `;
      mobilePrefsList.appendChild(prefItem);
    }
  });
}

/**
 * Met à jour le bouton de réservation en fonction des places disponibles
 * @param {number} availableSeats - Nombre de places disponibles
 */
function updateBookingButton(availableSeats) {
  const bookingButtons = document.querySelectorAll('.btn-book, .mobile-booking-footer__btn');
  
  bookingButtons.forEach(button => {
    if (availableSeats <= 0) {
      button.disabled = true;
      button.textContent = 'Complet';
      button.classList.add('disabled');
    } else {
      button.disabled = false;
      button.textContent = 'Réserver';
      button.classList.remove('disabled');
    }
  });
}

/**
 * Initialise les détails du trajet
 * @param {string} rideId - ID du trajet
 */
async function initializeRideDetails(rideId) {
  try {
    // Récupérer les détails du trajet
    const rideData = await fetchRideDetails(rideId);
    
    // Mettre à jour les informations du trajet
    updateRideDetails(rideData);
    
    // Vérifier si les données du conducteur sont déjà incluses dans les données du trajet
    if (rideData.driver) {
      // Utiliser les données du conducteur incluses
      console.log('Utilisation des données du conducteur incluses dans les données du trajet');
      updateDriverDetails(rideData.driver);
      
      // Générer des avis aléatoires
      const mockReviews = generateRandomReviews();
      
      // Mettre à jour les avis
      updateReviews(mockReviews);
    } else {
      // Récupérer les détails du conducteur si non inclus
      const driverData = await fetchDriverDetails(rideData.driverId);
      
      // Mettre à jour les informations du conducteur
      updateDriverDetails(driverData);
      
      try {
        // Essayer de récupérer les avis sur le conducteur
        const reviewsData = await fetchDriverReviews(rideData.driverId);
        
        // Si aucun avis n'est trouvé ou si le tableau d'avis est vide, générer des avis aléatoires
        if (!reviewsData || !reviewsData.reviews || reviewsData.reviews.length === 0) {
          console.log('Aucun avis trouvé, génération d\'avis aléatoires');
          const randomReviews = generateRandomReviews();
          updateReviews(randomReviews);
        } else {
          // Mettre à jour les avis avec les données reçues
          updateReviews(reviewsData);
        }
      } catch (error) {
        console.error('Erreur lors de la récupération des avis, génération d\'avis aléatoires:', error);
        // En cas d'erreur, générer des avis aléatoires
        const randomReviews = generateRandomReviews();
        updateReviews(randomReviews);
      }
    }
  } catch (error) {
    console.error('Erreur lors de l\'initialisation des détails du trajet:', error);
    showErrorMessage('Une erreur s\'est produite lors du chargement des données');
  }
}

/**
 * Gère le bouton de retour
 */
function setupBackButton() {
  const backButtons = document.querySelectorAll('.header__logo button, .mobile-header__back-btn');

  backButtons.forEach(button => {
    button.addEventListener('click', () => {
      // Retour à la page précédente si elle existe, sinon à la liste des covoiturages
      if (document.referrer && document.referrer.includes(window.location.origin)) {
        window.history.back();
      } else {
        window.location.href = '/frontend/pages/public/covoiturages.html';
      }
    });
  });
}

/**
 * Gère le bouton de partage
 */
function setupShareButton() {
  const shareButtons = document.querySelectorAll('.header__menu, .mobile-header__share-btn');

  shareButtons.forEach(button => {
    button.addEventListener('click', () => {
      // Vérifier si l'API Web Share est disponible
      if (navigator.share) {
        navigator
          .share({
            title: 'EcoRide - Détails du trajet',
            text: 'Découvrez ce trajet sur EcoRide',
            url: window.location.href,
          })
          .catch((error) => console.error('Erreur lors du partage:', error));
      } else {
        // Fallback: copier l'URL dans le presse-papier
        navigator.clipboard
          .writeText(window.location.href)
          .then(() => {
            showToast('Lien copié dans le presse-papier !');
          })
          .catch((error) => console.error('Erreur lors de la copie:', error));
      }
    });
  });
}

/**
 * Affiche un toast (notification temporaire)
 * @param {string} message - Message à afficher
 */
function showToast(message) {
  // Créer l'élément toast s'il n'existe pas
  let toastElement = document.querySelector('.toast');
  
  if (!toastElement) {
    toastElement = document.createElement('div');
    toastElement.className = 'toast';
    document.body.appendChild(toastElement);
  }
  
  toastElement.textContent = message;
  toastElement.classList.add('toast--visible');
  
  // Cacher le toast après 3 secondes
  setTimeout(() => {
    toastElement.classList.remove('toast--visible');
  }, 3000);
}

/**
 * Gère le bouton de réservation
 */
function setupBookingButton() {
  const bookingButtons = document.querySelectorAll('.btn-book, .mobile-booking-footer__btn');

  bookingButtons.forEach(button => {
    button.addEventListener('click', async () => {
      const isLoggedIn = Auth.isLoggedIn();

      if (!isLoggedIn) {
        // Rediriger vers la page de connexion avec une redirection de retour
        const returnUrl = encodeURIComponent(window.location.href);
        window.location.href = `/frontend/pages/public/login.html?redirect=${returnUrl}`;
        return;
      }

      const rideId = getRideId();
      
      if (!rideId) {
        showErrorMessage('ID de trajet non trouvé');
        return;
      }
      
      // Désactiver le bouton pendant le traitement
      bookingButtons.forEach(btn => {
        btn.disabled = true;
        btn.textContent = 'Traitement...';
      });
      
      try {
        // Appel à l'API pour effectuer la réservation
        const response = await API.post(`/api/rides/${rideId}/book`, {
          seats: 1  // Par défaut 1 place
        });
        
        if (response.error) {
          throw new Error(response.message || 'Erreur lors de la réservation');
        }
        
        // Réservation réussie
        showToast('Réservation effectuée avec succès !');
        
        // Mettre à jour les places disponibles
        const rideData = await fetchRideDetails(rideId);
        updateRideDetails(rideData);
      } catch (error) {
        console.error('Erreur lors de la réservation:', error);
        showErrorMessage(error.message || 'Erreur lors de la réservation');
        
        // Réactiver le bouton
        bookingButtons.forEach(btn => {
          btn.disabled = false;
          btn.textContent = 'Réserver';
        });
      }
    });
  });
}

/**
 * Gère le bouton de contact
 */
function setupContactButton() {
  const contactButtons = document.querySelectorAll('.contact-btn, .mobile-driver__contact-btn');

  contactButtons.forEach(button => {
    button.addEventListener('click', async () => {
      const isLoggedIn = Auth.isLoggedIn();

      if (!isLoggedIn) {
        // Rediriger vers la page de connexion avec une redirection de retour
        const returnUrl = encodeURIComponent(window.location.href);
        window.location.href = `/frontend/pages/public/login.html?redirect=${returnUrl}`;
        return;
      }

      // Récupérer les détails du trajet
      const rideId = getRideId();
      const rideData = cache.ride.get(rideId);
      
      if (!rideData) {
        showErrorMessage('Données du trajet non disponibles');
        return;
      }
      
      // TODO: Implémenter la logique de messagerie
      showToast('La messagerie sera bientôt disponible !');
    });
  });
}

/**
 * Gère le bouton "Voir tous les avis"
 */
function setupReviewsButton() {
  const viewAllButtons = document.querySelectorAll('.view-all, .mobile-reviews__view-all');

  viewAllButtons.forEach(button => {
    button.addEventListener('click', () => {
      const data = updateReviews._lastData;
      const showAll = !updateReviews._showAll;
      updateReviews(data, showAll);
    });
  });
}

/**
 * Formater une date
 * @param {string} dateStr - Date au format ISO
 * @returns {string} Date formatée
 */
function formatDate(dateStr) {
  const options = { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' };
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', options);
}

/**
 * Formater une heure
 * @param {string} timeStr - Heure au format HH:MM
 * @returns {string} Heure formatée
 */
function formatTime(timeStr) {
  return timeStr;
}

/**
 * Calculer la durée entre deux heures
 * @param {string} departureTime - Heure de départ
 * @param {string} arrivalTime - Heure d'arrivée
 * @returns {string} Durée formatée
 */
function calculateDuration(departureTime, arrivalTime) {
  const [departureHours, departureMinutes] = departureTime.split(':').map(Number);
  const [arrivalHours, arrivalMinutes] = arrivalTime.split(':').map(Number);
  
  // Convertir en minutes
  let departureTotalMinutes = departureHours * 60 + departureMinutes;
  let arrivalTotalMinutes = arrivalHours * 60 + arrivalMinutes;
  
  // Gérer le cas où l'arrivée est le jour suivant
  if (arrivalTotalMinutes < departureTotalMinutes) {
    arrivalTotalMinutes += 24 * 60;
  }
  
  // Calculer la différence
  const durationMinutes = arrivalTotalMinutes - departureTotalMinutes;
  
  // Formater le résultat
  const hours = Math.floor(durationMinutes / 60);
  const minutes = durationMinutes % 60;
  
  if (minutes === 0) {
    return `${hours}h`;
  } else {
    return `${hours}h${minutes}`;
  }
}

/**
 * Obtenir le libellé d'impact environnemental en fonction des émissions de CO2
 * @param {number} co2Emission - Émissions de CO2 en g/km
 * @returns {string} Libellé d'impact
 */
function getEnvironmentalImpactLabel(co2Emission) {
  if (co2Emission <= 50) {
    return 'Impact environnemental très faible';
  } else if (co2Emission <= 100) {
    return 'Impact environnemental faible';
  } else if (co2Emission <= 150) {
    return 'Impact environnemental modéré';
  } else if (co2Emission <= 200) {
    return 'Impact environnemental élevé';
  } else {
    return 'Impact environnemental très élevé';
  }
}

/**
 * Formater la date d'un avis
 * @param {string} dateStr - Date au format ISO
 * @returns {string} Date formatée
 */
function formatReviewDate(dateStr) {
  const options = { month: 'long', year: 'numeric' };
  const date = new Date(dateStr);
  return date.toLocaleDateString('fr-FR', options);
}

/**
 * Initialisation au chargement de la page
 */
document.addEventListener('DOMContentLoaded', () => {
  // Vérifier si on a un ID de trajet
  const rideId = getRideId();
  
  if (!rideId) {
    console.warn("ID de trajet non trouvé dans l'URL");
    showErrorMessage("ID de trajet manquant dans l'URL");
    
    // En mode démonstration, on charge des données fictives
    mockRideData();
  } else {
    // Initialiser la page avec les données du trajet
    initializeRideDetails(rideId);
  }
  
  // Configurer les boutons
  setupBackButton();
  setupShareButton();
  setupBookingButton();
  setupContactButton();
  setupReviewsButton();
  
  // Ajouter une classe CSS pour les animations
  document.body.classList.add('js-enabled');
  
  console.log('Page de détail du covoiturage initialisée');
});

/**
 * Charge des données fictives pour la démonstration
 */
function mockRideData() {
  // Simulation d'un délai réseau
  setTimeout(() => {
    // Trajet fictif
    const mockRide = {
      id: '123',
      departure: 'Paris',
      destination: 'Lyon',
      date: '2025-03-24T00:00:00.000Z', // S'assurer que c'est un format ISO valide
      departureTime: '08:00',
      arrivalTime: '11:30',
      price: 25,
      availableSeats: 2,
      co2Emission: 120,
      driverId: '456',
      vehicle: {
        model: 'Tesla Model 3',
        year: '2023',
        fuelType: 'Électrique',
        tags: ['Confort ++', 'Écologique']
      },
      preferences: {
        smoking: false,
        pets: true,
        music: true,
        talking: true
      }
    };
    
    // Conducteur fictif
    const mockDriver = {
      id: '456',
      firstName: 'Thomas',
      lastName: 'Dubois',
      avatar: 'https://storage.googleapis.com/uxpilot-auth.appspot.com/avatars/avatar-2.jpg',
      rating: 4.8,
      ridesCount: 42,
      responseRate: 98,
      responseTime: '< 1 heure',
      verified: true
    };
    
    // Générer des avis aléatoires
    const mockReviews = generateRandomReviews(5); // Générer exactement 5 avis
    
    // Stocker dans le cache
    cache.ride.set('123', mockRide);
    cache.driver.set('456', mockDriver);
    cache.reviews.set('456-1-3', mockReviews);
    
    // Mettre à jour l'interface
    updateRideDetails(mockRide);
    updateDriverDetails(mockDriver);
    updateReviews(mockReviews);
    
    // Cacher l'état de chargement
    hideLoadingState();
  }, 1000);
}

/**
 * Génère des avis aléatoires pour un conducteur
 * @param {number} count - Nombre d'avis à générer (par défaut 3 à 8)
 * @returns {Object} Données des avis générées
 */
function generateRandomReviews(count = 0) {
  // Si count est 0, générer un nombre aléatoire d'avis entre 3 et 8
  const reviewCount = count || Math.floor(Math.random() * 6) + 3;
  
  // Total des avis (plus que ceux affichés pour la pagination)
  const totalReviews = Math.floor(Math.random() * 15) + reviewCount;
  
  // Noms et prénoms pour les auteurs des avis
  const firstNames = ['Marie', 'Pierre', 'Sophie', 'Thomas', 'Julie', 'Nicolas', 'Emma', 'Lucas', 'Camille', 'Léo'];
  const lastInitials = ['L.', 'M.', 'D.', 'B.', 'R.', 'G.', 'P.', 'T.', 'V.', 'C.'];
  
  // Commentaires positifs possibles
  const positiveComments = [
    'Excellent conducteur, très ponctuel et sympathique !',
    'Trajet très agréable, je recommande vivement !',
    'Voiture confortable et conducteur accueillant.',
    'Super expérience, discussion intéressante pendant le trajet.',
    'Conducteur fiable et prudent, voyage parfait.',
    'Très bonne communication et ponctualité impeccable.',
    'Personne très sympathique, voyage sans accroc.',
    'Ambiance conviviale et trajet sécuritaire.',
    'Conducteur professionnel et voyage agréable.',
    'Très bon accueil, je le recommande !'
  ];
  
  // Commentaires mitigés possibles (pour les notes moyennes)
  const mixedComments = [
    'Bien dans l\'ensemble, quelques minutes de retard.',
    'Conducteur correct mais peu bavard.',
    'Trajet ok, rien à signaler de particulier.',
    'Voiture confortable mais conduite un peu rapide.',
    'Correct mais communication limitée.'
  ];
  
  // Avatars possibles
  const avatars = [
    'https://storage.googleapis.com/uxpilot-auth.appspot.com/avatars/avatar-1.jpg',
    'https://storage.googleapis.com/uxpilot-auth.appspot.com/avatars/avatar-2.jpg',
    'https://storage.googleapis.com/uxpilot-auth.appspot.com/avatars/avatar-3.jpg',
    'https://storage.googleapis.com/uxpilot-auth.appspot.com/avatars/avatar-4.jpg',
    'https://storage.googleapis.com/uxpilot-auth.appspot.com/avatars/avatar-5.jpg',
    'https://storage.googleapis.com/uxpilot-auth.appspot.com/avatars/avatar-6.jpg',
    'https://storage.googleapis.com/uxpilot-auth.appspot.com/avatars/avatar-7.jpg',
    'https://storage.googleapis.com/uxpilot-auth.appspot.com/avatars/avatar-8.jpg'
  ];
  
  // Générer les avis
  const reviews = [];
  let totalRating = 0;
  
  for (let i = 0; i < reviewCount; i++) {
    // Générer une note entre 3.5 et 5.0
    const rating = Math.random() <= 0.8 
      ? (Math.random() * 0.5 + 4.5) // 80% de chance d'avoir entre 4.5 et 5.0
      : (Math.random() * 1.0 + 3.5); // 20% de chance d'avoir entre 3.5 et 4.5
    
    // Arrondir à la décimale 0.5 près
    const roundedRating = Math.round(rating * 2) / 2;
    totalRating += roundedRating;
    
    // Sélectionner aléatoirement les informations de l'auteur
    const authorFirstName = firstNames[Math.floor(Math.random() * firstNames.length)];
    const authorLastInitial = lastInitials[Math.floor(Math.random() * lastInitials.length)];
    const authorName = `${authorFirstName} ${authorLastInitial}`;
    const authorAvatar = avatars[Math.floor(Math.random() * avatars.length)];
    
    // Sélectionner un commentaire en fonction de la note
    const comment = roundedRating >= 4.5 
      ? positiveComments[Math.floor(Math.random() * positiveComments.length)]
      : mixedComments[Math.floor(Math.random() * mixedComments.length)];
    
    // Générer une date dans les 6 derniers mois
    const date = new Date();
    date.setMonth(date.getMonth() - Math.floor(Math.random() * 6));
    
    reviews.push({
      id: `review-${i}`,
      authorId: `author-${i}`,
      authorName,
      authorAvatar,
      rating: roundedRating,
      comment,
      date: date.toISOString()
    });
  }
  
  // Calculer la note moyenne
  const averageRating = reviews.length > 0 ? totalRating / reviews.length : 0;
  
  return {
    reviews,
    total: totalReviews,
    pages: Math.ceil(totalReviews / REVIEWS_PER_PAGE),
    averageRating: parseFloat(averageRating.toFixed(1))
  };
}
