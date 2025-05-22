/**
 * Script pour la page de contact
 * Gère le formulaire de contact et les interactions FAQ
 */

document.addEventListener('DOMContentLoaded', () => {
  initFAQAccordion();
  initContactForm();
  initMapLazyLoading();
});

/**
 * Initialise l'accordéon pour les questions fréquentes
 * avec prise en charge de l'accessibilité
 */
function initFAQAccordion() {
  const faqItems = document.querySelectorAll('.faq-item');

  faqItems.forEach((item) => {
    const question = item.querySelector('.faq-question');
    const answer = item.querySelector('.faq-answer');

    // Définir la hauteur initiale à 0
    answer.style.maxHeight = '0px';

    // Gestionnaire pour le clic
    question.addEventListener('click', () => {
      toggleFaqItem(item, faqItems);
    });

    // Gestionnaire pour le clavier (touche Entrée)
    question.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggleFaqItem(item, faqItems);
      }
    });
  });
}

/**
 * Bascule l'état d'un élément FAQ
 * @param {HTMLElement} item - L'élément FAQ à basculer
 * @param {NodeList} allItems - Tous les éléments FAQ
 */
function toggleFaqItem(item, allItems) {
  const question = item.querySelector('.faq-question');
  const answer = item.querySelector('.faq-answer');
  const isActive = item.classList.contains('active');

  // Fermer tous les autres éléments
  allItems.forEach((otherItem) => {
    if (otherItem !== item) {
      const otherQuestion = otherItem.querySelector('.faq-question');
      const otherAnswer = otherItem.querySelector('.faq-answer');
      otherAnswer.style.maxHeight = '0px';
      otherItem.classList.remove('active');
      otherQuestion.setAttribute('aria-expanded', 'false');
      otherAnswer.setAttribute('aria-hidden', 'true');
    }
  });

  // Basculer l'élément actuel
  if (isActive) {
    answer.style.maxHeight = '0px';
    item.classList.remove('active');
    question.setAttribute('aria-expanded', 'false');
    answer.setAttribute('aria-hidden', 'true');
  } else {
    answer.style.maxHeight = answer.scrollHeight + 'px';
    item.classList.add('active');
    question.setAttribute('aria-expanded', 'true');
    answer.setAttribute('aria-hidden', 'false');
  }
}

/**
 * Initialise le formulaire de contact avec validation
 */
function initContactForm() {
  const contactForm = document.getElementById('contactForm');

  if (!contactForm) return;

  // Focus sur le premier champ du formulaire
  setTimeout(() => {
    const firstInput = contactForm.querySelector('input:not([type="hidden"])');
    if (firstInput) firstInput.focus();
  }, 500);

  contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    // Récupération des valeurs du formulaire
    const formData = {
      name: document.getElementById('name').value.trim(),
      email: document.getElementById('email').value.trim(),
      subject: document.getElementById('subject').value,
      message: document.getElementById('message').value.trim(),
      privacy: document.getElementById('privacy').checked,
    };

    // Validation basique
    if (!validateForm(formData)) return;

    // Désactiver le bouton pendant l'envoi et ajouter un indicateur de chargement
    const submitButton = contactForm.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML =
      '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Envoi en cours...';
    submitButton.setAttribute('aria-busy', 'true');

    try {
      // Simuler l'envoi du formulaire
      await submitContactForm(formData);

      // Afficher un message de succès
      showFormMessage(
        'Votre message a été envoyé avec succès. Nous vous répondrons dans les meilleurs délais.',
        'success'
      );

      // Réinitialiser le formulaire
      contactForm.reset();

      // Annoncer aux technologies d'assistance que le message a été envoyé
      announceToScreenReader('Votre message a été envoyé avec succès');
    } catch (error) {
      showFormMessage(
        "Une erreur est survenue lors de l'envoi du message. Veuillez réessayer plus tard.",
        'error'
      );
      console.error("Erreur lors de l'envoi du formulaire:", error);
    } finally {
      // Réactiver le bouton et restaurer son texte
      submitButton.disabled = false;
      submitButton.innerHTML = originalButtonText;
      submitButton.removeAttribute('aria-busy');
    }
  });
}

/**
 * Valide les données du formulaire
 * @param {Object} formData - Les données du formulaire
 * @returns {boolean} - True si les données sont valides, false sinon
 */
