/**
 * Gestion de la recherche et de l'affichage des covoiturages
 */

// Imports
import { RideService } from '../services/ride-service.js';
import { LocationService } from '../services/location-service.js';
import { API } from '../common/api.js';

// Ajout des styles pour le toast d'information
const toastStyles = document.createElement('style');
toastStyles.textContent = `
  .toast--info {
    background-color: var(--color-primary-light);
    color: var(--color-primary-dark);
    border-left: 4px solid var(--color-primary);
  }
  
  .toast--visible {
    animation: slideIn 0.3s ease-out forwards;
  }
  
  @keyframes slideIn {
    0% {
      transform: translateY(100%);
      opacity: 0;
    }
    100% {
      transform: translateY(0);
      opacity: 1;
    }
  }
`;
document.head.appendChild(toastStyles);

// URL de l'API Adresse du gouvernement français
const API_URL = 'https://api-adresse.data.gouv.fr';

// Cache pour les résultats d'autocomplétion
const autocompleteCache = new Map();

// Éléments DOM
const fromInput = document.getElementById('from');
const toInput = document.getElementById('to');
const dateInput = document.getElementById('date');
const searchForm = document.getElementById('search-form');
const fromAutocompleteContainer = document.getElementById('from-autocomplete');
const toAutocompleteContainer = document.getElementById('to-autocomplete');
const resultsList = document.getElementById('results-list');
const resultsCount = document.getElementById('results-count');

// On attend l'initialisation complète pour configurer la date
// afin de permettre aux paramètres d'URL d'être prioritaires
const today = new Date().toISOString().split('T')[0];

/**
 * Initialisation des composants
 */
function init() {
  // Vérification que les éléments du DOM sont bien présents
  if (
    !fromInput ||
    !toInput ||
    !dateInput ||
    !searchForm ||
    !fromAutocompleteContainer ||
    !toAutocompleteContainer
  ) {
    console.error('Certains éléments du DOM sont manquants.');
    return;
  }

  // Configuration du sélecteur de date avec date minimale = aujourd'hui
  dateInput.setAttribute('min', today);
  
  // Récupérer les paramètres d'URL pour pré-remplir le formulaire et lancer la recherche
  const hasUrlParams = setFormFromUrlParams();
  
  // Si aucune date n'a été spécifiée dans l'URL, utiliser la date du jour
  if (!hasUrlParams || !dateInput.value) {
    dateInput.value = today;
  }

  // Initialisation des champs d'autocomplétion
  setupAutocomplete(fromInput, fromAutocompleteContainer);
  setupAutocomplete(toInput, toAutocompleteContainer);

  // Gestion de la soumission du formulaire
  searchForm.addEventListener('submit', handleSearch);

  // Configuration des boutons de la section résultats
  setupResultsButtons();

  // Gestion des filtres de résultats
  const filterSelect = document.getElementById('sort-filter');
  if (filterSelect) {
    filterSelect.addEventListener('change', function () {
      const filterType = this.value;
      if (filterType) {
        applyFilter(filterType);
      }
    });
  }

  // Si aucun paramètre d'URL, simuler une recherche avec des villes par défaut
  if (!hasUrlParams) {
    simulateSearch();
  }

  // Gestion des filtres avancés
  const toggleFiltersBtn = document.getElementById('toggle-filters');
  const filtersContent = document.getElementById('filters-content');

  if (toggleFiltersBtn && filtersContent) {
    // Vérifier si on est en mode desktop
    const isDesktop = window.innerWidth >= 1024; // Correspond à notre mixin desktop

    // En mobile : masquer les filtres avancés par défaut
    // En desktop : toujours afficher les filtres
    if (!isDesktop) {
      // Forcer l'état fermé sur mobile
      filtersContent.style.display = 'none';
      toggleFiltersBtn.setAttribute('aria-expanded', 'false');
      // S'assurer que l'icône est correcte
      const chevronIcon = toggleFiltersBtn.querySelector('.fa-chevron-down, .fa-chevron-up');
      if (chevronIcon) {
        chevronIcon.className = 'fa-solid fa-chevron-down';
      }
    } else {
      filtersContent.style.display = 'flex';
      toggleFiltersBtn.setAttribute('aria-expanded', 'true');
    }

    // Gestion du toggle des filtres avancés
    toggleFiltersBtn.addEventListener('click', function () {
      const expanded = this.getAttribute('aria-expanded') === 'true';
      this.setAttribute('aria-expanded', !expanded);

      // Référence au contenu à afficher/masquer
      const filtersContent = document.getElementById('filters-content');

      // Référence à l'icône de chevron
      const chevronIcon = this.querySelector('.fa-chevron-down, .fa-chevron-up');

      if (expanded) {
        // Animation de fermeture
        if (filtersContent) {
          filtersContent.style.display = 'none';
        }

        // Changer l'icône pour indiquer que le contenu est fermé
        if (chevronIcon) {
          chevronIcon.className = 'fa-solid fa-chevron-down';
        }
      } else {
        // Animation d'ouverture
        if (filtersContent) {
          filtersContent.style.display = 'flex';
        }

        // Changer l'icône pour indiquer que le contenu est ouvert
        if (chevronIcon) {
          chevronIcon.className = 'fa-solid fa-chevron-up';
        }
      }
    });

    // Ajuster l'affichage des filtres lors du redimensionnement de la fenêtre
    window.addEventListener('resize', function () {
      const isDesktop = window.innerWidth >= 1024;
      if (isDesktop) {
        filtersContent.style.display = 'flex';
        toggleFiltersBtn.setAttribute('aria-expanded', 'true');
      } else if (toggleFiltersBtn.getAttribute('aria-expanded') === 'false') {
        filtersContent.style.display = 'none';
      }
    });

    // Gestion du changement de valeur du prix maximum
    const maxPriceInput = document.getElementById('max-price');
    if (maxPriceInput) {
      maxPriceInput.addEventListener('input', function () {
        document.getElementById('price-label').textContent = `Prix maximum: ${this.value}€`;
        maxPriceInput.setAttribute('aria-valuenow', this.value);
        maxPriceInput.setAttribute('aria-valuetext', `${this.value}€`);

        // Appliquer les filtres immédiatement si des résultats sont déjà affichés
        if (resultsList && resultsList.children.length > 0) {
          applyAdvancedFilters();
        }
      });
    }

    // Ajouter les événements pour les cases à cocher des filtres avancés
    const advancedFilterCheckboxes = [
      'electric-only',
      'verified-only',
      'non-smoking',
      'pets-allowed',
    ];

    advancedFilterCheckboxes.forEach((id) => {
      const checkbox = document.getElementById(id);
      if (checkbox) {
        checkbox.addEventListener('change', function () {
          if (resultsList) {
            // Appliquer les filtres avancés aux résultats
            applyAdvancedFilters();
          }
        });
      }
    });

    // Gestion du bouton de réinitialisation des filtres
    const resetFiltersBtn = document.getElementById('reset-filters');
    if (resetFiltersBtn) {
      resetFiltersBtn.addEventListener('click', function () {
        // Réinitialiser tous les checkboxes
        advancedFilterCheckboxes.forEach((id) => {
          const checkbox = document.getElementById(id);
          if (checkbox) {
            checkbox.checked = false;
          }
        });

        // Réinitialiser le curseur de prix
        if (maxPriceInput) {
          maxPriceInput.value = 50;
          document.getElementById('price-label').textContent = 'Prix maximum: 50€';
          maxPriceInput.setAttribute('aria-valuenow', 50);
          maxPriceInput.setAttribute('aria-valuetext', '50€');
        }

        // Appliquer les filtres réinitialisés
        if (resultsList && resultsList.children.length > 0) {
          // Retirer tous les filtres et afficher tous les résultats
          const allRideCards = document.querySelectorAll('.ride-card');
          allRideCards.forEach((card) => {
            card.style.display = 'block';
          });

          // Mettre à jour le compteur de résultats
          resultsCount.textContent = `${allRideCards.length} trajet(s) trouvé(s)`;
        }
      });
    }
  }
}

