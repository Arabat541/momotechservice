import { useEffect } from 'react';
import { toast } from '@/components/ui/use-toast';
import { getProfile } from '@/lib/api';

export function useAuthSession(fetchUserRoleAndSetCurrentUser, setLoadingAuth, setCurrentUser) {
  useEffect(() => {
    setLoadingAuth(true); 
    const checkSession = async () => {
        const token = localStorage.getItem('token');
        if (!token) {
            setCurrentUser(null);
            setLoadingAuth(false);
            return;
        }
        try {
            const userProfileData = await getProfile(token);
            if (!userProfileData || userProfileData.error) {
                setCurrentUser(null);
            } else {
                await fetchUserRoleAndSetCurrentUser(userProfileData);
            }
        } catch (err) {
            setCurrentUser(null);
        } finally {
            setLoadingAuth(false);
        }
    };
    let isMounted = true;
    if (isMounted) {
        checkSession();
    }

    return () => {
      isMounted = false;
    };
  }, [fetchUserRoleAndSetCurrentUser, setLoadingAuth, setCurrentUser]); 
}