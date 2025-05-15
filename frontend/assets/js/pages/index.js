/**
 * Gestion du formulaire de recherche sur la page d'accueil
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

  // Validation du formulaire avant soumission
  searchForm.addEventListener('submit', handleSearch);

  console.log("Formulaire de recherche initialisé sur la page d'accueil.");
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
 * Validation et soumission du formulaire de recherche
 * @param {Event} e - L'événement de soumission
 */
function handleSearch(e) {
  // Validation des champs
  if (!validateSearchForm()) {
    e.preventDefault();
    return;
  }

  // Continuer la soumission normale du formulaire
  console.log('Formulaire valide, redirection vers la page de résultats...');
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

  // Vérifier que la ville de départ est différente de la ville d'arrivée
  if (fromInput.value.trim().toLowerCase() === toInput.value.trim().toLowerCase()) {
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

// Initialiser la page au chargement
document.addEventListener('DOMContentLoaded', () => {
  console.log("Initialisation de la page d'accueil...");
  init();
});