/**
 * Récupère les suggestions de villes depuis l'API
 * @param {string} query - La requête de recherche
 * @returns {Promise<Array>} - La liste des villes correspondantes
 */
async function fetchCitySuggestions(query) {
  if (!query || query.length < 2) return [];

  // Vérifier dans le cache d'abord
  const cacheKey = query.toLowerCase();
  if (autocompleteCache.has(cacheKey)) {
    return autocompleteCache.get(cacheKey);
  }

  try {
    // Paramètres de recherche pour l'API
    const params = new URLSearchParams({
      q: query,
      type: 'municipality', // Limiter aux communes
      autocomplete: 1,
      limit: 5, // Limiter à 5 résultats
    });

    const response = await fetch(`${API_URL}/search/?${params}`);

    if (!response.ok) {
      throw new Error(`Erreur API: ${response.status}`);
    }

    const data = await response.json();

    // Transformer les résultats dans le format attendu
    const cities = data.features.map((feature) => ({
      name: feature.properties.city,
      postcode: feature.properties.postcode,
      region: feature.properties.context,
      citycode: feature.properties.citycode,
      coordinates: feature.geometry.coordinates,
    }));

    // Stocker dans le cache
    autocompleteCache.set(cacheKey, cities);

    return cities;
  } catch (error) {
    console.error('Erreur lors de la récupération des suggestions:', error);
    return [];
  }
}

/**
 * Configuration de l'autocomplétion pour un champ
 * @param {HTMLInputElement} input - L'élément input à configurer
 * @param {HTMLElement} container - Le conteneur pour les suggestions
 */
function setupAutocomplete(input, container) {
  let currentFocus = -1;
  let currentSuggestions = [];

  // Fonction pour afficher les suggestions
  const showSuggestions = async () => {
    const query = input.value.trim();
    closeAllLists();
    currentFocus = -1;

    if (!query) return;

    try {
      // Récupérer les suggestions depuis l'API
      currentSuggestions = await fetchCitySuggestions(query);

      if (currentSuggestions.length > 0) {
        container.classList.add('active');

        currentSuggestions.forEach((city, index) => {
          const suggestionItem = document.createElement('div');
          suggestionItem.className = 'suggestion-item';

          // Mettre en surbrillance la partie correspondant à la requête
          const cityName = city.name;
          const matchIndex = cityName.toLowerCase().indexOf(query.toLowerCase());

          let cityHTML = cityName;
          if (matchIndex >= 0) {
            const matchEnd = matchIndex + query.length;
            cityHTML =
              cityName.substring(0, matchIndex) +
              '<strong>' +
              cityName.substring(matchIndex, matchEnd) +
              '</strong>' +
              cityName.substring(matchEnd);
          }

          suggestionItem.innerHTML = `
            <div class="main-text">${cityHTML}</div>
            <div class="sub-text">${city.postcode} - ${city.region}</div>
          `;

          // Sélection d'une suggestion au clic
          suggestionItem.addEventListener('click', () => {
            input.value = city.name;
            // Stocker les données complètes dans un attribut data pour validation ultérieure
            input.dataset.cityData = JSON.stringify(city);
            closeAllLists();
          });

          container.appendChild(suggestionItem);
        });
      }
    } catch (error) {
      console.error("Erreur lors de l'affichage des suggestions:", error);
    }
  };

  // Gestion des événements de saisie
  input.addEventListener('input', debounce(showSuggestions, 300));

  // Fermer les suggestions lors de la perte de focus
  input.addEventListener('blur', () => {
    // Délai pour permettre le clic sur une suggestion
    setTimeout(() => {
      closeAllLists();
    }, 200);
  });

  // Navigation au clavier dans les suggestions
  input.addEventListener('keydown', (e) => {
    const items = container.querySelectorAll('.suggestion-item');

    if (items.length === 0) return;

    // Flèche bas
    if (e.key === 'ArrowDown') {
      currentFocus++;
      setActive(items, currentFocus);
      e.preventDefault();
    }
    // Flèche haut
    else if (e.key === 'ArrowUp') {
      currentFocus--;
      setActive(items, currentFocus);
      e.preventDefault();
    }
    // Entrée
    else if (e.key === 'Enter' && currentFocus > -1) {
      if (items[currentFocus]) {
        items[currentFocus].click();
        e.preventDefault();
      }
    }
  });

  // Fonction pour définir l'élément actif dans la liste
  function setActive(items, index) {
    if (!items || !items.length) return;

    // Réinitialiser l'index si hors limites
    if (index >= items.length) currentFocus = 0;
    if (index < 0) currentFocus = items.length - 1;

    // Supprimer la classe active de tous les éléments
    Array.from(items).forEach((item) => {
      item.classList.remove('selected');
    });

    // Ajouter la classe active à l'élément courant
    items[currentFocus].classList.add('selected');
  }
}

