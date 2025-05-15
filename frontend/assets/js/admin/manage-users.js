import { initAuthUI } from '../common/auth.js';
import { checkAdminAccess } from './admin-auth.js';

// En-têtes globaux pour les requêtes API admin
let headersGlobal = {};

// S'assure que l'utilisateur est connecté et a les droits d'administration
(async () => {
  // Vérifier que l'utilisateur est authentifié
  const authOK = await initAuthUI();
  if (!authOK) return;

  // Vérifier que l'utilisateur a les droits d'administration
  const isAdmin = await checkAdminAccess();
  if (!isAdmin) return;

  // Configuration du bouton de déconnexion pour rediriger vers la page d'accueil
  const logoutBtn = document.getElementById('logout-btn');
  if (logoutBtn) {
    // Suppression de tous les écouteurs d'événements existants
    const newLogoutBtn = logoutBtn.cloneNode(true);
    logoutBtn.parentNode.replaceChild(newLogoutBtn, logoutBtn);

    // Ajout de notre écouteur d'événement personnalisé
    newLogoutBtn.addEventListener('click', async () => {
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

  const token = localStorage.getItem('auth_token');
  if (!token) {
    window.location.href = '/pages/public/login.html';
    return;
  }

  const headers = {
    'Content-Type': 'application/json',
    Authorization: `Bearer ${token}`,
  };
  headersGlobal = headers;

  try {
    const [usersResp, rolesResp] = await Promise.all([
      fetch('/api/admin/users', { method: 'GET', credentials: 'include', headers }),
      fetch('/api/admin/roles', { method: 'GET', credentials: 'include', headers }),
    ]);

    // Vérifier le statut avant parsing JSON
    if (!usersResp.ok) {
      throw new Error(`Erreur ${usersResp.status} lors de la récupération des utilisateurs`);
    }
    if (!rolesResp.ok) {
      throw new Error(`Erreur ${rolesResp.status} lors de la récupération des rôles`);
    }

    const usersResult = await usersResp.json();
    if (usersResult.error) {
      throw new Error(usersResult.message || 'Erreur lors de la récupération des utilisateurs');
    }
    const rolesResult = await rolesResp.json();
    if (rolesResult.error) {
      throw new Error(rolesResult.message || 'Erreur lors de la récupération des rôles');
    }

    const users = usersResult.data;
    const roles = rolesResult.data;

    renderUsersTable(users, roles);
  } catch (err) {
    console.error(err);
    alert('Impossible de charger la liste des utilisateurs');
  }
})();

function renderUsersTable(users, roles) {
  const tbody = document.getElementById('users-tbody');
  tbody.innerHTML = '';

  users.forEach((user) => {
    const tr = document.createElement('tr');

    // Colonne Nom
    const tdName = document.createElement('td');
    tdName.textContent = user.name;
    // Colonne Email
    const tdEmail = document.createElement('td');
    tdEmail.textContent = user.email;

    // Colonne Rôles
    const tdRoles = document.createElement('td');
    user.roles.forEach((r) => {
      const span = document.createElement('span');
      span.textContent = r.name;
      const btn = document.createElement('button');
      btn.textContent = '×';
      btn.title = 'Retirer ce rôle';
      btn.dataset.userId = user.id;
      btn.dataset.roleId = r.id;
      btn.addEventListener('click', onRemoveRole);
      span.appendChild(btn);
      tdRoles.appendChild(span);
      tdRoles.appendChild(document.createTextNode(' '));
    });

    // Colonne Ajouter un rôle
    const tdAdd = document.createElement('td');
    const select = document.createElement('select');
    roles.forEach((r) => {
      if (!user.roles.find((ur) => ur.id === r.id)) {
        const option = document.createElement('option');
        option.value = r.id;
        option.textContent = r.name;
        select.appendChild(option);
      }
    });
    const addBtn = document.createElement('button');
    addBtn.textContent = 'Ajouter';
    addBtn.dataset.userId = user.id;
    addBtn.addEventListener('click', () => onAddRole(user.id, select.value));
    tdAdd.appendChild(select);
    tdAdd.appendChild(addBtn);

    tr.appendChild(tdName);
    tr.appendChild(tdEmail);
    tr.appendChild(tdRoles);
    tr.appendChild(tdAdd);
    tbody.appendChild(tr);
  });
}

async function onAddRole(userId, roleId) {
  try {
    const resp = await fetch(`/api/admin/users/${userId}/roles`, {
      method: 'POST',
      credentials: 'include',
      headers: headersGlobal,
      body: JSON.stringify({ role_id: parseInt(roleId, 10) }),
    });
    // Vérifier le statut et le type de contenu avant parsing
    const contentType = resp.headers.get('Content-Type') || '';
    if (!resp.ok || !contentType.includes('application/json')) {
      const text = await resp.text();
      throw new Error(text || "Erreur lors de l'ajout du rôle");
    }
    const res = await resp.json();
    if (res.error) {
      throw new Error(res.message || "Erreur lors de l'ajout du rôle");
    }
    // Recharger la page
    window.location.reload();
  } catch (err) {
    console.error(err);
    alert(err.message || "Erreur lors de l'ajout du rôle");
  }
}

async function onRemoveRole(e) {
  const btn = e.currentTarget;
  const userId = btn.dataset.userId;
  const roleId = btn.dataset.roleId;
  if (!confirm('Êtes-vous sûr de vouloir retirer ce rôle ?')) return;
  try {
    const resp = await fetch(`/api/admin/users/${userId}/roles/${roleId}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: headersGlobal,
    });
    // Vérifier le statut et le type de contenu avant parsing
    const contentTypeDel = resp.headers.get('Content-Type') || '';
    if (!resp.ok || !contentTypeDel.includes('application/json')) {
      const text = await resp.text();
      throw new Error(text || 'Erreur lors de la suppression du rôle');
    }
    const res = await resp.json();
    if (res.error) {
      throw new Error(res.message || 'Erreur lors de la suppression du rôle');
    }
    window.location.reload();
  } catch (err) {
    console.error(err);
    alert(err.message || 'Erreur lors de la suppression du rôle');
  }
}
