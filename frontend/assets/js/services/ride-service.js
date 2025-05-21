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
    
    // Récupérer d'abord les trajets locaux pour afficher en priorité
    const localRides = this.getLocalRides();
    const matchingLocalRides = localRides.filter(ride => {
      return (ride.departure_city?.toLowerCase() === departure.toLowerCase() || 
              ride.departure?.toLowerCase() === departure.toLowerCase()) && 
             (ride.arrival_city?.toLowerCase() === destination.toLowerCase() || 
              ride.destination?.toLowerCase() === destination.toLowerCase()) &&
             this.isSameDate(ride.departure_date || ride.date, date);
    });
    
    console.log("RideService.searchRides - Trajets locaux correspondants:", matchingLocalRides);
    
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
        let apiData = response.data || [];
        
        // S'assurer que apiData est un tableau
        if (!Array.isArray(apiData)) {
          apiData = [];
        }
        
        // Combiner les trajets locaux avec ceux de l'API
        // Les trajets locaux sont déjà marqués comme prioritaires
        const combinedData = [...matchingLocalRides, ...apiData];
        
        console.log("RideService.searchRides - Données combinées:", combinedData);
        
        // Mettre en cache et retourner les données combinées
        this.addToCache(cache.searches, cacheKey, combinedData);
        return combinedData;
      }
      
      // Si la requête API échoue, retourner au moins les trajets locaux
      if (matchingLocalRides.length > 0) {
        this.addToCache(cache.searches, cacheKey, matchingLocalRides);
        return matchingLocalRides;
      }
      
      console.warn("RideService.searchRides - Réponse invalide de l'API:", response);
      return [];
    } catch (error) {
      console.error("RideService.searchRides - Erreur:", error);
      
      // En cas d'erreur API, retourner au moins les trajets locaux correspondants
      if (matchingLocalRides.length > 0) {
        console.log("RideService.searchRides - Retour des trajets locaux suite à erreur API");
        return matchingLocalRides;
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
      
      // Modification : Définir isPriority à true pour tous les trajets locaux
      // afin qu'ils apparaissent en premier dans les résultats
      const prioritizedRides = Array.isArray(rides) ? rides.map(ride => ({
        ...ride,
        isPriority: true,
        // Assurer que les places disponibles soient définies correctement
        availableSeats: ride.availableSeats || ride.available_seats || 2,
        // Normaliser les données du conducteur
        driver: {
          ...ride.driver,
          name: ride.driver?.name || ride.driver?.username || 'Conducteur EcoRide',
          rating: parseFloat(ride.driver?.rating) || 4.5
        }
      })) : [];
      
      return prioritizedRides;
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
    // Vérifier le cache
    const cachedData = this.getFromCache(cache.rideDetails, rideId);
    if (cachedData) return cachedData;
    
    // Vérifier s'il y a des données en sessionStorage
    const sessionData = this.getFromSession(rideId);
    if (sessionData) return sessionData;
    
    try {
      // Effectuer la requête API
      const response = await API.get(`/api/rides/${rideId}`);
      
      if (response.error) {
        throw new Error(response.message || 'Erreur lors de la récupération des détails du trajet');
      }
      
      // Mettre en cache et retourner les données
      this.addToCache(cache.rideDetails, rideId, response.data);
      this.saveToSession(rideId, response.data);
      
      return response.data;
    } catch (error) {
      console.error(`Erreur lors de la récupération du trajet ${rideId}:`, error);
      throw error;
    }
  }
  
  /**
   * Récupère les avis sur un conducteur
   * @param {string} driverId - ID du conducteur
   * @param {number} page - Numéro de la page
   * @param {number} limit - Nombre d'avis par page
   * @returns {Promise<Object>} Liste des avis
   */
  static async getDriverReviews(driverId, page = 1, limit = 3) {
    // Construire la clé de cache
    const cacheKey = `${driverId}-${page}-${limit}`;
    
    // Vérifier le cache
    const cachedData = this.getFromCache(cache.reviews, cacheKey);
    if (cachedData) return cachedData;
    
    try {
      // Effectuer la requête API
      const response = await API.get(`/api/drivers/${driverId}/reviews?page=${page}&limit=${limit}`);
      
      if (response.error) {
        throw new Error(response.message || 'Erreur lors de la récupération des avis');
      }
      
      // Mettre en cache et retourner les données
      this.addToCache(cache.reviews, cacheKey, response.data);
      
      return response.data;
    } catch (error) {
      console.error(`Erreur lors de la récupération des avis du conducteur ${driverId}:`, error);
      throw error;
    }
  }
  
  /**
   * Effectue une réservation sur un trajet
   * @param {string} rideId - ID du trajet
   * @param {number} seats - Nombre de places à réserver
   * @returns {Promise<Object>} Détails de la réservation
   */
  static async bookRide(rideId, seats = 1) {
    try {
      // Effectuer la requête API
      const response = await API.post(`/api/rides/${rideId}/book`, { seats });
      
      if (response.error) {
        throw new Error(response.message || 'Erreur lors de la réservation');
      }
      
      // Invalider le cache pour forcer un rechargement des données
      this.invalidateCache(cache.rideDetails, rideId);
      
      return response.data;
    } catch (error) {
      console.error(`Erreur lors de la réservation du trajet ${rideId}:`, error);
      throw error;
    }
  }
  
  /**
   * Annule une réservation
   * @param {string} bookingId - ID de la réservation
   * @returns {Promise<Object>} Résultat de l'annulation
   */
  static async cancelBooking(bookingId) {
    try {
      // Effectuer la requête API
      const response = await API.delete(`/api/bookings/${bookingId}`);
      
      if (response.error) {
        throw new Error(response.message || 'Erreur lors de l\'annulation de la réservation');
      }
      
      return response.data;
    } catch (error) {
      console.error(`Erreur lors de l'annulation de la réservation ${bookingId}:`, error);
      throw error;
    }
  }
  
  /**
   * Crée un nouveau trajet
   * @param {Object} rideData - Données du trajet
   * @returns {Promise<Object>} Résultat de la création
   */
  static async createRide(rideData) {
    try {
      // Effectuer la requête API
      const response = await API.post('/api/rides', rideData);
      
      if (response.error) {
        throw new Error(response.message || 'Erreur lors de la création du trajet');
      }
      
      return response.data;
    } catch (error) {
      console.error('Erreur lors de la création du trajet:', error);
      throw error;
    }
  }
  
  /**
   * Met à jour un trajet existant
   * @param {string} rideId - ID du trajet
   * @param {Object} rideData - Nouvelles données du trajet
   * @returns {Promise<Object>} Résultat de la mise à jour
   */
  static async updateRide(rideId, rideData) {
    try {
      // Effectuer la requête API
      const response = await API.put(`/api/rides/${rideId}`, rideData);
      
      if (response.error) {
        throw new Error(response.message || 'Erreur lors de la mise à jour du trajet');
      }
      
      // Invalider le cache pour forcer un rechargement des données
      this.invalidateCache(cache.rideDetails, rideId);
      
      return response.data;
    } catch (error) {
      console.error(`Erreur lors de la mise à jour du trajet ${rideId}:`, error);
      throw error;
    }
  }
  
  /**
   * Supprime un trajet
   * @param {string} rideId - ID du trajet
   * @returns {Promise<Object>} Résultat de la suppression
   */
  static async deleteRide(rideId) {
    try {
      // Effectuer la requête API
      const response = await API.delete(`/api/rides/${rideId}`);
      
      if (response.error) {
        throw new Error(response.message || 'Erreur lors de la suppression du trajet');
      }
      
      // Invalider les caches
      this.invalidateCache(cache.rideDetails, rideId);
      
      return response.data;
    } catch (error) {
      console.error(`Erreur lors de la suppression du trajet ${rideId}:`, error);
      throw error;
    }
  }
  
  /**
   * Ajoute des données au cache
   * @param {Map} cacheMap - Cache à utiliser
   * @param {string} key - Clé du cache
   * @param {*} data - Données à mettre en cache
   */
  static addToCache(cacheMap, key, data) {
    cacheMap.set(key, {
      data,
      timestamp: Date.now()
    });
  }
  
  /**
   * Récupère des données depuis le cache si elles sont valides
   * @param {Map} cacheMap - Cache à utiliser
   * @param {string} key - Clé du cache
   * @returns {*|null} Données du cache ou null si non trouvées/expirées
   */
  static getFromCache(cacheMap, key) {
    const cached = cacheMap.get(key);
    
    if (!cached) return null;
    
    // Vérifier si les données sont expirées
    const isExpired = Date.now() - cached.timestamp > CACHE_DURATION;
    
    if (isExpired) {
      cacheMap.delete(key);
      return null;
    }
    
    return cached.data;
  }
  
  /**
   * Invalide une entrée spécifique du cache
   * @param {Map} cacheMap - Cache à utiliser
   * @param {string} key - Clé du cache à invalider
   */
  static invalidateCache(cacheMap, key) {
    cacheMap.delete(key);
  }
  
  /**
   * Vide un cache spécifique
   * @param {Map} cacheMap - Cache à vider
   */
  static clearCache(cacheMap) {
    cacheMap.clear();
  }
  
  /**
   * Vide tous les caches
   */
  static clearAllCaches() {
    this.clearCache(cache.rides);
    this.clearCache(cache.rideDetails);
    this.clearCache(cache.reviews);
    this.clearCache(cache.searches);
  }
  
  /**
   * Enregistre les données d'un trajet dans la session
   * @param {string} rideId - ID du trajet
   * @param {Object} rideData - Données du trajet
   */
  static saveToSession(rideId, rideData) {
    try {
      // Créer une clé pour sessionStorage
      const storageKey = `ride_${rideId}`;
      
      // Stocker les données converties en JSON
      sessionStorage.setItem(storageKey, JSON.stringify({
        data: rideData,
        timestamp: Date.now()
      }));
    } catch (error) {
      console.error('Erreur lors de l\'enregistrement dans sessionStorage:', error);
    }
  }
  
  /**
   * Récupère les données d'un trajet depuis la session
   * @param {string} rideId - ID du trajet
   * @returns {Object|null} Données du trajet ou null si non trouvées/expirées
   */
  static getFromSession(rideId) {
    try {
      // Récupérer les données
      const storageKey = `ride_${rideId}`;
      const storedData = sessionStorage.getItem(storageKey);
      
      if (!storedData) return null;
      
      const { data, timestamp } = JSON.parse(storedData);
      
      // Vérifier si les données sont expirées
      const isExpired = Date.now() - timestamp > CACHE_DURATION;
      
      if (isExpired) {
        sessionStorage.removeItem(storageKey);
        return null;
      }
      
      return data;
    } catch (error) {
      console.error('Erreur lors de la récupération depuis sessionStorage:', error);
      return null;
    }
  }
  
  /**
   * Supprime les données d'un trajet de la session
   * @param {string} rideId - ID du trajet
   */
  static removeFromSession(rideId) {
    try {
      const storageKey = `ride_${rideId}`;
      sessionStorage.removeItem(storageKey);
    } catch (error) {
      console.error('Erreur lors de la suppression depuis sessionStorage:', error);
    }
  }
  
  /**
   * Confirme une réservation de trajet après vérification préalable
   * @param {string} rideId - ID du trajet
   * @returns {Promise<Object>} Détails de la confirmation
   */
  static async confirmRide(rideId) {
    try {
      // Effectuer la requête API
      const response = await API.post(`/api/rides/${rideId}/confirm`);
      
      if (response.error) {
        throw new Error(response.message || 'Erreur lors de la confirmation de la réservation');
      }
      
      // Invalider le cache pour forcer un rechargement des données
      this.invalidateCache(cache.rideDetails, rideId);
      
      // Également invalider les caches de recherche puisque les places disponibles ont changé
      this.clearCache(cache.searches);
      
      // Forcer la mise à jour du solde de crédits dans la session
      if (response.data && typeof response.data.new_balance === 'number') {
        // Stocker le nouveau solde dans une variable globale window
        window._userBalance = response.data.new_balance;
        
        // Mettre à jour le sessionStorage
        try {
          const userInfo = JSON.parse(sessionStorage.getItem('user_info') || '{}');
          userInfo.credits = response.data.new_balance;
          sessionStorage.setItem('user_info', JSON.stringify(userInfo));
        } catch (e) {
          console.warn('Impossible de mettre à jour les informations utilisateur en session:', e);
        }
      }
      
      return response.data;
    } catch (error) {
      console.error(`Erreur lors de la confirmation du trajet ${rideId}:`, error);
      throw error;
    }
  }
  
  /**
   * Gère la réservation d'un trajet local (stocké en localStorage)
   * @param {string} rideId - ID du trajet local
   * @param {number} seats - Nombre de places à réserver
   * @returns {Promise<Object>} Résultat de la réservation locale
   */
  static async bookLocalRide(rideId, seats = 1) {
    try {
      console.log(`Traitement d'une réservation locale pour le trajet ${rideId}`);
      
      // Vérifier si l'ID est bien un ID local
      if (!rideId.startsWith('local-')) {
        throw new Error('Ce n\'est pas un trajet local');
      }
      
      // Récupérer les trajets locaux
      const storedRides = localStorage.getItem('user_rides');
      if (!storedRides) {
        throw new Error('Aucun trajet local trouvé');
      }
      
      let rides = JSON.parse(storedRides);
      const rideIndex = rides.findIndex(ride => ride.id === rideId);
      
      if (rideIndex === -1) {
        throw new Error('Trajet local non trouvé');
      }
      
      // Vérifier les places disponibles
      const ride = rides[rideIndex];
      const availableSeats = ride.availableSeats || ride.available_seats || 0;
      
      if (availableSeats < seats) {
        throw new Error(`Places insuffisantes: ${availableSeats} disponibles, ${seats} demandées`);
      }
      
      // Vérifier les crédits de l'utilisateur
      const balanceResponse = await API.get('/api/credits/balance');
      if (balanceResponse.error || typeof balanceResponse.data?.balance !== 'number') {
        throw new Error('Impossible de vérifier votre solde de crédits');
      }
      
      const userBalance = balanceResponse.data.balance;
      const price = ride.price || 0;
      
      if (userBalance < price) {
        throw new Error(`Crédits insuffisants: ${userBalance} disponibles, ${price} nécessaires`);
      }
      
      // Créer une réservation locale et l'enregistrer sur le serveur
      const bookingData = {
        ride_id: rideId,
        seats: seats,
        price: price,
        departure: ride.departure || ride.departure_city,
        destination: ride.destination || ride.arrival_city,
        date_depart: ride.date || ride.departure_date,
        departureTime: ride.departureTime || ride.departure_time
      };
      
      // Soumettre la réservation au serveur
      const response = await API.post('/api/bookings/create', bookingData);
      
      if (response.error) {
        throw new Error(response.message || 'Erreur lors de l\'enregistrement de la réservation');
      }
      
      // Mettre à jour le nombre de places disponibles localement
      rides[rideIndex].availableSeats = availableSeats - seats;
      rides[rideIndex].available_seats = availableSeats - seats; // Assurer la compatibilité
      
      // Sauvegarder les modifications
      localStorage.setItem('user_rides', JSON.stringify(rides));
      
      // Enregistrer la réservation localement aussi
      let localBookings = JSON.parse(localStorage.getItem('user_bookings') || '[]');
      localBookings.push({
        ...bookingData,
        id: `booking-${Date.now()}`,
        status: 'Confirmé',
        reserved_at: new Date().toISOString()
      });
      localStorage.setItem('user_bookings', JSON.stringify(localBookings));
      
      // Enlever le montant du prix des crédits de l'utilisateur
      const debitResponse = await API.post('/api/credits/debit', { amount: price });
      
      if (debitResponse.error) {
        console.warn('La déduction des crédits a échoué:', debitResponse.message);
      }
      
      // Mettre à jour le solde en session
      if (debitResponse.data && typeof debitResponse.data.balance === 'number') {
        window._userBalance = debitResponse.data.balance;
        
        try {
          const userInfo = JSON.parse(sessionStorage.getItem('user_info') || '{}');
          userInfo.credits = debitResponse.data.balance;
          sessionStorage.setItem('user_info', JSON.stringify(userInfo));
        } catch (e) {
          console.warn('Impossible de mettre à jour les informations utilisateur en session:', e);
        }
      }
      
      return {
        success: true,
        message: 'Réservation effectuée avec succès',
        booking: response.data,
        new_balance: debitResponse.data?.balance || userBalance - price
      };
    } catch (error) {
      console.error(`Erreur lors de la réservation du trajet local ${rideId}:`, error);
      throw error;
    }
  }
} 