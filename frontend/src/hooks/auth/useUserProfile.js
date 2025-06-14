import { useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { toast } from '@/components/ui/use-toast';
import { getProfile } from '@/lib/api';

export function useUserProfile(setCurrentUser, setLoadingAuth) {
  const navigate = useNavigate();

  const fetchUserRoleAndSetCurrentUser = useCallback(async (authUser, isLoginOrSignupEvent = false) => {
    setLoadingAuth(true);
    const userId = authUser.id || authUser._id;
    if (!authUser || !userId) {
      setCurrentUser(null);
      setLoadingAuth(false);
      return null;
    }
    try {
      // Appel à l'API backend pour récupérer le profil utilisateur
      const token = authUser.token || localStorage.getItem('token');
      const userProfileData = await getProfile(token);
      if (userProfileData) {
        setCurrentUser({
          id: userProfileData._id || userProfileData.id,
          email: userProfileData.email,
          role: userProfileData.role,
          nom: userProfileData.nom,
          prenom: userProfileData.prenom
        });
        // Redirige si on est sur /auth et qu'on a un profil valide
        if (window.location.pathname === '/auth') {
          navigate('/reparations-place', { replace: true });
        }
        if (isLoginOrSignupEvent) {
          navigate('/reparations-place', { replace: true });
        }
      } else {
        setCurrentUser(null);
      }
    } catch (error) {
      let description = "Impossible de récupérer le profil utilisateur.";
      if (error.message && (error.message.includes('Failed to fetch') || error.message.includes('NetworkError'))) {
        description = "Problème de réseau ou serveur indisponible. Veuillez réessayer plus tard.";
      }
      toast({ variant: "destructive", title: "Erreur Profil", description });
      setCurrentUser(null);
    } finally {
      setLoadingAuth(false);
    }
    return null;
  }, [navigate, setCurrentUser, setLoadingAuth]);

  return { fetchUserRoleAndSetCurrentUser };
}