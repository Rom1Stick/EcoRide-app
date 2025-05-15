/**
 * Gestion de la recherche et de l'affichage des covoiturages
 */

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

// Configuration du sélecteur de date avec date minimale = aujourd'hui
const today = new Date().toISOString().split('T')[0];
dateInput.setAttribute('min', today);
if (!dateInput.value) {
  dateInput.value = today;
}

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

  // Initialisation des champs d'autocomplétion
  setupAutocomplete(fromInput, fromAutocompleteContainer);
  setupAutocomplete(toInput, toAutocompleteContainer);

  // Gestion de la soumission du formulaire
  searchForm.addEventListener('submit', handleSearch);

  // Gestion des filtres de résultats
  const filterPrice = document.getElementById('filter-price');
  const filterTime = document.getElementById('filter-time');
  const filterRating = document.getElementById('filter-rating');

  if (filterPrice) filterPrice.addEventListener('click', () => applyFilter('price'));
  if (filterTime) filterTime.addEventListener('click', () => applyFilter('time'));
  if (filterRating) filterRating.addEventListener('click', () => applyFilter('rating'));

  // Récupérer les paramètres d'URL pour pré-remplir le formulaire et lancer la recherche
  setFormFromUrlParams();
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

  // Validation des champs
  if (!validateSearchForm()) {
    return;
  }

  // Construire l'URL avec les paramètres de recherche
  const searchParams = new URLSearchParams();
  searchParams.append('from', fromInput.value);
  searchParams.append('to', toInput.value);
  searchParams.append('date', dateInput.value);

  // Mettre à jour l'URL avec les paramètres de recherche
  const url = new URL(window.location.href);
  url.search = searchParams.toString();
  history.pushState({}, '', url);

  // Simuler un chargement des résultats
  displayLoadingResults();

  // Dans un cas réel, on ferait un appel API ici
  // Pour la démo, on simule un délai et on affiche des résultats fictifs
  setTimeout(() => {
    const results = getMockResults(fromInput.value, toInput.value, dateInput.value);
    displayResults(results);
  }, 1000);
}

/**
 * Validation du formulaire de recherche
 * @returns {boolean} Vrai si le formulaire est valide
 */
function validateSearchForm() {
  let isValid = true;

  // Vérifier si les champs sont remplis
  if (!fromInput.value.trim()) {
    highlightInvalidField(fromInput, 'Ce champ est obligatoire');
    isValid = false;
  } else {
    resetField(fromInput);
  }

  if (!toInput.value.trim()) {
    highlightInvalidField(toInput, 'Ce champ est obligatoire');
    isValid = false;
  } else {
    resetField(toInput);
  }

  // Vérifier si la date est sélectionnée
  if (!dateInput.value) {
    highlightInvalidField(dateInput, 'Veuillez sélectionner une date');
    isValid = false;
  } else {
    resetField(dateInput);
  }

  // Vérifier que les villes existent
  let fromCity, toCity;

  try {
    // Récupérer les données de ville depuis les attributs data si disponibles
    if (fromInput.dataset.cityData) {
      fromCity = JSON.parse(fromInput.dataset.cityData);
    }

    if (toInput.dataset.cityData) {
      toCity = JSON.parse(toInput.dataset.cityData);
    }
  } catch (e) {
    console.error('Erreur lors de la récupération des données de ville:', e);
  }

  if (!fromCity) {
    highlightInvalidField(fromInput, 'Veuillez sélectionner une ville dans la liste');
    isValid = false;
  }

  if (!toCity) {
    highlightInvalidField(toInput, 'Veuillez sélectionner une ville dans la liste');
    isValid = false;
  }

  // Vérifier que la ville de départ est différente de la ville d'arrivée
  if (fromCity && toCity && fromCity.name === toCity.name) {
    highlightInvalidField(toInput, "Les villes de départ et d'arrivée doivent être différentes");
    isValid = false;
  }

  return isValid;
}

/**
 * Met en évidence un champ invalide
 * @param {HTMLElement} field - Le champ à mettre en évidence
 * @param {string} message - Message d'erreur optionnel
 */
