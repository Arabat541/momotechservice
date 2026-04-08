// Service API pour le CRM (auth, profil, export, boutiques, etc.)
const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:5000/api';

// Helper: get current shopId from localStorage
function getShopId() {
  return localStorage.getItem('currentShopId') || '';
}

// Helper: build headers with auth + shop
function authHeaders(token) {
  const headers = { Authorization: `Bearer ${token}` };
  const shopId = getShopId();
  if (shopId) headers['X-Shop-Id'] = shopId;
  return headers;
}

function authJsonHeaders(token) {
  return { ...authHeaders(token), 'Content-Type': 'application/json' };
}

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
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur profil');
  return res.json();
}

export async function updateProfile(token, updates) {
  const res = await fetch(`${API_URL}/users/me`, {
    method: 'PUT',
    headers: authJsonHeaders(token),
    body: JSON.stringify(updates)
  });
  if (!res.ok) throw new Error('Erreur modification profil');
  return res.json();
}

export async function exportUsersCSV(token) {
  const res = await fetch(`${API_URL}/export/users/csv`, {
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur export CSV');
  return res.blob();
}

export async function exportUsersPDF(token) {
  const res = await fetch(`${API_URL}/export/users/pdf`, {
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur export PDF');
  return res.blob();
}

export async function getAllUsers(token) {
  const res = await fetch(`${API_URL}/users/users`, {
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur chargement utilisateurs');
  return res.json();
}

export async function registerUser(token, { email, password, nom, prenom, role }) {
  const headers = token ? authJsonHeaders(token) : { 'Content-Type': 'application/json' };
  const res = await fetch(`${API_URL}/users/register`, {
    method: 'POST',
    headers,
    body: JSON.stringify({ email, password, nom, prenom, role })
  });
  if (!res.ok) throw new Error('Erreur création utilisateur');
  return res.json();
}

export async function deleteUserById(token, userId) {
  const res = await fetch(`${API_URL}/users/users/${userId}`, {
    method: 'DELETE',
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur suppression utilisateur');
  return res.json();
}

export async function updateUserRole(token, userId, role) {
  const res = await fetch(`${API_URL}/users/users/${userId}/role`, {
    method: 'PUT',
    headers: authJsonHeaders(token),
    body: JSON.stringify({ role })
  });
  if (!res.ok) throw new Error('Erreur modification rôle');
  return res.json();
}

// --- Reparations ---
export async function fetchRepairs(token) {
  const res = await fetch(`${API_URL}/repairs`, {
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur récupération réparations');
  return res.json();
}

export async function createRepair(token, repair) {
  const res = await fetch(`${API_URL}/repairs`, {
    method: 'POST',
    headers: authJsonHeaders(token),
    body: JSON.stringify(repair)
  });
  if (!res.ok) throw new Error('Erreur création réparation');
  return res.json();
}

export async function updateRepair(token, id, updates) {
  const res = await fetch(`${API_URL}/repairs/${id}`, {
    method: 'PUT',
    headers: authJsonHeaders(token),
    body: JSON.stringify(updates)
  });
  if (!res.ok) throw new Error('Erreur modification réparation');
  return res.json();
}

export async function deleteRepair(token, id) {
  const res = await fetch(`${API_URL}/repairs/${id}`, {
    method: 'DELETE',
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur suppression réparation');
  return res.json();
}

// --- Stocks ---
export async function fetchStocks(token) {
  const res = await fetch(`${API_URL}/stocks`, {
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur récupération stocks');
  return res.json();
}

export async function createStock(token, stock) {
  const res = await fetch(`${API_URL}/stocks`, {
    method: 'POST',
    headers: authJsonHeaders(token),
    body: JSON.stringify(stock)
  });
  if (!res.ok) throw new Error('Erreur création stock');
  return res.json();
}

export async function updateStock(token, id, updates) {
  const res = await fetch(`${API_URL}/stocks/${id}`, {
    method: 'PUT',
    headers: authJsonHeaders(token),
    body: JSON.stringify(updates)
  });
  if (!res.ok) throw new Error('Erreur modification stock');
  return res.json();
}

export async function deleteStock(token, id) {
  const res = await fetch(`${API_URL}/stocks/${id}`, {
    method: 'DELETE',
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur suppression stock');
  return res.json();
}

// --- Paramètres ---
export async function getSettings(token) {
  const res = await fetch(`${API_URL}/settings`, {
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur chargement paramètres');
  return res.json();
}

export async function updateSettings(token, { companyInfo, warranty }) {
  const res = await fetch(`${API_URL}/settings`, {
    method: 'PUT',
    headers: authJsonHeaders(token),
    body: JSON.stringify({ companyInfo, warranty })
  });
  if (!res.ok) throw new Error('Erreur mise à jour paramètres');
  return res.json();
}

export async function resetPasswordRequest(email) {
  const res = await fetch(`${API_URL}/users/reset-password`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email })
  });
  if (!res.ok) throw new Error('Erreur réinitialisation mot de passe');
  return res.json();
}

export async function confirmResetPassword(email, code, password) {
  const res = await fetch(`${API_URL}/users/confirm-reset-password`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, code, password })
  });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data.error || 'Erreur réinitialisation mot de passe');
  }
  return res.json();
}

// --- Boutiques ---
export async function getMyShops(token) {
  const res = await fetch(`${API_URL}/shops`, {
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur chargement boutiques');
  return res.json();
}

export async function createShop(token, shop) {
  const res = await fetch(`${API_URL}/shops`, {
    method: 'POST',
    headers: authJsonHeaders(token),
    body: JSON.stringify(shop)
  });
  if (!res.ok) throw new Error('Erreur création boutique');
  return res.json();
}

export async function updateShop(token, id, updates) {
  const res = await fetch(`${API_URL}/shops/${id}`, {
    method: 'PUT',
    headers: authJsonHeaders(token),
    body: JSON.stringify(updates)
  });
  if (!res.ok) throw new Error('Erreur modification boutique');
  return res.json();
}

export async function deleteShopById(token, id) {
  const res = await fetch(`${API_URL}/shops/${id}`, {
    method: 'DELETE',
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur suppression boutique');
  return res.json();
}

export async function addUserToShop(token, shopId, userId) {
  const res = await fetch(`${API_URL}/shops/${shopId}/users`, {
    method: 'POST',
    headers: authJsonHeaders(token),
    body: JSON.stringify({ userId })
  });
  if (!res.ok) throw new Error('Erreur ajout utilisateur à la boutique');
  return res.json();
}

export async function removeUserFromShop(token, shopId, userId) {
  const res = await fetch(`${API_URL}/shops/${shopId}/users`, {
    method: 'DELETE',
    headers: authJsonHeaders(token),
    body: JSON.stringify({ userId })
  });
  if (!res.ok) throw new Error('Erreur retrait utilisateur de la boutique');
  return res.json();
}

// --- Storefront (public, no auth) ---
export async function getShopPublicInfo(shopId) {
  const res = await fetch(`${API_URL}/storefront/${shopId}`);
  if (!res.ok) throw new Error('Boutique introuvable');
  return res.json();
}

export async function trackRepair(numero) {
  const res = await fetch(`${API_URL}/storefront/track/${encodeURIComponent(numero)}`);
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data.error || 'Réparation introuvable');
  }
  return res.json();
}

// --- SAV ---
export async function fetchSAVs(token) {
  const res = await fetch(`${API_URL}/sav`, {
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur récupération SAV');
  return res.json();
}

export async function createSAV(token, sav) {
  const res = await fetch(`${API_URL}/sav`, {
    method: 'POST',
    headers: authJsonHeaders(token),
    body: JSON.stringify(sav)
  });
  if (!res.ok) throw new Error('Erreur création SAV');
  return res.json();
}

export async function updateSAV(token, id, updates) {
  const res = await fetch(`${API_URL}/sav/${id}`, {
    method: 'PUT',
    headers: authJsonHeaders(token),
    body: JSON.stringify(updates)
  });
  if (!res.ok) throw new Error('Erreur modification SAV');
  return res.json();
}

export async function deleteSAV(token, id) {
  const res = await fetch(`${API_URL}/sav/${id}`, {
    method: 'DELETE',
    headers: authHeaders(token)
  });
  if (!res.ok) throw new Error('Erreur suppression SAV');
  return res.json();
}

export async function lookupRepairForSAV(token, numero) {
  const res = await fetch(`${API_URL}/sav/lookup/${encodeURIComponent(numero)}`, {
    headers: authHeaders(token)
  });
  if (!res.ok) {
    const data = await res.json().catch(() => ({}));
    throw new Error(data.error || 'Réparation introuvable');
  }
  return res.json();
}
