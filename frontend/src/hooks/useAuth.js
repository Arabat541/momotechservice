import { useState, useEffect, useCallback } from 'react';
import { useAuthActions } from '@/hooks/auth/useAuthActions';
import { useUserProfile } from '@/hooks/auth/useUserProfile';
import { useAuthSession } from '@/hooks/auth/useAuthSession';
import { useUserManagement } from '@/hooks/auth/useUserManagement';
import { getProfile } from '../lib/api';

export function useAuth() {
  const [currentUser, setCurrentUser] = useState(null);
  const [users, setUsers] = useState([]); 
  const [loadingAuth, setLoadingAuth] = useState(true);

  const { fetchUserRoleAndSetCurrentUser } = useUserProfile(setCurrentUser, setLoadingAuth);
  
  useAuthSession(fetchUserRoleAndSetCurrentUser, setLoadingAuth, setCurrentUser);
  
  const { handleLogin, handleSignup, handleSignout } = useAuthActions(setCurrentUser, setLoadingAuth);
  
  // Utiliser useUserManagement sans dépendance à currentUser
  const { 
    createUser, 
    deleteUser, 
    fetchAllUsers: fetchAllUsersFromHook 
  } = useUserManagement(setCurrentUser, setUsers);

  // Fonction stable, ne dépend que du rôle
  const fetchAllUsers = useCallback(async () => {
    if (currentUser?.role === 'patron') {
      const usersList = await fetchAllUsersFromHook();
      // Ne met à jour le state que si la liste a changé
      setUsers(prev => {
        if (JSON.stringify(prev) !== JSON.stringify(usersList)) {
          return usersList;
        }
        return prev;
      });
      return usersList;
    }
    setUsers([]); // Clear users if not patron or no current user
    return [];
  }, [currentUser, fetchAllUsersFromHook]);

  // Effect to fetch users when currentUser (et leur rôle) change
  useEffect(() => {
    if (currentUser && currentUser.role === 'patron') {
      fetchAllUsers();
    } else {
      setUsers([]); // Clear users if not patron or logged out
    }
  }, [currentUser, fetchAllUsers]);

  return { 
    currentUser, 
    users, 
    loadingAuth, 
    handleLogin, 
    handleSignup, 
    handleLogout: handleSignout, 
    createUser, 
    deleteUser,
    fetchAllUsers // Expose the correct fetchAllUsers
  };
}

export function useUserRole(token) {
  const [role, setRole] = useState(null);
  useEffect(() => {
    if (!token) return;
    getProfile(token)
      .then(user => setRole(user.role))
      .catch(() => setRole(null));
  }, [token]);
  return role;
}
