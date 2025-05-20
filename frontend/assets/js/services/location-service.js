/**
 * Service pour la gestion des lieux (autocomplétion, recherche, etc.)
 */
import { API } from '../common/api.js';

// URL de l'API Adresse du gouvernement français
const API_URL = 'https://api-adresse.data.gouv.fr';

// Cache pour les résultats d'autocomplétion
const autocompleteCache = new Map();

// Cache pour les lieux populaires
const popularLocationsCache = [];

/**
 * Classe LocationService pour gérer les fonctionnalités liées aux lieux
 */
export class LocationService {
  /**
   * Recherche de lieux par terme à partir de l'API nationale
   * @param {string} term - Terme de recherche
   * @returns {Promise<Array>} Liste des lieux correspondants
   */
  static async searchLocations(term) {
    // Minimum 3 caractères requis pour l'API, sinon retourner les lieux populaires
    if (!term || term.length < 3) {
      return this.getPopularLocations();
    }
    
    try {
      // Vérifier d'abord le cache
      const cacheKey = term.toLowerCase();
      if (autocompleteCache.has(cacheKey)) {
        return autocompleteCache.get(cacheKey);
      }
      
      // Paramètres de recherche pour l'API nationale
      const params = new URLSearchParams({
        q: term,
        type: 'municipality', // Limiter aux communes
        autocomplete: 1,
        limit: 5 // Limiter à 5 résultats
      });
      
      const response = await fetch(`${API_URL}/search/?${params}`);
      
      if (!response.ok) {
        // Si l'erreur est 400 (requête incorrecte), retourner silencieusement les lieux par défaut
        // Cela peut se produire quand le terme est trop court ou invalide pour l'API
        return this.getFallbackLocations(term);
      }
      
      const data = await response.json();
      
      // Transformer les résultats dans le format attendu
      const cities = data.features.map((feature) => ({
        id: feature.properties.citycode,
        nom: feature.properties.city,
        codePostal: feature.properties.postcode,
        region: feature.properties.context,
        coordinates: feature.geometry.coordinates
      }));
      
      // Stocker dans le cache
      autocompleteCache.set(cacheKey, cities);
      
      return cities;
    } catch (error) {
      console.error('Erreur lors de la recherche de lieux:', error);
      return this.getFallbackLocations(term);
    }
  }
  
  /**
   * Récupère les lieux populaires (cache ou API)
   * @returns {Promise<Array>} Liste des lieux populaires
   */
  static async getPopularLocations() {
    if (popularLocationsCache.length > 0) {
      return popularLocationsCache;
    }
    
    try {
      const response = await API.get('/api/locations/popular');
      const locations = response.error ? this.getDefaultLocations() : (response.data.locations || []);
      
      // Mettre en cache
      popularLocationsCache.push(...locations);
      
      return locations;
    } catch (error) {
      console.error('Erreur lors de la récupération des lieux populaires:', error);
      return this.getDefaultLocations();
    }
  }
  
  /**
   * Lieux par défaut (si l'API échoue)
   * @returns {Array} Liste de lieux par défaut
   */
  static getDefaultLocations() {
    return [
      { id: 1, nom: 'Paris', codePostal: '75000', region: 'Île-de-France' },
      { id: 2, nom: 'Lyon', codePostal: '69000', region: 'Auvergne-Rhône-Alpes' },
      { id: 3, nom: 'Marseille', codePostal: '13000', region: 'Provence-Alpes-Côte d\'Azur' },
      { id: 4, nom: 'Bordeaux', codePostal: '33000', region: 'Nouvelle-Aquitaine' },
      { id: 5, nom: 'Lille', codePostal: '59000', region: 'Hauts-de-France' },
      { id: 6, nom: 'Strasbourg', codePostal: '67000', region: 'Grand Est' },
      { id: 7, nom: 'Nantes', codePostal: '44000', region: 'Pays de la Loire' },
      { id: 8, nom: 'Toulouse', codePostal: '31000', region: 'Occitanie' }
    ];
  }
  
  /**
   * Lieux de secours en cas d'échec de l'API
   * @param {string} term - Terme de recherche
   * @returns {Array} Liste de lieux correspondant au terme
   */
  static getFallbackLocations(term) {
    const defaultLocations = this.getDefaultLocations();
    const termLower = term.toLowerCase();
    
    return defaultLocations.filter(location => 
      location.nom.toLowerCase().includes(termLower)
    );
  }
  
