import { initAuthUI } from '../common/auth.js';
import { checkAdminAccess } from './admin-auth.js';

// Variables globales pour les graphiques
let ridesChart = null;
let creditsChart = null;

// Données factices en cas d'échec de l'API
const fallbackData = {
  rides: {
    dailyRides: [
      { date: '2023-06-01', count: 12 },
      { date: '2023-06-02', count: 15 },
      { date: '2023-06-03', count: 8 },
      { date: '2023-06-04', count: 20 },
      { date: '2023-06-05', count: 18 },
      { date: '2023-06-06', count: 22 },
      { date: '2023-06-07', count: 16 }
    ],
    total: 111
  },
  credits: {
    dailyCredits: [
      { date: '2023-06-01', amount: 120 },
      { date: '2023-06-02', amount: 150 },
      { date: '2023-06-03', amount: 80 },
      { date: '2023-06-04', amount: 200 },
      { date: '2023-06-05', amount: 180 },
      { date: '2023-06-06', amount: 220 },
      { date: '2023-06-07', amount: 160 }
    ],
    total: 1110
  }
};

(async () => {
  // Vérifier que l'utilisateur est authentifié
  const authOK = await initAuthUI();
  if (!authOK) return;

  // Vérifier que l'utilisateur a les droits d'administration
  const isAdmin = await checkAdminAccess();
  if (!isAdmin) return;

  // Préparer les en-têtes
  const token = localStorage.getItem('auth_token');
  const headers = {
    'Content-Type': 'application/json',
    Authorization: `Bearer ${token}`,
  };

  // Chargement parallèle des données
  try {
    // Récupération des utilisateurs en attente
    let pendingUsersData = [];
    try {
      const usersResp = await fetch('/api/admin/users/pending', {
        method: 'GET',
        credentials: 'include',
        headers,
      });
      
      if (!usersResp.ok) {
        throw new Error(`Erreur ${usersResp.status}`);
      }
      
      // Vérifier si la réponse est du JSON valide
      const contentType = usersResp.headers.get("content-type");
      if (contentType && contentType.indexOf("application/json") !== -1) {
        const usersResult = await usersResp.json();
        if (usersResult.error) throw new Error(usersResult.message);
        pendingUsersData = usersResult.data || [];
      } else {
        console.error("La réponse des utilisateurs n'est pas au format JSON");
      }
    } catch (err) {
      console.error("Erreur lors de la récupération des utilisateurs:", err);
    }
    
    // Récupération des statistiques des trajets
    let ridesData = fallbackData.rides;
    try {
      const statsResp = await fetch('/api/admin/stats/rides', {
        method: 'GET',
        credentials: 'include',
        headers,
      });
      
      if (!statsResp.ok) {
        throw new Error(`Erreur ${statsResp.status}`);
      }
      
      // Vérifier si la réponse est du JSON valide
      const contentType = statsResp.headers.get("content-type");
      if (contentType && contentType.indexOf("application/json") !== -1) {
        const statsResult = await statsResp.json();
        if (statsResult.error) throw new Error(statsResult.message);
        if (statsResult.data) ridesData = statsResult.data;
      } else {
        console.error("La réponse des statistiques n'est pas au format JSON");
      }
    } catch (err) {
      console.error("Utilisation des données factices pour les trajets:", err);
    }
    
    // Récupération des statistiques des crédits
    let creditsData = fallbackData.credits;
    try {
      const creditsResp = await fetch('/api/admin/stats/credits', {
        method: 'GET',
        credentials: 'include',
        headers,
      });
      
      if (!creditsResp.ok) {
        throw new Error(`Erreur ${creditsResp.status}`);
      }
      
      // Vérifier si la réponse est du JSON valide
      const contentType = creditsResp.headers.get("content-type");
      if (contentType && contentType.indexOf("application/json") !== -1) {
        const creditsResult = await creditsResp.json();
        if (creditsResult.error) throw new Error(creditsResult.message);
        if (creditsResult.data) creditsData = creditsResult.data;
      } else {
        console.error("La réponse des crédits n'est pas au format JSON");
      }
    } catch (err) {
      console.error("Utilisation des données factices pour les crédits:", err);
    }

    // Afficher les données
    renderPendingUsers(pendingUsersData, headers);
    renderRidesChart(ridesData);
    renderCreditsChart(creditsData);
    updateTotalCredits(creditsData.total || 0);

  } catch (err) {
    console.error('Erreur globale lors du chargement des données:', err);
    alert('Une erreur est survenue lors du chargement des données.');
  }
})();

