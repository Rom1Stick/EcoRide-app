/**
 * Gestion du menu mobile pour EcoRide
 * Contient les fonctionnalités pour ouvrir/fermer le menu mobile et les interactions
 */

document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const mobileNav = document.querySelector('.header__nav');
  const body = document.body;
  
  if (menuToggle && mobileNav) {
    // Initialisation - s'assurer que le menu est fermé au chargement
    mobileNav.classList.remove('active');
    body.classList.remove('menu-open');
    
    // Toggle menu - changement d'icône et accessibilité
    menuToggle.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      
      const isMenuOpen = mobileNav.classList.toggle('active');
      body.classList.toggle('menu-open', isMenuOpen);
      
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
      } else {
        body.style.overflow = '';
      }
    });

    // Ferme le menu lorsqu'on clique sur un élément du menu
    const menuLinks = mobileNav.querySelectorAll('a');
    menuLinks.forEach((link) => {
      link.addEventListener('click', function () {
        mobileNav.classList.remove('active');
        body.classList.remove('menu-open');
        body.style.overflow = '';
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
      });
    });

    // Ferme le menu lorsqu'on clique en dehors
    document.addEventListener('click', function (event) {
      if (
        mobileNav.classList.contains('active') &&
        !event.target.closest('.header__nav') &&
        !event.target.closest('#menuToggle')
      ) {
        mobileNav.classList.remove('active');
        body.classList.remove('menu-open');
        body.style.overflow = '';
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
      }
    });
    
    // Gestion des touches du clavier pour l'accessibilité
    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && mobileNav.classList.contains('active')) {
        mobileNav.classList.remove('active');
        body.classList.remove('menu-open');
        body.style.overflow = '';
        menuToggle.setAttribute('aria-expanded', 'false');
        menuToggle.innerHTML = '<i class="fa-solid fa-bars"></i>';
      }
    });
  }
});