  /**
   * Vérifie si une ville est valide (a été sélectionnée à partir des suggestions)
   * @param {HTMLElement} input - Champ de saisie à vérifier
   * @returns {boolean} Vrai si la ville est valide
   */
  static isCityValid(input) {
    // Vérifier si le champ a des données de ville associées
    return input && input.dataset && input.dataset.cityData;
  }

  /**
   * Marque un champ comme valide ou invalide
   * @param {HTMLElement} input - Champ à marquer
   * @param {boolean} isValid - Indique si le champ est valide
   */
  static markValidationState(input, isValid) {
    if (!input) return;
    
    // Récupérer le message d'erreur associé
    const errorId = input.getAttribute('aria-describedby');
    const errorEl = errorId ? document.getElementById(errorId) : null;
    
    // Mettre à jour l'état de validation
    input.dataset.validCity = isValid ? 'true' : 'false';
    
    // Mettre à jour les classes CSS
    input.classList.remove('input-valid', 'input-error');
    input.classList.add(isValid ? 'input-valid' : 'input-error');
    
    // Afficher/masquer le message d'erreur
    if (errorEl) {
      // Si l'élément est déjà visible/caché, ne pas changer son état pour éviter de déclencher 
      // plusieurs annonces aria-live si rien n'a changé
      const isCurrentlyHidden = errorEl.style.display === 'none';
      if (isValid && !isCurrentlyHidden) {
        errorEl.style.display = 'none';
      } else if (!isValid && isCurrentlyHidden) {
        errorEl.style.display = 'block';
      }
      
      // Mettre à jour l'attribut aria-invalid sur l'input
      if (isValid) {
        input.removeAttribute('aria-invalid');
      } else {
        input.setAttribute('aria-invalid', 'true');
      }
    }
  }
  
