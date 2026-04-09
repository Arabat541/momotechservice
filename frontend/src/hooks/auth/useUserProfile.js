import { useCallback, useRef } from 'react';
import { useNavigate } from 'react-router-dom';

export function useUserProfile(setCurrentUser, setLoadingAuth) {
  const navigate = useNavigate();
  // Use a ref so the callback never depends on navigate identity
  const navigateRef = useRef(navigate);
  navigateRef.current = navigate;

  // authUser already contains the profile data from getProfile() in useAuthSession.
  // No need to call getProfile again — just extract the fields and set currentUser.
  const fetchUserRoleAndSetCurrentUser = useCallback(async (authUser) => {
    const userId = authUser.id || authUser._id;
    if (!authUser || !userId) {
      setCurrentUser(null);
      setLoadingAuth(false);
      return null;
    }
    setCurrentUser({
      id: authUser._id || authUser.id,
      email: authUser.email,
      role: authUser.role,
      nom: authUser.nom,
      prenom: authUser.prenom
    });
    setLoadingAuth(false);
    // Redirect if still on /auth
    if (window.location.pathname === '/auth') {
      navigateRef.current('/reparations-place', { replace: true });
    }
    return null;
  }, [setCurrentUser, setLoadingAuth]);

  return { fetchUserRoleAndSetCurrentUser };
}