/**
 * Ferme toutes les listes d'autocomplétion
 */
function closeAllLists() {
  const containers = document.querySelectorAll('.autocomplete-container');
  containers.forEach((container) => {
    container.innerHTML = '';
    container.classList.remove('active');
  });
}

/**
 * Fonction debounce pour limiter le nombre d'appels à une fonction
 * @param {Function} func - La fonction à exécuter
 * @param {number} wait - Le délai d'attente en millisecondes
 * @returns {Function} La fonction debounced
 */
function debounce(func, wait) {
  let timeout;
  return function (...args) {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
      func.apply(this, args);
    }, wait);
  };
}

/**
 * Gère la soumission du formulaire de recherche
 * @param {Event} e - L'événement de soumission
 */
function handleSearch(e) {
  e.preventDefault();

  // Valider les champs de ville
  const cityFields = ['from', 'to'];
  const citiesValid = LocationService.validateCityFields(searchForm, cityFields);
  
  if (!citiesValid) {
    // Ne pas soumettre le formulaire si les villes ne sont pas valides
    console.error('Veuillez sélectionner des villes valides parmi les suggestions proposées');
    return;
  }
  
  // Récupérer les données du formulaire
  const fromCity = fromInput.value;
  const toCity = toInput.value;
  const date = dateInput.value;
  
  // Construire l'URL avec les paramètres de recherche
  const searchParams = new URLSearchParams();
  searchParams.append('from', fromCity);
  searchParams.append('to', toCity);
  searchParams.append('date', date);

  // Mettre à jour l'URL avec les paramètres de recherche
  const url = new URL(window.location.href);
  url.search = searchParams.toString();
  history.pushState({}, '', url);

  // Simuler un chargement des résultats
  displayLoadingResults();

  // Rechercher d'abord les trajets réels dans la base de données
  fetchRealRides(fromCity, toCity, date)
    .then(realRides => {
      // Si aucun trajet réel n'est trouvé, générer des trajets fictifs
      if (realRides.length === 0 || realRides.error) {
        return getMockResults(fromCity, toCity, date, []);
      }
      
      // Sinon, compléter avec des trajets fictifs
      const mockRides = getMockResults(fromCity, toCity, date, realRides);
      
      // Compter les trajets prioritaires (provenant de notre base de données)
      const priorityRidesCount = realRides.filter(ride => ride.isPriority).length;
      
      // Afficher un message si des trajets prioritaires sont trouvés
      if (priorityRidesCount > 0) {
        const toast = document.createElement('div');
        toast.className = 'toast toast--info toast--visible';
        toast.textContent = `${priorityRidesCount} trajet(s) EcoRide disponible(s) pour tester la réservation!`;
        document.body.appendChild(toast);
        
        // Cacher le message après 5 secondes
        setTimeout(() => {
          toast.classList.remove('toast--visible');
          // Supprimer après la fin de l'animation
          setTimeout(() => toast.remove(), 300);
        }, 5000);
      }
      
      // Combiner les trajets réels (qui sont déjà marqués comme prioritaires) avec les trajets fictifs
      return [...realRides, ...mockRides];
    })
    .then(results => {
      // Trier les résultats pour mettre les trajets prioritaires en premier
      results.sort((a, b) => {
        // Les trajets prioritaires d'abord
        if (a.isPriority && !b.isPriority) return -1;
        if (!a.isPriority && b.isPriority) return 1;
        
        // Ensuite, trier par prix croissant
        return a.price - b.price;
      });
      
      displayResults(results);
    })
    .catch(error => {
      console.error("Erreur lors de la recherche de trajets:", error);
      // En cas d'erreur, afficher seulement des résultats fictifs
      const mockResults = getMockResults(fromCity, toCity, date, []);
      displayResults(mockResults);
    });
}

/**
 * Récupère les covoiturages réels depuis l'API
 * @param {string} from - Ville de départ
 * @param {string} to - Ville d'arrivée
 * @param {string} date - Date du trajet
 * @returns {Array} Tableau des trajets formatés
 */
