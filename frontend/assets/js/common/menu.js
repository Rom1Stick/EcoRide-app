/**
 * Gestion du menu mobile pour EcoRide
 * Contient les fonctionnalités pour ouvrir/fermer le menu mobile et les interactions
 */

document.addEventListener('DOMContentLoaded', function () {
  const menuToggle = document.getElementById('menuToggle');
  const mobileNav = document.querySelector('.header__nav');

  // Ouvre/ferme le menu lorsqu'on clique sur le bouton du menu
  menuToggle.addEventListener('click', function () {
    mobileNav.classList.toggle('active');
    document.body.classList.toggle('menu-open');
  });

  // Ferme le menu lorsqu'on clique sur un élément du menu
  const menuLinks = document.querySelectorAll('.header__nav a');
  menuLinks.forEach((link) => {
    link.addEventListener('click', function () {
      mobileNav.classList.remove('active');
      document.body.classList.remove('menu-open');
    });
  });

  // Ferme le menu lorsqu'on clique en dehors
  document.addEventListener('click', function (event) {
    if (
      !event.target.closest('.header__nav') &&
      !event.target.closest('.header__menu') &&
      mobileNav.classList.contains('active')
    ) {
      mobileNav.classList.remove('active');
      document.body.classList.remove('menu-open');
    }
  });
});
