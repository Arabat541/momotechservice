import { useCallback } from 'react';
import { toast } from '@/components/ui/use-toast';
import { getAllUsers, registerUser, deleteUserById } from '@/lib/api';

export function useUserManagement(setCurrentUser, setLoadingAuth) {

  // Fonction stable, jamais redéfinie
  const fetchAllUsers = useCallback(async () => {
    try {
      const token = localStorage.getItem('token');
      const users = await getAllUsers(token);
      return users;
    } catch (error) {
      toast({ variant: 'destructive', title: 'Erreur', description: error.message || 'Impossible de charger les utilisateurs.' });
      return [];
    }
  }, []);

  const createUser = async (email, password, nom, prenom, role) => {
    try {
      const token = localStorage.getItem('token');
      await registerUser(token, { email, password, nom, prenom, role });
      toast({ title: 'Utilisateur créé', description: `Utilisateur ${email} ajouté.` });
      return true;
    } catch (error) {
      toast({ variant: 'destructive', title: 'Erreur création', description: error.message || 'Impossible de créer l\'utilisateur.' });
      return false;
    }
  };

  const deleteUser = async (userId) => {
    try {
      const token = localStorage.getItem('token');
      await deleteUserById(token, userId);
      toast({ title: 'Utilisateur supprimé', description: `Utilisateur supprimé.` });
      return true;
    } catch (error) {
      toast({ variant: 'destructive', title: 'Erreur suppression', description: error.message || 'Impossible de supprimer l\'utilisateur.' });
      return false;
    }
  };

  return { fetchAllUsers, createUser, deleteUser };
}