function validateForm(formData) {
  // Vérification que tous les champs requis sont remplis
  if (!formData.name || !formData.email || !formData.subject || !formData.message) {
    showFormMessage('Veuillez remplir tous les champs du formulaire.', 'error');

    // Focus sur le premier champ vide
    if (!formData.name) {
      document.getElementById('name').focus();
    } else if (!formData.email) {
      document.getElementById('email').focus();
    } else if (!formData.subject) {
      document.getElementById('subject').focus();
    } else if (!formData.message) {
      document.getElementById('message').focus();
    }

    return false;
  }

  // Validation de l'email avec une expression régulière simple
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(formData.email)) {
    showFormMessage('Veuillez entrer une adresse email valide.', 'error');
    document.getElementById('email').focus();
    return false;
  }

  // Vérification que la case de confidentialité est cochée
  if (!formData.privacy) {
    showFormMessage('Veuillez accepter la politique de confidentialité.', 'error');
    document.getElementById('privacy').focus();
    return false;
  }

  return true;
}

/**
 * Simule l'envoi du formulaire (sera remplacé par un appel API réel)
 * @param {Object} formData - Les données du formulaire
 * @returns {Promise} - Promise résolue quand le formulaire est envoyé
 */
async function submitContactForm(formData) {
  // Simulation d'un délai d'envoi
  return new Promise((resolve) => {
    setTimeout(() => {
      resolve({ success: true });
    }, 1500);
  });
}

/**
 * Affiche un message après soumission du formulaire
 * @param {string} message - Le message à afficher
 * @param {string} type - Le type de message ('success' ou 'error')
 */
function showFormMessage(message, type = 'success') {
  // Vérifier si un message existe déjà et le supprimer
  const existingMessage = document.querySelector('.form-message');
  if (existingMessage) {
    existingMessage.remove();
  }

  // Créer un nouvel élément de message
  const messageElement = document.createElement('div');
  messageElement.className = `form-message ${type}`;
  messageElement.setAttribute('role', 'alert');
  messageElement.setAttribute('aria-live', 'assertive');
  messageElement.textContent = message;

  // Ajouter une icône appropriée
  const icon = document.createElement('i');
  icon.className =
    type === 'success' ? 'fa-solid fa-circle-check' : 'fa-solid fa-circle-exclamation';
  icon.setAttribute('aria-hidden', 'true');
  messageElement.prepend(icon);

  // Insérer le message après le formulaire
  const form = document.getElementById('contactForm');
  form.insertAdjacentElement('afterend', messageElement);

  // Faire défiler vers le message
  messageElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

  // Supprimer le message après un délai
  if (type === 'success') {
    setTimeout(() => {
      messageElement.classList.add('fade-out');
      setTimeout(() => messageElement.remove(), 500);
    }, 5000);
  }
}

/**
 * Annonce un message aux lecteurs d'écran
 * @param {string} message - Le message à annoncer
 */
function announceToScreenReader(message) {
  const ariaLive = document.createElement('div');
  ariaLive.className = 'sr-only';
  ariaLive.setAttribute('aria-live', 'assertive');
  ariaLive.setAttribute('aria-atomic', 'true');
  document.body.appendChild(ariaLive);

  setTimeout(() => {
    ariaLive.textContent = message;

    setTimeout(() => {
      document.body.removeChild(ariaLive);
    }, 1000);
  }, 100);
}

/**
 * Initialise le chargement paresseux pour la carte Google Maps
 */
function initMapLazyLoading() {
  const mapContainer = document.querySelector('.map-container');
  if (!mapContainer) return;

  const iframe = mapContainer.querySelector('iframe');

  // Observer l'intersection avec le viewport
  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const src = iframe.getAttribute('src');
          if (src) {
            // La carte ne sera chargée que lorsqu'elle sera visible
            iframe.setAttribute('data-src', src);
            iframe.setAttribute('src', src);
            observer.unobserve(entry.target);
          }
        }
      });
    },
    { threshold: 0.1 }
  );

  if (iframe) {
    // Déplacer l'URL dans un attribut data-src pour éviter le chargement immédiat
    const src = iframe.getAttribute('src');
    iframe.setAttribute('data-src', src);
    iframe.setAttribute('src', 'about:blank');

    // Observer la carte
    observer.observe(mapContainer);
  }
}