async function fetchRealRides(from, to, date) {
  try {
    console.log("Recherche de trajets avec les paramètres:", { from, to, date });
    
    // Simuler un délai pour éviter les erreurs de course (race condition)
    await new Promise(resolve => setTimeout(resolve, 200));
    
    try {
      // Essayer de récupérer les trajets réels
      console.log("Appel à RideService.searchRides avec:", from, to, date);
      const response = await RideService.searchRides(from, to, date, 1);
      console.log("Réponse complète de l'API:", response);
      
      // Vérifier si la réponse est valide
      if (!response) {
        console.warn("La réponse de l'API est null ou undefined");
        return [];
      }
      
      // Extraire les résultats selon la structure de la réponse
      let results = [];
      
      if (Array.isArray(response)) {
        console.log("Réponse est un tableau de longueur:", response.length);
        results = response;
      } else if (response.rides && Array.isArray(response.rides)) {
        console.log("Réponse contient un tableau 'rides' de longueur:", response.rides.length);
        results = response.rides;
      } else if (response.data && Array.isArray(response.data.rides)) {
        console.log("Réponse contient data.rides de longueur:", response.data.rides.length);
        results = response.data.rides;
      } else if (response.data && Array.isArray(response.data)) {
        console.log("Réponse contient data[] de longueur:", response.data.length);
        results = response.data;
      } else {
        console.warn("Structure de réponse inconnue:", response);
        // Tenter de traiter comme un objet unique
        if (response.id) {
          console.log("La réponse semble être un objet unique avec un ID");
          results = [response];
        } else {
          return [];
        }
      }
      
      if (results.length > 0) {
        console.log("Trajets trouvés:", results);
        
        // Transformer les résultats dans le format attendu pour l'affichage
        return results.map(ride => {
          console.log("Traitement du covoiturage:", ride);
          
          // Structure de données normalisée pour l'affichage
          const formattedRide = {
            id: ride.id || '',
            from: ride.departure?.location || ride.departure_city || ride.departure || from,
            to: ride.arrival?.location || ride.arrival_city || ride.destination || to,
            date: formatDate(ride.departure?.date || ride.departure_date || ride.date),
            rawDate: ride.departure?.date || ride.departure_date || ride.date,
            time: ride.departure?.time || ride.departure_time || ride.departureTime || "12h00",
            price: parseFloat(ride.price) || Math.floor(Math.random() * 33) + 8,
            driver: {
              name: ride.driver?.name || ride.driver?.username || 'Conducteur EcoRide',
              rating: parseFloat(ride.driver?.rating) || (Math.floor(Math.random() * 21) + 30) / 10,
              trips: ride.driver?.rides_count || Math.floor(Math.random() * 80) + 20,
              image: ride.driver?.profile_image || ride.driver?.profilePicture || '../../assets/images/profile_Marie.svg',
            },
            vehicleType: ride.vehicle?.model || ride.vehicle?.brand || ['Citadine', 'Berline', 'SUV', 'Compacte'][Math.floor(Math.random() * 4)],
            co2Saved: ride.ecologicalImpact?.carbonFootprint || ride.co2_saved || Math.floor(Math.random() * 30) + 10,
            availableSeats: ride.seats?.available || ride.available_seats || ride.availableSeats || Math.floor(Math.random() * 4) + 1,
            duration: ride.duration || `${Math.floor(Math.random() * 3) + 1}h${Math.floor(Math.random() * 60)}`,
            electricVehicle: ride.vehicle?.energyId === 2 || ride.vehicle?.energy === 'Électrique' || ride.vehicle?.is_electric || Math.random() > 0.7,
            nonSmoking: ride.preferences?.non_smoking || Math.random() > 0.4,
            petsAllowed: ride.preferences?.pets_allowed || Math.random() > 0.6,
            driverVerified: ride.driver?.is_verified || Math.random() > 0.5,
            isPriority: true // Marquer comme prioritaire car provient de la base de données
          };
          
          console.log("Covoiturage formaté:", formattedRide);
          return formattedRide;
        });
      }
      
      console.warn("Aucun trajet trouvé dans la base de données ou réponse invalide");
      return []; // Retourner un tableau vide que getMockResults complétera
      
    } catch (apiError) {
      // Erreur lors de l'appel API - consigner l'erreur et continuer avec les données mockées
      console.error("Erreur lors de la récupération des trajets API:", apiError);
      console.warn("Utilisation des trajets fictifs à la place");
      return []; // Retourner un tableau vide que getMockResults complétera
    }
  } catch (error) {
    // Erreur générale - consigner et retourner un tableau vide
    console.error("Erreur générale lors de la recherche de trajets:", error);
    return [];
  }
}

/**
 * Récupère des résultats fictifs pour la démo
 * @param {string} from - Ville de départ
 * @param {string} to - Ville d'arrivée
 * @param {string} date - Date du trajet
 * @param {Array} existingRides - Trajets déjà trouvés dans la base de données
 * @returns {Array} Tableau de résultats
 */
function getMockResults(from, to, date, existingRides = []) {
  // Dans un cas réel, ces données viendraient d'une API

  // Générer un nombre aléatoire de résultats entre 5 et 15
  // Réduire le nombre si des trajets réels ont été trouvés
  const existingCount = existingRides ? existingRides.length : 0;
  const maxCount = Math.max(10 - existingCount, 5);
  const count = Math.floor(Math.random() * (maxCount - 5)) + 5;

  const results = [];
  const dateObj = new Date(date);
  const formattedDate = dateObj.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  });

  // Liste des prénoms pour les conducteurs
  const firstNames = ['Sophie', 'Thomas', 'Marie', 'Lucas', 'Camille'];
  const lastInitials = ['D.', 'M.', 'L.', 'B.', 'R.'];

  // Précharger les images pour éviter les erreurs 404
  const profilePics = [
    '../../assets/images/profile_Marie.svg',
    '../../assets/images/profile_Paul.svg',
    '../../assets/images/profile_Claire.svg',
    '../../assets/images/profile_Sophie.svg',
    '../../assets/images/profile_Thomas.svg',
  ];

  for (let i = 0; i < count; i++) {
    // Générer une heure aléatoire entre 6h et 20h
    const hour = Math.floor(Math.random() * 15) + 6;
    const minute = Math.floor(Math.random() * 60);
    const formattedTime = `${hour.toString().padStart(2, '0')}h${minute.toString().padStart(2, '0')}`;

    // Prix aléatoire entre 8€ et 40€
    const price = Math.floor(Math.random() * 33) + 8;

    // Note aléatoire entre 3 et 5
    const rating = (Math.floor(Math.random() * 21) + 30) / 10;

    // Places disponibles aléatoires entre 1 et 4
    const availableSeats = Math.floor(Math.random() * 4) + 1;

    // Calcul de l'heure d'arrivée
    const durationMinutes = Math.floor(Math.random() * 150) + 30;

    // Calcul du temps de trajet pour l'affichage
    const durationHours = Math.floor(durationMinutes / 60);
    const remainingMinutes = durationMinutes % 60;
    const durationStr =
      durationHours > 0
        ? `${durationHours}h${remainingMinutes > 0 ? remainingMinutes : ''}`
        : `${remainingMinutes}min`;

    // Choisir un index aléatoire pour les noms/images
    const nameIndex = Math.floor(Math.random() * firstNames.length);
    const lastNameIndex = Math.floor(Math.random() * lastInitials.length);
    const imageIndex = Math.floor(Math.random() * profilePics.length);

    // Données aléatoires pour les filtres
    const electricVehicle = Math.random() > 0.7;
    const nonSmoking = Math.random() > 0.4;
    const petsAllowed = Math.random() > 0.6;
    const driverVerified = Math.random() > 0.5;

    results.push({
      id: `trip-${i}`,
      from: from,
      to: to,
      date: formattedDate,
      rawDate: dateObj.toISOString(), // Ajouter la date au format ISO
      time: formattedTime,
      price: price,
      driver: {
        name: firstNames[nameIndex] + ' ' + lastInitials[lastNameIndex],
        rating: rating,
        trips: Math.floor(Math.random() * 80) + 20,
        image: profilePics[imageIndex],
      },
      vehicleType: ['Citadine', 'Berline', 'SUV', 'Compacte'][Math.floor(Math.random() * 4)],
      co2Saved: Math.floor(Math.random() * 30) + 10,
      availableSeats: availableSeats,
      duration: durationStr,
      electricVehicle: electricVehicle,
      nonSmoking: nonSmoking,
      petsAllowed: petsAllowed,
      driverVerified: driverVerified,
      isPriority: false // Les trajets fictifs ne sont pas prioritaires
    });
  }

  return results;
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

