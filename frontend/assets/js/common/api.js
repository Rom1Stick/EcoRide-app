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
    console.log('Aucun token trouvé dans localStorage');
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
      console.log('Réponse API non valide:', response.status);
      return { isAuthenticated: false, isAdmin: false, user: null };
    }
    
    const result = await response.json();
    console.log('Données utilisateur reçues:', result);
    
    if (result.error || !result.data) {
      console.log('Erreur dans les données de l\'API:', result.error);
      return { isAuthenticated: false, isAdmin: false, user: null };
    }
    
    // Vérifier si l'utilisateur a un rôle admin
    let isAdmin = false;
    
    // Vérification dans le tableau de rôles (si présent)
    if (Array.isArray(result.data.roles)) {
      isAdmin = result.data.roles.some(role => 
        typeof role === 'string' ? 
          role.toLowerCase().includes('admin') : 
          (role.name && role.name.toLowerCase().includes('admin'))
      );
    }
    
    // Vérification dans le champ role (si présent)
    if (!isAdmin && result.data.role) {
      if (typeof result.data.role === 'string') {
        isAdmin = result.data.role.toLowerCase().includes('admin');
      } else if (result.data.role.name) {
        isAdmin = result.data.role.name.toLowerCase().includes('admin');
      }
    }
    
    // Vérification dans le champ isAdmin (si présent)
    if (!isAdmin && result.data.isAdmin !== undefined) {
      isAdmin = !!result.data.isAdmin;
    }
    
    // Force l'état admin à true pour test (à enlever en production)
    isAdmin = true;
    
    console.log('Utilisateur admin?', isAdmin);
    
    return {
      isAuthenticated: true,
      isAdmin: isAdmin,
      user: result.data
    };
  } catch (err) {
    console.warn('Erreur lors de la récupération des informations utilisateur', err);
    return { isAuthenticated: false, isAdmin: false, user: null };
  }
}
