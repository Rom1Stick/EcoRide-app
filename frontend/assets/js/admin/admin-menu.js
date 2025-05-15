/**
 * Gestion du menu mobile pour la section administration EcoRide
 * Contient les fonctionnalités pour ouvrir/fermer le menu mobile admin et les interactions
 */

document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('mobile-menu-toggle');
  const adminNav = document.getElementById('admin-nav');
  const body = document.body;
  
  // Gestion du bouton de déconnexion
  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', async function() {
      try {
        // Appel à l'API de déconnexion
        await fetch('/api/auth/logout', {
          method: 'POST',
          credentials: 'include',
        });
        
        // Suppression du token d'authentification
        localStorage.removeItem('auth_token');
        
        // Redirection vers la page d'accueil
        window.location.href = '/pages/public/index.html';
      } catch (error) {
        console.error('Erreur lors de la déconnexion:', error);
        alert('Une erreur est survenue lors de la déconnexion. Veuillez réessayer.');
      }
    });
  }
  
  if (menuToggle && adminNav) {
    // Initialisation - s'assurer que le menu est fermé au chargement
    adminNav.classList.remove('open');
    body.classList.remove('menu-is-open');
    
    // Toggle menu - changement d'icône et accessibilité
    menuToggle.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      
      const isMenuOpen = adminNav.classList.toggle('open');
      body.classList.toggle('menu-is-open', isMenuOpen);
      
      // Mettre à jour les attributs ARIA pour l'accessibilité
      menuToggle.setAttribute('aria-expanded', isMenuOpen);
      
      // Changer l'icône du bouton selon l'état du menu
      if (isMenuOpen) {
        menuToggle.innerHTML = '<i class="fa-solid fa-xmark"></i>';
      } else {
        menuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
      }
      
      // Empêcher le défilement du fond quand le menu est ouvert
      if (isMenuOpen) {
        body.style.overflow = 'hidden';
        // Scroll vers le haut pour voir tout le menu
        window.scrollTo(0, 0);
      } else {
        body.style.overflow = '';
      }
    });

    // Ferme le menu lorsqu'on clique sur un élément du menu
    const menuLinks = adminNav.querySelectorAll('.header__link');
    menuLinks.forEach((link) => {
      link.addEventListener('click', function () {
        adminNav.classList.remove('open');
        body.classList.remove('menu-is-open');
        body.style.overflow = '';
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
      });
    });

    // Ferme le menu lorsqu'on clique en dehors
    document.addEventListener('click', function (event) {
      if (
        adminNav.classList.contains('open') &&
        !event.target.closest('#admin-nav') &&
        !event.target.closest('#mobile-menu-toggle')
      ) {
        adminNav.classList.remove('open');
        body.classList.remove('menu-is-open');
        body.style.overflow = '';
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
      }
    });
    
    // Gestion des touches du clavier pour l'accessibilité
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && adminNav.classList.contains('open')) {
        adminNav.classList.remove('open');
        body.classList.remove('menu-is-open');
        body.style.overflow = '';
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
      }
    });
    
    // Fonction pour ajuster la position du menu en fonction de la hauteur du header
    function fixMobileMenuPosition() {
      if (window.innerWidth < 768) { // Taille tablette
        const headerHeight = document.querySelector('.header--admin').offsetHeight;
        adminNav.style.top = headerHeight + 'px';
      } else {
        adminNav.style.top = 'auto';
      }
    }

    // Exécuter au chargement
    fixMobileMenuPosition();

    // Exécuter au redimensionnement
    window.addEventListener('resize', fixMobileMenuPosition);
  }
}); 