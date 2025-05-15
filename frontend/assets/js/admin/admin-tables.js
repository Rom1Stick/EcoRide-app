/**
 * Gestion des tableaux responsifs pour la section administration EcoRide
 * Ajoute automatiquement les attributs data-label aux cellules du tableau pour l'affichage mobile
 */

document.addEventListener('DOMContentLoaded', function() {
  // Script pour les attributs data-label des tableaux
  function addDataLabelsToTableRows() {
    const tableHeaders = document.querySelectorAll('.admin-table th');
    const tableRows = document.querySelectorAll('.admin-table tbody tr:not(#user-row-template)');
    
    if (tableHeaders.length && tableRows.length) {
      const headerTexts = Array.from(tableHeaders).map(th => th.textContent.trim());
      
      tableRows.forEach(row => {
        const cells = row.querySelectorAll('td');
        cells.forEach((cell, index) => {
          if (index < headerTexts.length && !cell.hasAttribute('data-label')) {
            cell.setAttribute('data-label', headerTexts[index]);
          }
        });
      });
    }
  }
  
  // Exécuter au chargement
  addDataLabelsToTableRows();
  
  // Observer pour les changements dans le tableau
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
        addDataLabelsToTableRows();
      }
    });
  });
  
  // Observer les tableaux d'administration
  const adminTables = document.querySelectorAll('.admin-table tbody');
  adminTables.forEach(tbody => {
    observer.observe(tbody, { childList: true, subtree: true });
  });
  
  // Masquer l'indicateur de défilement après quelques secondes
  const scrollIndicator = document.querySelector('.table-scroll-indicator');
  if (scrollIndicator) {
    setTimeout(function() {
      scrollIndicator.classList.add('fade-out');
      setTimeout(function() {
        scrollIndicator.style.display = 'none';
      }, 500);
    }, 5000);
  }
}); 