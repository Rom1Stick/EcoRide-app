/**
 * Service pour gérer les appels API liés aux covoiturages
 */
import { API } from '../common/api.js';

// Configuration du cache
const CACHE_DURATION = 5 * 60 * 1000; // 5 minutes en millisecondes
const cache = {
  rides: new Map(),
  rideDetails: new Map(),
  reviews: new Map(),
  searches: new Map()
};

/**
 * Classe RideService pour centraliser la logique d'API liée aux covoiturages
 */
export class RideService {
  /**
   * Récupère la liste des trajets avec filtres optionnels
   * @param {Object} filters - Critères de filtrage
   * @returns {Promise<Object>} Données des trajets
   */
  static async getRides(filters = {}) {
    // Construire la clé de cache basée sur les filtres
    const cacheKey = JSON.stringify(filters);
    
    // Vérifier le cache
    const cachedData = this.getFromCache(cache.rides, cacheKey);
    if (cachedData) return cachedData;
    
    // Construire les paramètres de la requête
    const queryParams = new URLSearchParams();
    
    // Ajouter les filtres à la requête
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        queryParams.append(key, value);
      }
    });
    
    // Effectuer la requête API
    const url = `/api/rides${queryParams.toString() ? '?' + queryParams.toString() : ''}`;
    const response = await API.get(url);
    
    // Mettre en cache et retourner les données
    if (!response.error) {
      this.addToCache(cache.rides, cacheKey, response.data);
    }
    
    return response.data;
  }
  
  /**
   * Recherche des trajets selon des critères spécifiques
   * @param {string} departure - Lieu de départ
   * @param {string} destination - Lieu d'arrivée
   * @param {string} date - Date du trajet
   * @param {number} passengers - Nombre de passagers
   * @returns {Promise<Object>} Résultats de la recherche
   */
  static async searchRides(departure, destination, date, passengers = 1) {
    console.log("RideService.searchRides - Paramètres:", { 
      departure, 
      destination, 
      date, 
      passengers 
    });
    
    // Construire la clé de cache
    const cacheKey = `${departure}-${destination}-${date}-${passengers}`;
    
    // Vérifier le cache
    const cachedData = this.getFromCache(cache.searches, cacheKey);
    if (cachedData) {
      console.log("RideService.searchRides - Données trouvées en cache");
      return cachedData;
    }
    
    // IMPORTANT: Adapter les noms des paramètres au format du backend
    // Le backend attend departure_city et arrival_city au lieu de departure et destination
    const queryParams = new URLSearchParams({
      departure_city: departure,
      arrival_city: destination,
      date: date,
      passengers: passengers
    });
    
    console.log("RideService.searchRides - URL de requête:", `/api/rides/search?${queryParams.toString()}`);
    
    try {
      // Effectuer la requête API
      const response = await API.get(`/api/rides/search?${queryParams.toString()}`);
      console.log("RideService.searchRides - Réponse API:", response);
      
      // Vérification des données et mise en cache
      if (response && !response.error) {
        let data = response.data;
        
        // Si les données sont vides ou invalides, simulons des trajets locaux
        // C'est temporaire pour montrer les covoiturages publiés pendant le développement
        if (!data || (Array.isArray(data) && data.length === 0)) {
          // Vérifier si nous avons des trajets en sessionStorage
          const localRides = this.getLocalRides();
          
          // Filtrer les trajets locaux selon les critères de recherche
          const matchingRides = localRides.filter(ride => {
            return (ride.departure_city?.toLowerCase() === departure.toLowerCase() || 
                    ride.departure?.toLowerCase() === departure.toLowerCase()) && 
                   (ride.arrival_city?.toLowerCase() === destination.toLowerCase() || 
                    ride.destination?.toLowerCase() === destination.toLowerCase()) &&
                   this.isSameDate(ride.departure_date || ride.date, date);
          });
          
          if (matchingRides.length > 0) {
            console.log("RideService.searchRides - Trajets locaux trouvés:", matchingRides);
            data = matchingRides;
          } else {
            console.log("RideService.searchRides - Aucun trajet local correspondant");
          }
        }
        
        // Mettre en cache et retourner les données
        this.addToCache(cache.searches, cacheKey, data);
        return data;
      }
      
      console.warn("RideService.searchRides - Réponse invalide de l'API:", response);
      return [];
    } catch (error) {
      console.error("RideService.searchRides - Erreur:", error);
      
      // Essayer de trouver des trajets locaux en cas d'erreur API
      const localRides = this.getLocalRides();
      const matchingRides = localRides.filter(ride => {
        return (ride.departure_city?.toLowerCase() === departure.toLowerCase() || 
                ride.departure?.toLowerCase() === departure.toLowerCase()) && 
               (ride.arrival_city?.toLowerCase() === destination.toLowerCase() || 
                ride.destination?.toLowerCase() === destination.toLowerCase()) &&
               this.isSameDate(ride.departure_date || ride.date, date);
      });
      
      if (matchingRides.length > 0) {
        console.log("RideService.searchRides - Trajets locaux trouvés après erreur API:", matchingRides);
        return matchingRides;
      }
      
      return [];
    }
  }
  
  /**
   * Compare deux dates pour déterminer si elles sont le même jour
   * @param {string} date1 - Première date
   * @param {string} date2 - Seconde date
   * @returns {boolean} Vrai si les dates sont le même jour
   */
  static isSameDate(date1, date2) {
    if (!date1 || !date2) return false;
    
    try {
      // Normaliser les dates
      const d1 = new Date(date1);
      const d2 = new Date(date2);
      
      // Vérifier si les dates sont valides
      if (isNaN(d1.getTime()) || isNaN(d2.getTime())) return false;
      
      // Comparer année/mois/jour
      return d1.getFullYear() === d2.getFullYear() &&
             d1.getMonth() === d2.getMonth() &&
             d1.getDate() === d2.getDate();
    } catch (e) {
      console.error("Erreur lors de la comparaison des dates:", e);
      return false;
    }
  }
  
  /**
   * Récupère les trajets locaux stockés
   * @returns {Array} Tableau des trajets locaux
   */
  static getLocalRides() {
    try {
      // Récupérer tous les trajets du localStorage
      const storedRides = localStorage.getItem('user_rides');
      if (!storedRides) return [];
      
      const rides = JSON.parse(storedRides);
      return Array.isArray(rides) ? rides : [];
    } catch (e) {
      console.error("Erreur lors de la récupération des trajets locaux:", e);
      return [];
    }
  }
  
  /**
   * Récupère les détails d'un trajet spécifique
   * @param {string} rideId - ID du trajet
   * @returns {Promise<Object>} Détails du trajet
   */
  static async getRideDetails(rideId) {
    // Vérifier si on a des données dans le sessionStorage (données passées depuis la liste)
    const sessionData = this.getFromSession(rideId);
    if (sessionData) {
      // Mettre en cache et retourner les données de session
      this.addToCache(cache.rideDetails, rideId, sessionData);
      return sessionData;
    }
    
    // Vérifier le cache
    const cachedData = this.getFromCache(cache.rideDetails, rideId);
    if (cachedData) return cachedData;
    
    // Effectuer la requête API
    const response = await API.get(`/api/rides/${rideId}`);
    
    // Mettre en cache et retourner les données
    if (!response.error) {
      this.addToCache(cache.rideDetails, rideId, response.data);
    }
    
    return response.data;
  }
  
  /**
   * Récupère les avis sur un conducteur
   * @param {string} driverId - ID du conducteur
   * @param {number} page - Numéro de la page
   * @param {number} limit - Nombre d'avis par page
   * @returns {Promise<Object>} Avis sur le conducteur
   */
  static async getDriverReviews(driverId, page = 1, limit = 3) {
    // Construire la clé de cache
    const cacheKey = `${driverId}-${page}-${limit}`;
    
    // Vérifier le cache
    const cachedData = this.getFromCache(cache.reviews, cacheKey);
    if (cachedData) return cachedData;
    
    // Effectuer la requête API
    const response = await API.get(`/api/users/${driverId}/reviews?page=${page}&limit=${limit}`);
    
    // Mettre en cache et retourner les données
    if (!response.error) {
      this.addToCache(cache.reviews, cacheKey, response.data);
    }
    
    return response.data;
  }
  
  /**
   * Réserve une place sur un trajet
   * @param {string} rideId - ID du trajet
   * @param {number} seats - Nombre de places à réserver
   * @returns {Promise<Object>} Confirmation de réservation
   */
  static async bookRide(rideId, seats = 1) {
    const response = await API.post(`/api/rides/${rideId}/book`, { seats });
    
    // Si la réservation est réussie, invalider le cache pour ce trajet
    if (!response.error) {
      this.invalidateCache(cache.rideDetails, rideId);
      // Supprimer aussi du sessionStorage
      this.removeFromSession(rideId);
    }
    
    return response;
  }
  
  /**
   * Annule une réservation
   * @param {string} bookingId - ID de la réservation
   * @returns {Promise<Object>} Confirmation d'annulation
   */
  static async cancelBooking(bookingId) {
    return await API.delete(`/api/bookings/${bookingId}`);
  }
  
  /**
   * Crée un nouveau trajet
   * @param {Object} rideData - Données du trajet
   * @returns {Promise<Object>} Confirmation de création
   */
  static async createRide(rideData) {
    return await API.post('/api/rides', rideData);
  }
  
  /**
   * Met à jour un trajet existant
   * @param {string} rideId - ID du trajet
   * @param {Object} rideData - Nouvelles données du trajet
   * @returns {Promise<Object>} Confirmation de mise à jour
   */
  static async updateRide(rideId, rideData) {
    const response = await API.put(`/api/rides/${rideId}`, rideData);
    
    // Si la mise à jour est réussie, invalider le cache pour ce trajet
    if (!response.error) {
      this.invalidateCache(cache.rideDetails, rideId);
      // Supprimer aussi du sessionStorage
      this.removeFromSession(rideId);
    }
    
    return response;
  }
  
  /**
   * Supprime un trajet
   * @param {string} rideId - ID du trajet
   * @returns {Promise<Object>} Confirmation de suppression
   */
  static async deleteRide(rideId) {
    return await API.delete(`/api/rides/${rideId}`);
  }
  
  /**
   * Ajoute des données au cache avec une date d'expiration
   * @param {Map} cacheMap - Cache à utiliser
   * @param {string} key - Clé de cache
   * @param {*} data - Données à mettre en cache
   */
  static addToCache(cacheMap, key, data) {
    cacheMap.set(key, {
      data,
      expiry: Date.now() + CACHE_DURATION
    });
  }
  
  /**
   * Récupère des données du cache si elles sont valides
   * @param {Map} cacheMap - Cache à utiliser
   * @param {string} key - Clé de cache
   * @returns {*|null} Données en cache ou null si expirées/inexistantes
   */
  static getFromCache(cacheMap, key) {
    const cachedItem = cacheMap.get(key);
    
    if (!cachedItem) return null;
    
    // Vérifier si les données sont expirées
    if (Date.now() > cachedItem.expiry) {
      cacheMap.delete(key);
      return null;
    }
    
    return cachedItem.data;
  }
  
  /**
   * Invalide une entrée spécifique du cache
   * @param {Map} cacheMap - Cache à utiliser
   * @param {string} key - Clé de cache à invalider
   */
  static invalidateCache(cacheMap, key) {
    cacheMap.delete(key);
  }
  
  /**
   * Vide complètement un cache spécifique
   * @param {Map} cacheMap - Cache à vider
   */
  static clearCache(cacheMap) {
    cacheMap.clear();
  }
  
  /**
   * Vide tous les caches
   */
  static clearAllCaches() {
    Object.values(cache).forEach(cacheMap => cacheMap.clear());
  }

  /**
   * Sauvegarde les données d'un trajet dans la sessionStorage
   * @param {string} rideId - ID du trajet
   * @param {Object} rideData - Données du trajet
   */
  static saveToSession(rideId, rideData) {
    try {
      sessionStorage.setItem(`ride_${rideId}`, JSON.stringify(rideData));
      console.log('Données du trajet sauvegardées dans la session:', rideId);
      return true;
    } catch (error) {
      console.error('Erreur lors de la sauvegarde des données du trajet dans la session:', error);
      return false;
    }
  }

  /**
   * Récupère les données d'un trajet depuis la sessionStorage
   * @param {string} rideId - ID du trajet
   * @returns {Object|null} Données du trajet ou null si non trouvées
   */
  static getFromSession(rideId) {
    try {
      const data = sessionStorage.getItem(`ride_${rideId}`);
      if (!data) return null;
      
      return JSON.parse(data);
    } catch (error) {
      console.error('Erreur lors de la récupération des données du trajet depuis la session:', error);
      return null;
    }
  }

  /**
   * Supprime les données d'un trajet du sessionStorage
   * @param {string} rideId - ID du trajet
   */
  static removeFromSession(rideId) {
    try {
      sessionStorage.removeItem(`ride_${rideId}`);
    } catch (error) {
      console.warn('Impossible de supprimer les données du sessionStorage', error);
    }
  }
} 