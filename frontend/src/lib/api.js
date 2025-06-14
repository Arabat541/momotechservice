// Service API pour le CRM (auth, profil, export, etc.)
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:5000/api';

export async function login(email, password) {
  const res = await fetch(`${API_URL}/users/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });
  if (!res.ok) throw new Error('Erreur de connexion');
  return res.json();
}

export async function getProfile(token) {
  const res = await fetch(`${API_URL}/users/me`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  if (!res.ok) throw new Error('Erreur profil');
  return res.json();
}

export async function updateProfile(token, updates) {
  const res = await fetch(`${API_URL}/users/me`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(updates)
  });
  if (!res.ok) throw new Error('Erreur modification profil');
  return res.json();
}

export async function exportUsersCSV(token) {
  const res = await fetch(`${API_URL}/export/users/csv`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  if (!res.ok) throw new Error('Erreur export CSV');
  return res.blob();
}

export async function exportUsersPDF(token) {
  const res = await fetch(`${API_URL}/export/users/pdf`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  if (!res.ok) throw new Error('Erreur export PDF');
  return res.blob();
}

export async function getAllUsers(token) {
  const res = await fetch(`${API_URL}/users/users`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  if (!res.ok) throw new Error('Erreur chargement utilisateurs');
  return res.json();
}

export async function registerUser(token, { email, password, nom, prenom, role }) {
  const res = await fetch(`${API_URL}/users/register`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify({ email, password, nom, prenom, role })
  });
  if (!res.ok) throw new Error('Erreur création utilisateur');
  return res.json();
}

export async function deleteUserById(token, userId) {
  const res = await fetch(`${API_URL}/users/users/${userId}`, {
    method: 'DELETE',
    headers: { Authorization: `Bearer ${token}` }
  });
  if (!res.ok) throw new Error('Erreur suppression utilisateur');
  return res.json();
}

export async function updateUserRole(token, userId, role) {
  const res = await fetch(`${API_URL}/users/users/${userId}/role`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify({ role })
  });
  if (!res.ok) throw new Error('Erreur modification rôle');
  return res.json();
}

// --- Reparations ---
export async function fetchRepairs(token) {
  const res = await fetch(`${API_URL}/repairs`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  if (!res.ok) throw new Error('Erreur récupération réparations');
  return res.json();
}

export async function createRepair(token, repair) {
  const res = await fetch(`${API_URL}/repairs`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(repair)
  });
  if (!res.ok) throw new Error('Erreur création réparation');
  return res.json();
}

export async function updateRepair(token, id, updates) {
  const res = await fetch(`${API_URL}/repairs/${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(updates)
  });
  if (!res.ok) throw new Error('Erreur modification réparation');
  return res.json();
}

export async function deleteRepair(token, id) {
  const res = await fetch(`${API_URL}/repairs/${id}`, {
    method: 'DELETE',
    headers: { Authorization: `Bearer ${token}` }
  });
  if (!res.ok) throw new Error('Erreur suppression réparation');
  return res.json();
}

// --- Stocks ---
export async function fetchStocks(token) {
  const res = await fetch(`${API_URL}/stocks`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  if (!res.ok) throw new Error('Erreur récupération stocks');
  return res.json();
}

export async function createStock(token, stock) {
  const res = await fetch(`${API_URL}/stocks`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(stock)
  });
  if (!res.ok) throw new Error('Erreur création stock');
  return res.json();
}

export async function updateStock(token, id, updates) {
  const res = await fetch(`${API_URL}/stocks/${id}`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify(updates)
  });
  if (!res.ok) throw new Error('Erreur modification stock');
  return res.json();
}

export async function deleteStock(token, id) {
  const res = await fetch(`${API_URL}/stocks/${id}`, {
    method: 'DELETE',
    headers: { Authorization: `Bearer ${token}` }
  });
  if (!res.ok) throw new Error('Erreur suppression stock');
  return res.json();
}

// --- Paramètres ---
export async function getSettings(token) {
  const res = await fetch(`${API_URL}/settings`, {
    headers: { Authorization: `Bearer ${token}` }
  });
  if (!res.ok) throw new Error('Erreur chargement paramètres');
  return res.json();
}

export async function updateSettings(token, { companyInfo, warranty }) {
  const res = await fetch(`${API_URL}/settings`, {
    method: 'PUT',
    headers: {
      'Content-Type': 'application/json',
      Authorization: `Bearer ${token}`
    },
    body: JSON.stringify({ companyInfo, warranty })
  });
  if (!res.ok) throw new Error('Erreur mise à jour paramètres');
  return res.json();
}

export async function resetPasswordRequest(email, password) {
  const res = await fetch(`${API_URL}/users/reset-password`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });
  if (!res.ok) throw new Error('Erreur réinitialisation mot de passe');
  return res.json();
}