function renderPendingUsers(users, headers) {
  const tbody = document.getElementById('pending-users-tbody');
  if (!tbody) return;
  
  tbody.innerHTML = '';

  if (!users || users.length === 0) {
    const noUsersMessage = document.getElementById('no-users-message');
    if (noUsersMessage) {
      noUsersMessage.style.display = 'block';
    }
    return;
  }

  users.forEach((user) => {
    const tr = document.createElement('tr');

    // Nom
    const tdName = document.createElement('td');
    tdName.textContent = user.name;
    // Email
    const tdEmail = document.createElement('td');
    tdEmail.textContent = user.email;
    // Date d'inscription
    const tdDate = document.createElement('td');
    tdDate.textContent = new Date(user.registered_at).toLocaleString();
    // État de confirmation
    const tdStatus = document.createElement('td');
    tdStatus.textContent = user.confirmed ? 'Confirmé' : 'Non confirmé';

    // Actions
    const tdAction = document.createElement('td');
    if (!user.confirmed) {
      const btn = document.createElement('button');
      btn.textContent = 'Valider';
      btn.className = 'button button--primary';
      btn.addEventListener('click', async () => {
        if (!confirm('Valider ce compte utilisateur ?')) return;
        try {
          const r = await fetch(`/api/admin/users/${user.id}/confirm`, {
            method: 'POST',
            credentials: 'include',
            headers,
          });
          if (!r.ok) {
            const text = await r.text();
            throw new Error(text);
          }
          
          // Vérifier si la réponse est du JSON valide
          const contentType = r.headers.get("content-type");
          if (contentType && contentType.indexOf("application/json") !== -1) {
            const res2 = await r.json();
            if (res2.error) throw new Error(res2.message);
          }
          
          tr.remove();
        } catch (e) {
          alert(e.message || 'Erreur lors de la validation');
        }
      });
      tdAction.appendChild(btn);
    } else {
      tdAction.textContent = '-';
    }

    tr.appendChild(tdName);
    tr.appendChild(tdEmail);
    tr.appendChild(tdDate);
    tr.appendChild(tdStatus);
    tr.appendChild(tdAction);
    tbody.appendChild(tr);
  });
}

/**
 * Affiche le graphique des covoiturages par jour
 * @param {Array} data - Données des covoiturages
 */
function renderRidesChart(data) {
  const ctx = document.getElementById('ridesChart');
  if (!ctx) return;

  // S'assurer que les données nécessaires sont disponibles
  if (!data || !data.dailyRides || !Array.isArray(data.dailyRides)) {
    console.error('Format de données incorrect pour le graphique des covoiturages');
    data = fallbackData.rides;
  }

  // Transformer les données pour le graphique
  const labels = data.dailyRides.map(entry => formatDate(entry.date));
  const values = data.dailyRides.map(entry => entry.count);

  // Détruire le graphique précédent s'il existe
  if (ridesChart) {
    ridesChart.destroy();
  }

  // Créer le nouveau graphique
  ridesChart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [{
        label: 'Nombre de covoiturages',
        data: values,
        backgroundColor: 'rgba(75, 192, 192, 0.4)',
        borderColor: 'rgba(75, 192, 192, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: {
            precision: 0
          }
        }
      },
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            title: function(tooltipItems) {
              return tooltipItems[0].label;
            },
            label: function(context) {
              return `${context.parsed.y} covoiturage(s)`;
            }
          }
        }
      }
    }
  });
}

/**
 * Affiche le graphique des crédits gagnés par jour
 * @param {Array} data - Données des crédits
 */
function renderCreditsChart(data) {
  const ctx = document.getElementById('creditsChart');
  if (!ctx) return;

  // S'assurer que les données nécessaires sont disponibles
  if (!data || !data.dailyCredits || !Array.isArray(data.dailyCredits)) {
    console.error('Format de données incorrect pour le graphique des crédits');
    data = fallbackData.credits;
  }

  // Transformer les données pour le graphique
  const labels = data.dailyCredits.map(entry => formatDate(entry.date));
  const values = data.dailyCredits.map(entry => entry.amount);

  // Détruire le graphique précédent s'il existe
  if (creditsChart) {
    creditsChart.destroy();
  }

  // Créer le nouveau graphique
  creditsChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Crédits gagnés',
        data: values,
        backgroundColor: 'rgba(54, 162, 235, 0.2)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 2,
        tension: 0.3,
        fill: true
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      },
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          callbacks: {
            title: function(tooltipItems) {
              return tooltipItems[0].label;
            },
            label: function(context) {
              return `${context.parsed.y} crédit(s)`;
            }
          }
        }
      }
    }
  });
}

/**
 * Met à jour l'affichage du total des crédits
 * @param {number} total - Total des crédits
 */
function updateTotalCredits(total) {
  const element = document.getElementById('total-credits');
  if (element) {
    element.textContent = total.toLocaleString('fr-FR');
  }
}

/**
 * Formate une date pour l'affichage
 * @param {string} dateString - Date au format ISO
 * @returns {string} - Date formatée
 */
function formatDate(dateString) {
  if (!dateString) return 'N/A';
  
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
  } catch (e) {
    console.error('Erreur de formatage de date:', e);
    return 'N/A';
  }
}
