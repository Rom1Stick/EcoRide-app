import { initAuthUI } from '../common/auth.js';

(async () => {
  const authOK = await initAuthUI();
  if (!authOK) return;

  console.log('Admin dashboard chargé.');

  // Préparer les en-têtes
  const token = localStorage.getItem('auth_token');
  const headers = {
    'Content-Type': 'application/json',
    Authorization: `Bearer ${token}`,
  };

  // Charger les utilisateurs en attente
  try {
    const resp = await fetch('/api/admin/users/pending', {
      method: 'GET',
      credentials: 'include',
      headers,
    });
    if (!resp.ok) {
      throw new Error(`Erreur ${resp.status}`);
    }
    const result = await resp.json();
    if (result.error) throw new Error(result.message);
    renderPendingUsers(result.data, headers);
  } catch (err) {
    console.error(err);
  }
})();

function renderPendingUsers(users, headers) {
  const tbody = document.getElementById('pending-users-tbody');
  tbody.innerHTML = '';

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
          const res2 = await r.json();
          if (res2.error) throw new Error(res2.message);
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
