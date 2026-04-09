import { useEffect, useRef } from 'react';
import { getProfile } from '@/lib/api';

export function useAuthSession(fetchUserRoleAndSetCurrentUser, setLoadingAuth, setCurrentUser) {
  // Use refs so the effect only runs once on mount
  const fnRef = useRef(fetchUserRoleAndSetCurrentUser);
  fnRef.current = fetchUserRoleAndSetCurrentUser;
  const setLoadingRef = useRef(setLoadingAuth);
  setLoadingRef.current = setLoadingAuth;
  const setUserRef = useRef(setCurrentUser);
  setUserRef.current = setCurrentUser;

  useEffect(() => {
    let cancelled = false;
    setLoadingRef.current(true);

    const checkSession = async () => {
      const token = localStorage.getItem('token');
      if (!token) {
        if (!cancelled) {
          setUserRef.current(null);
          setLoadingRef.current(false);
        }
        return;
      }
      try {
        const userProfileData = await getProfile(token);
        if (cancelled) return;
        if (!userProfileData || userProfileData.error) {
          setUserRef.current(null);
        } else {
          await fnRef.current(userProfileData);
        }
      } catch {
        if (!cancelled) setUserRef.current(null);
      } finally {
        if (!cancelled) setLoadingRef.current(false);
      }
    };

    checkSession();

    return () => { cancelled = true; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []); // Run only once on mount
}