/**
 * Construit et retourne le HTML pour les badges d'options d'un trajet
 * @param {Object} ride - Données du trajet
 * @returns {string} - HTML pour les badges d'options
 */
function buildOptionBadges(ride) {
  const badges = [];

  // Vérifier si c'est un véhicule électrique
  if (ride.car && ride.car.electric) {
    badges.push(`
      <div class="badge" title="Véhicule électrique">
        <i class="fa-solid fa-bolt" aria-hidden="true"></i>
        Électrique
      </div>
    `);
  }

  // Vérifier si le conducteur est vérifié
  if (ride.driver && ride.driver.verified) {
    badges.push(`
      <div class="badge" title="Conducteur vérifié">
        <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        Vérifié
      </div>
    `);
  }

  // Vérifier les préférences
  if (ride.preferences) {
    // Non-fumeur
    if (ride.preferences.nonSmoking) {
      badges.push(`
        <div class="badge" title="Trajet non-fumeur">
          <i class="fa-solid fa-smoking-ban" aria-hidden="true"></i>
          Non-fumeur
        </div>
      `);
    }

    // Animaux acceptés
    if (ride.preferences.petsAllowed) {
      badges.push(`
        <div class="badge" title="Animaux acceptés">
          <i class="fa-solid fa-paw" aria-hidden="true"></i>
          Animaux
        </div>
      `);
    }
  }

  // Si aucun badge, retourner une chaîne vide
  if (badges.length === 0) {
    return '';
  }

  // Sinon, retourner les badges dans un conteneur
  return `
    <div class="option-badges">
      ${badges.join('')}
    </div>
  `;
}

/**
 * Affiche les résultats de recherche
 * @param {Array} results - Tableau des résultats à afficher
 * @param {boolean} isInitialLoad - Indique s'il s'agit du chargement initial
 */