function highlightInvalidField(field, message) {
  const inputGroup = field.closest('.input-group');
  inputGroup.style.boxShadow = '0 0 0 2px #ff3d00';

  // Afficher un message d'erreur si fourni
  if (message) {
    let errorElement = inputGroup.parentElement.querySelector('.error-message');

    if (!errorElement) {
      errorElement = document.createElement('div');
      errorElement.className = 'error-message';
      inputGroup.parentElement.appendChild(errorElement);
    }

    errorElement.textContent = message;
  }
}

/**
 * Réinitialise l'apparence d'un champ
 * @param {HTMLElement} field - Le champ à réinitialiser
 */
function resetField(field) {
  const inputGroup = field.closest('.input-group');
  inputGroup.style.boxShadow = '';

  // Supprimer le message d'erreur s'il existe
  const errorElement = inputGroup.parentElement.querySelector('.error-message');
  if (errorElement) {
    errorElement.remove();
  }
}

/**
 * Pré-remplit le formulaire à partir des paramètres d'URL
 */
async function setFormFromUrlParams() {
  const urlParams = new URLSearchParams(window.location.search);

  const from = urlParams.get('from');
  const to = urlParams.get('to');
  const date = urlParams.get('date');

  if (from) {
    fromInput.value = from;
    // Charger les données de la ville pour la validation
    try {
      const fromCities = await fetchCitySuggestions(from);
      const exactMatch = fromCities.find((city) => city.name === from);
      if (exactMatch) {
        fromInput.dataset.cityData = JSON.stringify(exactMatch);
      }
    } catch (error) {
      console.error('Erreur lors du chargement des données de ville de départ:', error);
    }
  }

  if (to) {
    toInput.value = to;
    // Charger les données de la ville pour la validation
    try {
      const toCities = await fetchCitySuggestions(to);
      const exactMatch = toCities.find((city) => city.name === to);
      if (exactMatch) {
        toInput.dataset.cityData = JSON.stringify(exactMatch);
      }
    } catch (error) {
      console.error("Erreur lors du chargement des données de ville d'arrivée:", error);
    }
  }

  if (date) {
    dateInput.value = date;
  }

  // Si tous les paramètres sont présents, lancer la recherche
  if (from && to && date) {
    // Petite temporisation pour permettre le chargement des données de ville
    setTimeout(() => {
      handleSearch(new Event('submit'));
    }, 500);
  }
}

/**
 * Affiche un état de chargement des résultats
 */
function displayLoadingResults() {
  resultsList.innerHTML = `
    <div class="result-card result-card--loading">
      <div class="loading-state">
        <div class="spinner"></div>
        <p>Recherche des trajets en cours...</p>
      </div>
    </div>
  `;
}

/**
 * Récupère des résultats fictifs pour la démo
 * @param {string} from - Ville de départ
 * @param {string} to - Ville d'arrivée
 * @param {string} date - Date du trajet
 * @returns {Array} Tableau de résultats
 */
function getMockResults(from, to, date) {
  // Dans un cas réel, ces données viendraient d'une API

  // Générer un nombre aléatoire de résultats entre 0 et 5
  const count = Math.floor(Math.random() * 6);

  if (count === 0) {
    return [];
  }

  const results = [];
  const dateObj = new Date(date);
  const formattedDate = dateObj.toLocaleDateString('fr-FR', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  });

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

    results.push({
      id: `trip-${i}`,
      from: from,
      to: to,
      date: formattedDate,
      time: formattedTime,
      price: price,
      driver: {
        name:
          ['Sophie', 'Thomas', 'Marie', 'Lucas', 'Camille'][Math.floor(Math.random() * 5)] +
          ' ' +
          ['D.', 'M.', 'L.', 'B.', 'R.'][Math.floor(Math.random() * 5)],
        rating: rating,
        trips: Math.floor(Math.random() * 80) + 20,
        image: `../../assets/images/profile_${['Sophie', 'Thomas', 'Marie', 'Lucas', 'Camille'][Math.floor(Math.random() * 5)]}.svg`,
      },
      vehicleType: ['Citadine', 'Berline', 'SUV', 'Compacte'][Math.floor(Math.random() * 4)],
      co2Saved: Math.floor(Math.random() * 30) + 10,
      availableSeats: availableSeats,
    });
  }

  return results;
}

/**
 * Affiche les résultats de recherche
 * @param {Array} results - Tableau des résultats à afficher
 */