  /**
   * Initialise l'autocomplétion sur un champ
   * @param {HTMLElement} input - Élément input à améliorer
   * @param {Function} onSelect - Fonction appelée à la sélection
   */
  static initAutocomplete(input, onSelect = null) {
    if (!input) return;
    
    // Créer les éléments d'autocomplétion
    const wrapper = input.parentNode;
    let autocompleteContainer = wrapper.querySelector('.autocomplete-container');
    
    // Si le conteneur n'existe pas déjà, le créer
    if (!autocompleteContainer) {
      autocompleteContainer = document.createElement('div');
      autocompleteContainer.className = 'autocomplete-container';
      wrapper.appendChild(autocompleteContainer);
    }
    
    // S'assurer que le parent a une position relative pour que le positionnement absolu fonctionne
    if (getComputedStyle(wrapper).position === 'static') {
      wrapper.style.position = 'relative';
    }
    
    // Assurer que le conteneur a le bon style pour l'affichage des suggestions
    autocompleteContainer.style.position = 'absolute';
    autocompleteContainer.style.top = '100%';
    autocompleteContainer.style.left = '0';
    autocompleteContainer.style.right = '0';
    autocompleteContainer.style.zIndex = '200'; // Plus élevé pour dépasser d'autres éléments
    autocompleteContainer.style.backgroundColor = '#4e342e';
    autocompleteContainer.style.borderRadius = '0 0 0.75rem 0.75rem';
    autocompleteContainer.style.maxHeight = '0';
    autocompleteContainer.style.overflow = 'hidden';
    autocompleteContainer.style.transition = 'all 0.3s ease';
    
    // Variables pour la navigation au clavier
    let currentFocus = -1;
    let currentSuggestions = [];
    
    // Fonction de recherche avec debounce
    let debounceTimeout;
    
    // Au départ, marquer le champ comme invalide s'il n'a pas déjà une valeur valide
    if (input.value && !this.isCityValid(input)) {
      this.markValidationState(input, false);
    }
    
    input.addEventListener('input', function() {
      // Marquer comme invalide quand l'utilisateur commence à taper
      LocationService.markValidationState(input, false);
      
      clearTimeout(debounceTimeout);
      
      debounceTimeout = setTimeout(async () => {
        const query = this.value.trim();
        closeAllLists();
        currentFocus = -1;
        
        if (!query) return;
        
        try {
          // Récupérer les suggestions depuis l'API
          currentSuggestions = await LocationService.searchLocations(query);
          
          if (currentSuggestions.length > 0) {
            // Vider le conteneur avant d'ajouter de nouvelles suggestions
            autocompleteContainer.innerHTML = '';
            
            // Activer le conteneur et définir le bon style
            autocompleteContainer.classList.add('active');
            autocompleteContainer.style.maxHeight = '200px';
            autocompleteContainer.style.overflow = 'auto';
            autocompleteContainer.style.opacity = '1';
            autocompleteContainer.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
            autocompleteContainer.style.border = '1px solid #388e3c';
            autocompleteContainer.style.borderTop = 'none';
            
            currentSuggestions.forEach((city, index) => {
              const suggestionItem = document.createElement('div');
              suggestionItem.className = 'suggestion-item';
              suggestionItem.setAttribute('role', 'option');
              
              // Mettre en surbrillance la partie correspondant à la requête
              const cityName = city.nom;
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
                <div class="sub-text">${city.codePostal} - ${city.region}</div>
              `;
              
              // Sélection d'une suggestion au clic
              suggestionItem.addEventListener('click', () => {
                input.value = city.nom;
                // Stocker les données complètes dans un attribut data pour validation ultérieure
                input.dataset.cityData = JSON.stringify(city);
                
                // Marquer le champ comme valide
                LocationService.markValidationState(input, true);
                
                closeAllLists();
                
                if (typeof onSelect === 'function') {
                  onSelect(city);
                }
              });
              
              autocompleteContainer.appendChild(suggestionItem);
            });
          }
        } catch (error) {
          console.error("Erreur lors de l'affichage des suggestions:", error);
        }
      }, 300); // 300ms de debounce
    });
    
    // Validation au changement de focus
    input.addEventListener('blur', () => {
      // Délai pour permettre le clic sur une suggestion
      setTimeout(() => {
        // Si le champ n'est pas vide mais qu'aucune ville n'a été sélectionnée, le marquer comme invalide
        if (input.value.trim() && !LocationService.isCityValid(input)) {
          LocationService.markValidationState(input, false);
        }
        closeAllLists();
      }, 200);
    });
    
    // Navigation au clavier dans les suggestions
    input.addEventListener('keydown', (e) => {
      const items = autocompleteContainer.querySelectorAll('.suggestion-item');
      
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
      
      // Assurer que l'élément sélectionné est visible dans le conteneur
      const container = items[currentFocus].parentNode;
      const itemTop = items[currentFocus].offsetTop;
      
      if (itemTop < container.scrollTop) {
        container.scrollTop = itemTop;
      } else if (itemTop + items[currentFocus].offsetHeight > container.scrollTop + container.offsetHeight) {
        container.scrollTop = itemTop + items[currentFocus].offsetHeight - container.offsetHeight;
      }
    }
    
    // Fonction interne pour fermer le conteneur d'autocomplétion
    function closeThisList() {
      autocompleteContainer.innerHTML = '';
      autocompleteContainer.classList.remove('active');
      autocompleteContainer.style.maxHeight = '0';
      autocompleteContainer.style.opacity = '0';
      autocompleteContainer.style.boxShadow = 'none';
      autocompleteContainer.style.border = 'none';
    }
    
    // Ajouter cette liste à la fonction globale de fermeture
    window.autocompleteContainers = window.autocompleteContainers || [];
    window.autocompleteContainers.push(closeThisList);
  }
  
  /**
   * Valide un formulaire pour s'assurer que les villes sélectionnées sont valides
   * @param {HTMLFormElement} form - Formulaire à valider
   * @param {Array<string>} fieldIds - IDs des champs à valider
   * @returns {boolean} True si le formulaire est valide
   */
  static validateCityFields(form, fieldIds) {
    if (!form || !fieldIds || !fieldIds.length) return true;
    
    let isValid = true;
    
    fieldIds.forEach(id => {
      const field = form.querySelector(`#${id}`);
      if (field && field.value.trim()) {
        const fieldValid = this.isCityValid(field);
        this.markValidationState(field, fieldValid);
        isValid = isValid && fieldValid;
      }
    });
    
    return isValid;
  }
}

/**
 * Ferme toutes les listes d'autocomplétion ouvertes
 */
function closeAllLists() {
  // Utiliser la liste des fonctions de fermeture pour chaque instance d'autocomplétion
  if (window.autocompleteContainers && Array.isArray(window.autocompleteContainers)) {
    window.autocompleteContainers.forEach(closeFunc => {
      if (typeof closeFunc === 'function') {
        closeFunc();
      }
    });
  }
  
  // Méthode de secours pour les instances qui n'auraient pas été enregistrées
  const containers = document.querySelectorAll('.autocomplete-container');
  containers.forEach((container) => {
    container.innerHTML = '';
    container.classList.remove('active');
    // Appliquer les styles de base pour la fermeture
    container.style.maxHeight = '0';
    container.style.opacity = '0';
    container.style.boxShadow = 'none';
    container.style.border = 'none';
  });
} 