function displayResults(results, isInitialLoad = true) {
  if (!resultsList) return;

  if (results.length === 0) {
    resultsList.innerHTML = `
      <div class="empty-results">
        <i class="fa-solid fa-route-slash" aria-hidden="true"></i>
        <p>Aucun covoiturage ne correspond à votre recherche.</p>
        <p>Essayez de modifier vos critères ou de rechercher à une autre date.</p>
        <div class="suggest-alert">
          <i class="fa-solid fa-bell" aria-hidden="true"></i>
          Créez une alerte pour être informé dès qu'un trajet est disponible.
        </div>
      </div>
    `;
    if (resultsCount) {
      resultsCount.textContent = '0 trajet trouvé';
    }
    return;
  }

  // Mise à jour du compteur - montrer toujours le nombre total de trajets disponibles
  if (isInitialLoad && resultsCount) {
    // Stocker tous les résultats dans un attribut data pour un accès ultérieur
    resultsList.dataset.allResults = JSON.stringify(results);

    resultsCount.textContent = `${results.length} trajet${results.length > 1 ? 's' : ''} trouvé${
      results.length > 1 ? 's' : ''
    }`;

    // Vider la liste si c'est le chargement initial
    resultsList.innerHTML = '';

    // N'afficher que les 3 premiers résultats
    const initialResults = results.slice(0, 3);
    // Définir l'index actuel
    resultsList.dataset.currentIndex = '3';

    // Montrer/cacher le bouton "Charger plus de résultats" selon le nombre total
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
      loadMoreBtn.style.display = results.length > 3 ? 'block' : 'none';
    }

    // On utilise les 3 premiers résultats pour l'affichage initial
    results = initialResults;
  }

  // Création des cartes de covoiturage (sans vider la liste si ce n'est pas le chargement initial)
  results.forEach((ride) => {
    // Création de la carte avec le nouveau format
    const card = document.createElement('div');
    card.className = 'ride-card';

    // Ajout des attributs data pour les filtres avancés
    card.dataset.price = ride.price;
    card.dataset.electric = ride.electricVehicle;
    card.dataset.verified = ride.driverVerified;
    card.dataset.nonsmoking = ride.nonSmoking;
    card.dataset.petsallowed = ride.petsAllowed;
    card.dataset.id = ride.id; // Stocker l'ID du trajet pour la redirection

    // Calcul de l'heure d'arrivée à partir de l'heure de départ
    const arrivalTime = calculateArrivalTime(ride.time);

    // Construire les étoiles pour la notation du conducteur
    const starsHtml = buildStarRating(ride.driver.rating || 4.5);

    // Formater la date pour l'affichage
    const formattedDate = formatDate(ride.date);

    // Image par défaut si aucune image n'est fournie
    const driverImage = ride.driver.image || '../../assets/images/default-avatar.jpg';

    // Définir le type de véhicule pour l'affichage
    const vehicleTypeDisplay = ride.electricVehicle
      ? `${ride.vehicleType} - Électrique`
      : ride.vehicleType;

    // Badges pour les options du trajet
    const badges = [];

    if (ride.electricVehicle) {
      badges.push(`
        <div class="badge" title="Véhicule électrique">
          <i class="fa-solid fa-bolt" aria-hidden="true"></i>
          Électrique
        </div>
      `);
    }

    if (ride.driverVerified) {
      badges.push(`
        <div class="badge" title="Conducteur vérifié">
          <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
          Vérifié
        </div>
      `);
    }

    if (ride.nonSmoking) {
      badges.push(`
        <div class="badge" title="Trajet non-fumeur">
          <i class="fa-solid fa-smoking-ban" aria-hidden="true"></i>
          Non-fumeur
        </div>
      `);
    }

    if (ride.petsAllowed) {
      badges.push(`
        <div class="badge" title="Animaux acceptés">
          <i class="fa-solid fa-paw" aria-hidden="true"></i>
          Animaux
        </div>
      `);
    }

    const badgesHtml = badges.length
      ? `
      <div class="option-badges">
        ${badges.join('')}
      </div>
    `
      : '';

    // Création du HTML de la carte
    card.innerHTML = `
      <div class="ride-card__header">
        <div class="driver-info">
          <img src="${driverImage}" alt="${ride.driver.name}" />
          <div class="driver-details">
            <span class="name">${ride.driver.name}</span>
            <div class="rating">
              ${starsHtml}
              <span>(${ride.driver.trips || 0})</span>
            </div>
          </div>
        </div>
        <div class="price">${ride.price}€</div>
      </div>
      
      <div class="ride-card__route">
        <div class="route-info">
          <div class="from">
            <i class="fa-solid fa-circle" aria-hidden="true"></i>
            ${ride.from}
          </div>
          <span class="route-separator">→</span>
          <div class="to">
            <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
            ${ride.to}
          </div>
        </div>
        <div class="time-info">
          <i class="fa-regular fa-clock" aria-hidden="true"></i>
          ${ride.time} (${formattedDate})
        </div>
      </div>
      
      ${badgesHtml}
      
      <div class="ride-card__footer">
        <div class="eco-info">
          <i class="fa-solid fa-leaf" aria-hidden="true"></i>
          ${vehicleTypeDisplay}
        </div>
        <div class="seats-info">
          <i class="fa-solid fa-user" aria-hidden="true"></i>
          ${ride.availableSeats} place(s) disponible(s)
        </div>
        <button class="details-button" data-ride-id="${ride.id}" aria-label="Voir les détails de ce trajet de covoiturage">
          Détails
        </button>
      </div>
    `;

    // Ajout de l'événement sur le bouton de détails
    card.querySelector('.details-button').addEventListener('click', function () {
      // Transformer les données du trajet dans le format attendu par la page de détail
      const rideDetails = {
        id: ride.id,
        departure: ride.from,
        destination: ride.to,
        date: ride.rawDate || ensureISODate(dateInput.value), // Utiliser la date de recherche
        departureTime: ride.time.replace('h', ':'),
        arrivalTime: calculateArrivalTime(ride.time).replace('h', ':'),
        price: ride.price,
        availableSeats: ride.availableSeats,
        co2Emission: ride.co2Emission || (ride.electricVehicle ? 0 : 120),
        driverId: ride.driver.id || `driver-${ride.id}`,
        vehicle: {
          model: ride.vehicleType + (ride.electricVehicle ? ' électrique' : ''),
          year: new Date().getFullYear().toString(),
          fuelType: ride.electricVehicle ? 'Électrique' : 'Thermique',
          tags: [
            ride.electricVehicle ? 'Écologique' : '',
            'Confort ++'
          ].filter(tag => tag)
        },
        preferences: {
          smoking: !ride.nonSmoking,
          pets: ride.petsAllowed,
          music: true,
          talking: true
        },
        driver: {
          firstName: ride.driver.name.split(' ')[0],
          lastName: ride.driver.name.split(' ').slice(1).join(' '),
          avatar: ride.driver.image || '../../assets/images/default-avatar.jpg',
          rating: ride.driver.rating || 4.5,
          ridesCount: ride.driver.trips || 42,
          responseRate: 98,
          responseTime: '< 1 heure',
          verified: ride.driverVerified
        }
      };
      
      // Sauvegarder les données du trajet dans le sessionStorage
      RideService.saveToSession(ride.id, rideDetails);
      
      // Rediriger vers la page de détail
      window.location.href = `detail-covoiturage.html?id=${ride.id}`;
    });

    // Ajout de la carte à la liste des résultats
    resultsList.appendChild(card);
  });
}

/**
 * Configure les événements pour les boutons supplémentaires
 */
