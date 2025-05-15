export async function registerUser({ name, email, password }) {
  const response = await fetch('/api/auth/register', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ name, email, password }),
  });
  const result = await response.json();
  if (!response.ok || result.error) {
    throw result;
  }
  return result;
}

// Fonction pour vérifier si l'utilisateur a un rôle admin
export async function getUserInfo() {
  const storedToken = localStorage.getItem('auth_token');
  if (!storedToken) {
    return { isAuthenticated: false, isAdmin: false, user: null };
  }

  try {
    const headers = { Authorization: `Bearer ${storedToken}` };
    const response = await fetch('/api/users/me', {
      method: 'GET',
      credentials: 'include',
      headers,
    });

    if (!response.ok) {
      return { isAuthenticated: false, isAdmin: false, user: null };
    }

    const result = await response.json();

    if (result.error || !result.data) {
      return { isAuthenticated: false, isAdmin: false, user: null };
    }

    // Vérifier si l'utilisateur a un rôle admin
    let isAdmin = false;

    // Priorité 1: Vérifier le champ isAdmin directement renvoyé par l'API
    if (result.data.isAdmin !== undefined) {
      isAdmin = !!result.data.isAdmin;
    }
    // Priorité 2: Vérification dans le tableau de rôles
    else if (Array.isArray(result.data.roles)) {
      isAdmin = result.data.roles.some((role) => {
        return typeof role === 'string'
          ? role.toLowerCase().includes('admin')
          : role.name && role.name.toLowerCase().includes('admin');
      });
    }
    // Priorité 3: Vérification dans le champ role
    else if (result.data.role) {
      if (typeof result.data.role === 'string') {
        isAdmin = result.data.role.toLowerCase().includes('admin');
      } else if (result.data.role.name) {
        isAdmin = result.data.role.name.toLowerCase().includes('admin');
      }
    }

    return {
      isAuthenticated: true,
      isAdmin: isAdmin,
      user: result.data,
    };
  } catch (err) {
    return { isAuthenticated: false, isAdmin: false, user: null };
  }
}
