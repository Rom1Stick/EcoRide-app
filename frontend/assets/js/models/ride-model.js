/**
 * Modèles de données pour les covoiturages
 */

/**
 * Classe représentant un trajet de covoiturage
 */
export class Ride {
  /**
   * Crée une instance de Ride
   * @param {Object} data - Données du trajet
   */
  constructor(data) {
    this.id = data.id || '';
    this.departure = data.departure || '';
    this.destination = data.destination || '';
    this.date = data.date || new Date().toISOString();
    this.departureTime = data.departureTime || '00:00';
    this.arrivalTime = data.arrivalTime || '00:00';
    this.price = data.price || 0;
    this.totalSeats = data.totalSeats || 0;
    this.availableSeats = data.availableSeats || 0;
    this.co2Emission = data.co2Emission || 0;
    this.driverId = data.driverId || '';
    this.vehicle = new Vehicle(data.vehicle || {});
    this.preferences = new Preferences(data.preferences || {});
    this.description = data.description || '';
    this.status = data.status || 'active';
    this.createdAt = data.createdAt || new Date().toISOString();
    this.updatedAt = data.updatedAt || new Date().toISOString();
  }

  /**
   * Convertit un objet JSON en instance de Ride
   * @param {Object} data - Données JSON
   * @returns {Ride} Instance de Ride
   */
  static fromJSON(data) {
    return new Ride(data);
  }

  /**
   * Convertit plusieurs objets JSON en instances de Ride
   * @param {Array} data - Tableau de données JSON
   * @returns {Array<Ride>} Tableau d'instances de Ride
   */
  static fromJSONArray(data) {
    return data.map(item => Ride.fromJSON(item));
  }

  /**
   * Convertit l'instance en objet JSON
   * @returns {Object} Représentation JSON de l'objet
   */
  toJSON() {
    return {
      id: this.id,
      departure: this.departure,
      destination: this.destination,
      date: this.date,
      departureTime: this.departureTime,
      arrivalTime: this.arrivalTime,
      price: this.price,
      totalSeats: this.totalSeats,
      availableSeats: this.availableSeats,
      co2Emission: this.co2Emission,
      driverId: this.driverId,
      vehicle: this.vehicle.toJSON(),
      preferences: this.preferences.toJSON(),
      description: this.description,
      status: this.status,
      createdAt: this.createdAt,
      updatedAt: this.updatedAt
    };
  }

  /**
   * Vérifie si le trajet est complet
   * @returns {boolean} True si le trajet est complet
   */
  isFull() {
    return this.availableSeats <= 0;
  }

  /**
   * Calcule la durée du trajet
   * @returns {string} Durée formatée (ex: "3h30")
   */
  getDuration() {
    const [departureHours, departureMinutes] = this.departureTime.split(':').map(Number);
    const [arrivalHours, arrivalMinutes] = this.arrivalTime.split(':').map(Number);
    
    let departureTotalMinutes = departureHours * 60 + departureMinutes;
    let arrivalTotalMinutes = arrivalHours * 60 + arrivalMinutes;
    
    if (arrivalTotalMinutes < departureTotalMinutes) {
      arrivalTotalMinutes += 24 * 60; // Ajout d'une journée
    }
    
    const durationMinutes = arrivalTotalMinutes - departureTotalMinutes;
    const hours = Math.floor(durationMinutes / 60);
    const minutes = durationMinutes % 60;
    
    return minutes === 0 ? `${hours}h` : `${hours}h${minutes}`;
  }

  /**
   * Obtient le niveau d'impact environnemental
   * @returns {string} Niveau d'impact
   */
  getEnvironmentalImpact() {
    if (this.co2Emission <= 50) {
      return 'très faible';
    } else if (this.co2Emission <= 100) {
      return 'faible';
    } else if (this.co2Emission <= 150) {
      return 'modéré';
    } else if (this.co2Emission <= 200) {
      return 'élevé';
    } else {
      return 'très élevé';
    }
  }
}

/**
 * Classe représentant un véhicule
 */
export class Vehicle {
  /**
   * Crée une instance de Vehicle
   * @param {Object} data - Données du véhicule
   */
  constructor(data) {
    this.id = data.id || '';
    this.model = data.model || '';
    this.brand = data.brand || '';
    this.year = data.year || '';
    this.fuelType = data.fuelType || '';
    this.color = data.color || '';
    this.licensePlate = data.licensePlate || '';
    this.comfort = data.comfort || 0;
    this.tags = data.tags || [];
    this.photoUrl = data.photoUrl || '';
  }