function setupResultsButtons() {
  // Bouton de tri
  const sortButton = document.getElementById('sort-button');
  const sortSelect = document.getElementById('sort-filter');

  if (sortButton && sortSelect) {
    sortButton.addEventListener('click', function () {
      // Afficher un menu de tri personnalisé ou utiliser le select existant
      sortSelect.click();
    });
  }

  // Bouton "Charger plus de résultats"
  const loadMoreBtn = document.getElementById('load-more-btn');
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', function () {
      if (!resultsList || !resultsList.dataset.allResults) return;

      // Récupérer tous les résultats stockés
      const allResults = JSON.parse(resultsList.dataset.allResults);

      // Récupérer l'index actuel
      const currentIndex = parseInt(resultsList.dataset.currentIndex || '0');

      // Déterminer combien de résultats afficher ensuite (max 3)
      const nextBatch = allResults.slice(currentIndex, currentIndex + 3);

      // S'il n'y a plus de résultats à afficher, masquer le bouton
      if (currentIndex + 3 >= allResults.length) {
        loadMoreBtn.style.display = 'none';
      }

      // Mettre à jour l'index actuel
      resultsList.dataset.currentIndex = (currentIndex + nextBatch.length).toString();

      // Ajouter les nouveaux résultats sans vider la liste existante
      displayResults(nextBatch, false);
    });
  }

  // Bouton "Créer une alerte"
  const createAlertBtn = document.getElementById('create-alert-btn');
  if (createAlertBtn) {
    createAlertBtn.addEventListener('click', function () {
      alert(
        'Alerte créée pour ce trajet ! Vous recevrez une notification lorsque de nouveaux trajets correspondant à vos critères seront disponibles.'
      );
    });
  }

  // Récupérer tous les nouveaux boutons "Détails" de la liste
  // Cette étape est nécessaire car displayResults ajoute déjà des listeners pour les nouveaux boutons
  // mais on veut s'assurer que tous les boutons sont bien configurés
  document.querySelectorAll('.details-button').forEach((button) => {
    // Vérifier si le bouton a déjà un événement de clic (pour éviter les doublons)
    if (!button.hasAttribute('data-event-attached')) {
      button.setAttribute('data-event-attached', 'true');
      button.addEventListener('click', function () {
        const rideId = this.getAttribute('data-ride-id') || this.closest('.ride-card').dataset.id;
        if (rideId) {
          window.location.href = `./detail-covoiturage.html?id=${rideId}`;
        }
      });
    }
  });
}

/**
 * Applique un filtre sur les résultats de recherche
 * @param {string} filterType - Type de filtre (price-asc, price-desc, time, rating)
 */
function applyFilter(filterType) {
  // Afficher l'état de chargement
  displayLoadingResults();

  setTimeout(() => {
    const results = getMockResults(fromInput.value, toInput.value, dateInput.value);

    // Tri des résultats selon le filtre
    if (filterType === 'price-asc') {
      results.sort((a, b) => a.price - b.price);
    } else if (filterType === 'price-desc') {
      results.sort((a, b) => b.price - a.price);
    } else if (filterType === 'time') {
      results.sort((a, b) => {
        // Extraire les heures et minutes pour comparer
        const [hoursA, minutesA] = a.time.split('h');
        const [hoursB, minutesB] = b.time.split('h');

        // Convertir en minutes pour comparer facilement
        const timeA = parseInt(hoursA) * 60 + parseInt(minutesA || 0);
        const timeB = parseInt(hoursB) * 60 + parseInt(minutesB || 0);

        return timeA - timeB;
      });
    } else if (filterType === 'rating') {
      results.sort((a, b) => b.driver.rating - a.driver.rating);
    }

    // Réinitialiser l'affichage avec les résultats triés
    displayResults(results, true);

    // Réafficher le bouton "Charger plus de résultats" s'il y a plus de 3 résultats
    const loadMoreBtn = document.getElementById('load-more-btn');
    if (loadMoreBtn) {
      loadMoreBtn.style.display = results.length > 3 ? 'block' : 'none';
    }
  }, 500);
}

/**
 * Applique les filtres avancés aux résultats
 */
function applyAdvancedFilters() {
  const rideCards = document.querySelectorAll('.ride-card');
  if (!rideCards.length) return;

  // On récupère l'état des filtres avancés
  const electricOnly = document.getElementById('electric-only')?.checked || false;
  const verifiedOnly = document.getElementById('verified-only')?.checked || false;
  const nonSmoking = document.getElementById('non-smoking')?.checked || false;
  const petsAllowed = document.getElementById('pets-allowed')?.checked || false;
  const maxPrice = document.getElementById('max-price')?.value || 100;

  let visibleCount = 0;

  // Pour chaque covoiturage, on vérifie s'il correspond aux critères
  rideCards.forEach((card) => {
    // Récupération des données du covoiturage depuis les attributs data
    const price = parseFloat(card.dataset.price) || 0;
    const isElectric = card.dataset.electric === 'true';
    const isVerified = card.dataset.verified === 'true';
    const isNonSmoking = card.dataset.nonsmoking === 'true';
    const allowsPets = card.dataset.petsallowed === 'true';

    // Vérification des critères
    let shouldShow = true;

    if (electricOnly && !isElectric) shouldShow = false;
    if (verifiedOnly && !isVerified) shouldShow = false;
    if (nonSmoking && !isNonSmoking) shouldShow = false;
    if (petsAllowed && !allowsPets) shouldShow = false;
    if (price > maxPrice) shouldShow = false;

    // Application de la visibilité
    if (shouldShow) {
      card.style.display = 'flex';
      visibleCount++;
    } else {
      card.style.display = 'none';
    }
  });

  // Mise à jour du compteur de résultats
  if (resultsCount) {
    resultsCount.textContent = `${visibleCount} trajet(s) trouvé(s)`;
  }
}

/**
 * Génère l'aperçu du trajet
 * @param {Object} ride - Les données du trajet
 * @returns {string} - Le HTML de l'aperçu du trajet
 */
function buildRoutePreview(ride) {
  if (!ride.from || !ride.to) return '';

  // Dans un cas réel, on utiliserait les coordonnées pour générer un trajet avec des arrêts
  // Pour la démo, on simplifie

  return `
    <div class="route-point departure">
      <div class="point"></div>
      <div class="info">
        <strong>${ride.from}</strong>
        <span class="time">${ride.time}</span>
      </div>
    </div>
    <div class="route-line">
      <div class="line"></div>
    </div>
    <div class="route-point arrival">
      <div class="point"></div>
      <div class="info">
        <strong>${ride.to}</strong>
        <span class="time">${calculateArrivalTime(ride.time)}</span>
      </div>
    </div>
  `;
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
  let minutes = parseInt(parts[1], 10);

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
 * Formate une date pour l'affichage
 * @param {string} dateStr - La date à formater
 * @returns {string} - La date formatée
 */
function formatDate(dateStr) {
  if (!dateStr) return '';

  // Si la date est déjà formatée, on la retourne telle quelle
  if (typeof dateStr === 'string' && dateStr.includes(' ')) {
    return dateStr;
  }

  try {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
    });
  } catch (e) {
    console.error('Erreur de formatage de date:', e);
    return dateStr || '';
  }
}

