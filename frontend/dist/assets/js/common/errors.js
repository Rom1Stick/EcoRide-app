/**
 * Script pour la gestion des erreurs
 */

// Fonction pour enregistrer les erreurs
function logError(errorType, errorDetails = {}) {
  console.error(`Erreur ${errorType}:`, errorDetails);

  // Dans un environnement de production, on pourrait envoyer l'erreur à un service d'analyse
  if (window.location.hostname !== 'localhost') {
    try {
      // Exemple de code pour envoyer les erreurs à un service d'analyse (désactivé)
      // sendErrorToAnalytics(errorType, errorDetails);
    } catch (e) {
      console.error("Impossible d'envoyer l'erreur au service d'analyse:", e);
    }
  }
}

// Vérification des paramètres d'URL pour personnaliser le message d'erreur
function customizeErrorMessage() {
  const urlParams = new URLSearchParams(window.location.search);
  const errorCode = urlParams.get('code') || '404';
  const errorMessage = urlParams.get('message');

  // Mise à jour du code d'erreur dans la page
  const errorCodeElement = document.querySelector('.error__code');
  if (errorCodeElement) {
    errorCodeElement.textContent = errorCode;
  }

  // Mise à jour du message d'erreur si fourni
  if (errorMessage) {
    const errorTextElement = document.querySelector('.error__text');
    if (errorTextElement) {
      errorTextElement.textContent = decodeURIComponent(errorMessage);
    }
  }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
  customizeErrorMessage();

  // Journalisation de l'erreur
  const urlParams = new URLSearchParams(window.location.search);
  const errorCode = urlParams.get('code') || '404';

  logError('navigation', {
    code: errorCode,
    url: document.referrer || 'Direct',
    timestamp: new Date().toISOString(),
  });
});