function displayResults(results) {
  // Mise à jour du compteur de résultats
  resultsCount.textContent = `${results.length} trajet(s) trouvé(s)`;

  // Si aucun résultat, afficher l'état vide
  if (results.length === 0) {
    resultsList.innerHTML = `
      <div class="result-card result-card--empty">
        <div class="empty-state">
          <i class="fa-solid fa-car-side"></i>
          <p>Aucun trajet disponible pour cette recherche.</p>
          <p>Essayez de modifier vos critères ou de choisir une autre date.</p>
        </div>
      </div>
    `;
    return;
  }

  // Générer le HTML pour chaque résultat
  const resultsHTML = results
    .map(
      (result) => `
    <div class="result-card" data-id="${result.id}">
      <div class="result-card__time">
        <div class="time">${result.time}</div>
        <div class="date">${result.date}</div>
      </div>
      <div class="result-card__route">
        <div class="from">
          <i class="fa-solid fa-location-dot"></i>
          <span>${result.from}</span>
        </div>
        <div class="journey-line"></div>
        <div class="to">
          <i class="fa-solid fa-location-dot"></i>
          <span>${result.to}</span>
        </div>
      </div>
      <div class="result-card__driver">
        <img src="${result.driver.image}" alt="${result.driver.name}" />
        <div class="info">
          <div class="name">${result.driver.name}</div>
          <div class="rating">
            <i class="fa-solid fa-star"></i>
            <span>${result.driver.rating.toFixed(1)}</span>
            <span class="trips">(${result.driver.trips} trajets)</span>
          </div>
        </div>
      </div>
      <div class="result-card__details">
        <div class="eco">
          <i class="fa-solid fa-leaf"></i>
          <span>-${result.co2Saved}kg CO2</span>
        </div>
        <div class="vehicle">
          <i class="fa-solid fa-car"></i>
          <span>${result.vehicleType}</span>
        </div>
        <div class="seats">
          <i class="fa-solid fa-user-group"></i>
          <span>${result.availableSeats} place${result.availableSeats > 1 ? 's' : ''}</span>
        </div>
      </div>
      <div class="result-card__action">
        <div class="price">${result.price}€</div>
        <button class="book-btn">Réserver</button>
      </div>
    </div>
  `
    )
    .join('');

  resultsList.innerHTML = resultsHTML;

  // Ajouter les écouteurs d'événements pour les cartes
  const cards = resultsList.querySelectorAll('.result-card');
  cards.forEach((card) => {
    const bookBtn = card.querySelector('.book-btn');
    if (bookBtn) {
      bookBtn.addEventListener('click', () => {
        alert('Fonctionnalité de réservation à implémenter dans une prochaine itération.');
      });
    }
  });
}

/**
 * Applique un filtre sur les résultats de recherche
 * @param {string} filterType - Type de filtre (price, time, rating)
 */
function applyFilter(filterType) {
  // Marquer le bouton de filtre comme actif
  const filterButtons = document.querySelectorAll('.filter-btn');
  filterButtons.forEach((btn) => {
    if (btn.id === `filter-${filterType}`) {
      btn.classList.toggle('active');
    } else {
      btn.classList.remove('active');
    }
  });

  // TODO: Implémenter le tri réel des résultats
  // Pour la démo, on simule un rechargement
  const isActive = document.getElementById(`filter-${filterType}`).classList.contains('active');

  if (isActive) {
    displayLoadingResults();
    setTimeout(() => {
      const results = getMockResults(fromInput.value, toInput.value, dateInput.value);

      // Tri des résultats selon le filtre
      if (filterType === 'price') {
        results.sort((a, b) => a.price - b.price);
      } else if (filterType === 'time') {
        results.sort((a, b) => a.time.localeCompare(b.time));
      } else if (filterType === 'rating') {
        results.sort((a, b) => b.driver.rating - a.driver.rating);
      }

      displayResults(results);
    }, 500);
  } else {
    // Si le filtre est désactivé, recharger les résultats sans tri
    handleSearch(new Event('submit'));
  }
}

// Initialiser la page au chargement
document.addEventListener('DOMContentLoaded', () => {
  console.log('Initialisation de la page de covoiturages...');
  init();
});