/**
 * Convertit n'importe quel format de date en chaîne ISO pour un stockage cohérent
 * @param {string|Date} date - La date à convertir
 * @returns {string} - La date au format ISO
 */
function ensureISODate(date) {
  if (!date) {
    return new Date().toISOString();
  }
  
  // Si c'est déjà un objet Date
  if (date instanceof Date) {
    return date.toISOString();
  }

  // Si c'est une chaîne qui ressemble déjà à un format ISO
  if (typeof date === 'string' && date.includes('T') && date.includes('Z')) {
    try {
      return new Date(date).toISOString();
    } catch (e) {
      console.warn('La date semble être au format ISO mais n\'est pas valide:', date);
      return new Date().toISOString();
    }
  }

  // Si c'est une date simple (YYYY-MM-DD)
  if (typeof date === 'string' && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
    try {
      return new Date(date).toISOString();
    } catch (e) {
      console.warn('La date simple n\'est pas valide:', date);
      return new Date().toISOString();
    }
  }

  // Pour les autres formats (comme les dates localisées en français), on utilise la date actuelle
  console.warn('Impossible d\'analyser la date, utilisation de la date actuelle:', date);
  return new Date().toISOString();
}

/**
 * Affiche un indicateur de chargement des résultats
 */
function displayLoadingResults() {
  if (!resultsList) return;

  resultsList.innerHTML = `
    <div class="loading-results">
      <div class="spinner">
        <i class="fa-solid fa-circle-notch fa-spin"></i>
      </div>
      <p>Recherche des meilleurs trajets pour vous...</p>
    </div>
  `;

  if (resultsCount) {
    resultsCount.textContent = 'Chargement...';
  }
}

/**
 * Simule une recherche avec des valeurs par défaut
 */
function simulateSearch() {
  // Définir des valeurs par défaut pour la recherche
  fromInput.value = 'Paris';
  toInput.value = 'Lyon';

  // Définir la date à aujourd'hui
  dateInput.value = today;

  // Simuler des données de ville pour la validation
  fromInput.dataset.cityData = JSON.stringify({
    name: 'Paris',
    postcode: '75000',
    region: 'Île-de-France',
    coordinates: [2.3522, 48.8566],
  });

  toInput.dataset.cityData = JSON.stringify({
    name: 'Lyon',
    postcode: '69000',
    region: 'Auvergne-Rhône-Alpes',
    coordinates: [4.8357, 45.764],
  });

  // Simuler un chargement des résultats
  displayLoadingResults();

  // Simuler un délai et afficher des résultats
  setTimeout(() => {
    const results = getMockResults('Paris', 'Lyon', today);
    displayResults(results, true);
  }, 1000);
}

/**
 * Récupère les paramètres de l'URL et remplit le formulaire avec ces valeurs
 * @returns {boolean} - Indique si des paramètres d'URL ont été trouvés et appliqués
 */
function setFormFromUrlParams() {
  const params = new URLSearchParams(window.location.search);
  let hasParams = false;

  // Récupérer les paramètres
  const from = params.get('from');
  const to = params.get('to');
  const date = params.get('date');

  // Remplir le formulaire avec les valeurs des paramètres
  if (from) {
    fromInput.value = from;
    hasParams = true;
  }

  if (to) {
    toInput.value = to;
    hasParams = true;
  }

  if (date) {
    // Vérifier que la date est au format YYYY-MM-DD et qu'elle est valide
    if (/^\d{4}-\d{2}-\d{2}$/.test(date) && new Date(date) !== "Invalid Date") {
      dateInput.value = date;
      // Assurons-nous que la date minimale permette cette date
      const dateObj = new Date(date);
      const today = new Date();
      if (dateObj < today) {
        dateInput.value = today.toISOString().split('T')[0];
      } else {
        dateInput.value = date;
      }
      hasParams = true;
    } else {
      // Date invalide, on utilise aujourd'hui
      dateInput.value = today;
      console.warn("Date invalide dans les paramètres d'URL, utilisation de la date du jour");
    }
  } else if (!hasParams) {
    // Si aucune date fournie et pas d'autres paramètres, on utilise aujourd'hui
    dateInput.value = today;
  }

  // Si des paramètres de recherche existent, lancer la recherche automatiquement
  if (from && to) {
    // Utiliser setTimeout pour s'assurer que le DOM est complètement chargé
    setTimeout(() => {
      // Récupérer les suggestions pour les villes et stocker les données
      if (from) {
        fetchCitySuggestions(from).then(suggestions => {
          if (suggestions && suggestions.length > 0) {
            // Trouver la ville par nom (avec ou sans correspondance exacte)
            let cityData = suggestions.find(city => city.name && city.name.toLowerCase() === from.toLowerCase());
            
            // Si pas trouvé, essayer avec le premier résultat
            if (!cityData && suggestions[0]) {
              cityData = suggestions[0];
            }
            
            if (cityData) {
              fromInput.dataset.cityData = JSON.stringify(cityData);
            }
          }
        });
      }

      if (to) {
        fetchCitySuggestions(to).then(suggestions => {
          if (suggestions && suggestions.length > 0) {
            // Trouver la ville par nom (avec ou sans correspondance exacte)
            let cityData = suggestions.find(city => city.name && city.name.toLowerCase() === to.toLowerCase());
            
            // Si pas trouvé, essayer avec le premier résultat
            if (!cityData && suggestions[0]) {
              cityData = suggestions[0];
            }
            
            if (cityData) {
              toInput.dataset.cityData = JSON.stringify(cityData);
            }
          }
        });
      }

      // Lancer la recherche après avoir récupéré les données des villes
      setTimeout(() => {
        // Évite l'avertissement en appelant directement handleSearch avec un événement simulé
        handleSearch({
          preventDefault: () => {},
          target: searchForm
        });
      }, 300);
    }, 100);
  }

  return hasParams;
}

// Initialiser la page au chargement
document.addEventListener('DOMContentLoaded', () => {
  console.log('Initialisation de la page de covoiturages...');
  init();
});
