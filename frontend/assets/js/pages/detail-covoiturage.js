/**
 * Script pour la page détail d'un covoiturage
 */
import { getCsrfToken } from '../common/api.js';
import { checkAuthentication } from '../common/auth.js';

/**
 * Récupère l'ID du covoiturage depuis l'URL
 * @returns {string|null} L'ID du covoiturage ou null si non trouvé
 */
function getRideId() {
  const urlParams = new URLSearchParams(window.location.search);
  return urlParams.get('id');
}

/**
 * Gère le bouton de retour
 */
function setupBackButton() {
  const backButton = document.querySelector('.header__logo button');

  backButton.addEventListener('click', () => {
    // Retour à la page précédente si elle existe, sinon à la liste des covoiturages
    if (document.referrer && document.referrer.includes(window.location.origin)) {
      window.history.back();
    } else {
      window.location.href = '/frontend/pages/public/covoiturages.html';
    }
  });
}

/**
 * Gère le bouton de partage
 */
function setupShareButton() {
  const shareButton = document.querySelector('.header__menu');

  shareButton.addEventListener('click', () => {
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
          alert('Lien copié dans le presse-papier !');
        })
        .catch((error) => console.error('Erreur lors de la copie:', error));
    }
  });
}

/**
 * Gère le bouton de réservation
 */
function setupBookingButton() {
  const bookButton = document.querySelector('.btn-book');

  bookButton.addEventListener('click', async () => {
    const isLoggedIn = await checkAuthentication();

    if (!isLoggedIn) {
      // Rediriger vers la page de connexion avec une redirection de retour
      const returnUrl = encodeURIComponent(window.location.href);
      window.location.href = `/frontend/pages/public/login.html?redirect=${returnUrl}`;
      return;
    }

    // TODO: Implémenter la logique de réservation
    console.log('Réservation initiée');
    alert('La fonctionnalité de réservation sera bientôt disponible !');
  });
}

/**
 * Gère le bouton de contact
 */
function setupContactButton() {
  const contactButton = document.querySelector('.contact-btn');

  contactButton.addEventListener('click', async () => {
    const isLoggedIn = await checkAuthentication();

    if (!isLoggedIn) {
      // Rediriger vers la page de connexion avec une redirection de retour
      const returnUrl = encodeURIComponent(window.location.href);
      window.location.href = `/frontend/pages/public/login.html?redirect=${returnUrl}`;
      return;
    }

    // TODO: Implémenter la logique de contact
    console.log('Contact initié');
    alert('La fonctionnalité de messagerie sera bientôt disponible !');
  });
}

/**
 * Gère le bouton "Voir tous les avis"
 */
function setupReviewsButton() {
  const viewAllButton = document.querySelector('.view-all');

  viewAllButton.addEventListener('click', () => {
    // Dans une version future, cela pourrait ouvrir une modale ou une nouvelle page
    alert('Cette fonctionnalité sera disponible prochainement !');
  });
}

/**
 * Initialisation au chargement de la page
 */
document.addEventListener('DOMContentLoaded', () => {
  // Vérifier si on a un ID de trajet
  const rideId = getRideId();
  if (!rideId) {
    console.warn("ID de trajet non trouvé dans l'URL");
    // On ne redirige pas car nous sommes sur une page statique de démonstration
  }

  // Configurer les boutons
  setupBackButton();
  setupShareButton();
  setupBookingButton();
  setupContactButton();
  setupReviewsButton();

  console.log('Page de détail du covoiturage initialisée');

  // Reorganisation mobile des sections (déplacement dans trip-details)
  function reorderSectionsForMobile() {
    const mql = window.matchMedia('(max-width: 1023px)');
    if (!mql.matches) return;
    const tripDetails = document.querySelector('.trip-details');
    const driverSection = document.querySelector('.driver-section');
    const preferencesSection = document.querySelector('.preferences-section');
    const reviewsSection = document.querySelector('.reviews-section');
    const vehicleSection = tripDetails ? tripDetails.querySelector('.vehicle-section') : null;
    // Placer conducteur avant véhicule
    if (driverSection && vehicleSection) {
      tripDetails.insertBefore(driverSection, vehicleSection);
    }
    // Placer préférences avant avis
    if (preferencesSection && reviewsSection) {
      tripDetails.insertBefore(preferencesSection, reviewsSection);
    }
    // Déplacer avis à la fin
    if (reviewsSection) {
      tripDetails.appendChild(reviewsSection);
    }
    // Cacher la barre latérale
    const sidebar = document.querySelector('.trip-sidebar');
    if (sidebar) {
      sidebar.style.display = 'none';
    }
  }
  window.addEventListener('load', reorderSectionsForMobile);
  window.addEventListener('resize', reorderSectionsForMobile);
});
