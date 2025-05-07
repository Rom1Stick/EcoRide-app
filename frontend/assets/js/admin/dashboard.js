import { api } from '../common/api.js';

document.addEventListener('DOMContentLoaded', () => {
  // Récupérer et charger les derniers utilisateurs inscrits
  async function loadLatestUsers() {
    const tableBody = document.querySelector('.admin-table tbody');

    if (!tableBody) return;

    try {
      // Simuler les données en attendant l'API réelle
      const users = await api.get('/admin/users/latest');

      if (users.length === 0) {
        tableBody.innerHTML = '<tr><td colspan="4">Aucun utilisateur enregistré</td></tr>';
        return;
      }

      // Remplir le tableau
      tableBody.innerHTML = users
        .map(
          (user) => `
        <tr>
          <td>${user.name}</td>
          <td>${user.email}</td>
          <td>${new Date(user.created_at).toLocaleDateString()}</td>
          <td>
            <button class="button button--small" data-action="view" data-id="${user.id}">Voir</button>
            <button class="button button--small button--danger" data-action="delete" data-id="${user.id}">Supprimer</button>
          </td>
        </tr>
      `
        )
        .join('');

      // Ajouter les écouteurs d'événements pour les actions
      tableBody.querySelectorAll('[data-action]').forEach((button) => {
        button.addEventListener('click', handleUserAction);
      });
    } catch (error) {
      console.error('Erreur de chargement des utilisateurs:', error);
      tableBody.innerHTML = '<tr><td colspan="4">Erreur de chargement des données</td></tr>';
    }
  }

  // Gérer les actions sur les utilisateurs
  async function handleUserAction(e) {
    const action = e.target.dataset.action;
    const userId = e.target.dataset.id;

    if (action === 'view') {
      // Rediriger vers la page de détails de l'utilisateur
      window.location.href = `user-details.html?id=${userId}`;
    } else if (action === 'delete') {
      if (confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')) {
        try {
          await api.post(`/admin/users/${userId}/delete`);
          // Recharger la liste après suppression
          loadLatestUsers();
        } catch (error) {
          alert("Erreur lors de la suppression de l'utilisateur");
        }
      }
    }
  }

  // Charger les données au chargement de la page
  loadLatestUsers();
});