  /**
   * Convertit l'instance en objet JSON
   * @returns {Object} Représentation JSON de l'objet
   */
  toJSON() {
    return {
      id: this.id,
      model: this.model,
      brand: this.brand,
      year: this.year,
      fuelType: this.fuelType,
      color: this.color,
      licensePlate: this.licensePlate,
      comfort: this.comfort,
      tags: this.tags,
      photoUrl: this.photoUrl
    };
  }
}

/**
 * Classe représentant les préférences d'un conducteur
 */
export class Preferences {
  /**
   * Crée une instance de Preferences
   * @param {Object} data - Données des préférences
   */
  constructor(data) {
    this.smoking = !!data.smoking;
    this.pets = !!data.pets;
    this.music = !!data.music;
    this.talking = !!data.talking;
  }

  /**
   * Convertit l'instance en objet JSON
   * @returns {Object} Représentation JSON de l'objet
   */
  toJSON() {
    return {
      smoking: this.smoking,
      pets: this.pets,
      music: this.music,
      talking: this.talking
    };
  }
}

/**
 * Classe représentant un avis
 */
export class Review {
  /**
   * Crée une instance de Review
   * @param {Object} data - Données de l'avis
   */
  constructor(data) {
    this.id = data.id || '';
    this.authorId = data.authorId || '';
    this.authorName = data.authorName || '';
    this.authorAvatar = data.authorAvatar || '';
    this.targetId = data.targetId || '';
    this.targetType = data.targetType || 'user';
    this.rating = data.rating || 0;
    this.comment = data.comment || '';
    this.date = data.date || new Date().toISOString();
    this.rideId = data.rideId || '';
  }

  /**
   * Convertit un objet JSON en instance de Review
   * @param {Object} data - Données JSON
   * @returns {Review} Instance de Review
   */
  static fromJSON(data) {
    return new Review(data);
  }

  /**
   * Convertit plusieurs objets JSON en instances de Review
   * @param {Array} data - Tableau de données JSON
   * @returns {Array<Review>} Tableau d'instances de Review
   */
  static fromJSONArray(data) {
    return data.map(item => Review.fromJSON(item));
  }

  /**
   * Convertit l'instance en objet JSON
   * @returns {Object} Représentation JSON de l'objet
   */
  toJSON() {
    return {
      id: this.id,
      authorId: this.authorId,
      authorName: this.authorName,
      authorAvatar: this.authorAvatar,
      targetId: this.targetId,
      targetType: this.targetType,
      rating: this.rating,
      comment: this.comment,
      date: this.date,
      rideId: this.rideId
    };
  }
}

/**
 * Classe représentant un utilisateur
 */
export class User {
  /**
   * Crée une instance de User
   * @param {Object} data - Données de l'utilisateur
   */
  constructor(data) {
    this.id = data.id || '';
    this.firstName = data.firstName || '';
    this.lastName = data.lastName || '';
    this.email = data.email || '';
    this.avatar = data.avatar || '';
    this.bio = data.bio || '';
    this.rating = data.rating || 0;
    this.ridesCount = data.ridesCount || 0;
    this.responseRate = data.responseRate || 0;
    this.responseTime = data.responseTime || '';
    this.isDriver = !!data.isDriver;
    this.isVerified = !!data.isVerified;
    this.createdAt = data.createdAt || new Date().toISOString();
  }

  /**
   * Convertit un objet JSON en instance de User
   * @param {Object} data - Données JSON
   * @returns {User} Instance de User
   */
  static fromJSON(data) {
    return new User(data);
  }

  /**
   * Obtient le nom complet de l'utilisateur
   * @returns {string} Nom complet
   */
  getFullName() {
    return `${this.firstName} ${this.lastName}`;
  }

  /**
   * Obtient le nom affiché de l'utilisateur (avec l'initiale du nom)
   * @returns {string} Nom affiché
   */
  getDisplayName() {
    return `${this.firstName} ${this.lastName.charAt(0)}.`;
  }

  /**
   * Convertit l'instance en objet JSON
   * @returns {Object} Représentation JSON de l'objet
   */
  toJSON() {
    return {
      id: this.id,
      firstName: this.firstName,
      lastName: this.lastName,
      email: this.email,
      avatar: this.avatar,
      bio: this.bio,
      rating: this.rating,
      ridesCount: this.ridesCount,
      responseRate: this.responseRate,
      responseTime: this.responseTime,
      isDriver: this.isDriver,
      isVerified: this.isVerified,
      createdAt: this.createdAt
    };
  }
} 