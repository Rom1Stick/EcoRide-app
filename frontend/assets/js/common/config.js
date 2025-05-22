/**
 * Configuration de l'application EcoRide
 * Ce fichier définit les paramètres de configuration selon l'environnement
 */

// Déterminer l'environnement courant
const isProduction = window.location.hostname !== 'localhost' && 
                    !window.location.hostname.includes('127.0.0.1') &&
                    !window.location.hostname.includes('192.168.');

// Configurer l'URL de base de l'API
export const API_BASE_URL = isProduction 
    ? '' // URL relative pour Heroku (même domaine)
    : 'http://localhost:8080'; // URL locale pour le développement

// Configuration pour les timeouts et retries
export const API_CONFIG = {
    timeout: 15000, // 15 secondes
    maxRetries: 2,
    retryDelay: 1000 // 1 seconde
};

// Version de l'application
export const APP_VERSION = '1.0.0';

// Environnement actuel
export const ENVIRONMENT = isProduction ? 'production' : 'development';

// Activer ou désactiver les logs selon l'environnement
export const ENABLE_LOGS = !isProduction;

// Exporter la configuration complète
export default {
    API_BASE_URL,
    API_CONFIG,
    APP_VERSION,
    ENVIRONMENT,
    ENABLE_LOGS
}; 