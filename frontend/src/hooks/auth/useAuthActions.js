import { useNavigate } from 'react-router-dom';
import { toast } from '@/components/ui/use-toast';
import { login, registerUser } from '@/lib/api';

export function useAuthActions(setCurrentUser, setLoadingAuth) {
  const navigate = useNavigate();

  const handleLogin = async (email, password) => {
    setLoadingAuth(true);
    try {
      const response = await login(email, password);
      if (response && response.token && response.user) {
        localStorage.setItem('token', response.token);
        // Conversion _id -> id et suppression du champ password
        const { _id, ...rest } = response.user || {};
        const userSafe = { id: _id, ...rest };
        setCurrentUser(userSafe);
        localStorage.setItem('profile', JSON.stringify(userSafe)); // Ajout stockage profil
        toast({ title: "Connexion réussie!", description: `Bienvenue ${userSafe.email}!` });
        navigate('/reparations-place', { replace: true });
        setLoadingAuth(false);
        return true;
      } else if (response && response.error) {
        let description = response.error;
        toast({ variant: "destructive", title: "Erreur de connexion", description });
        setLoadingAuth(false);
        return false;
      } else {
        toast({ variant: "destructive", title: "Erreur de connexion", description: "Réponse inattendue du serveur." });
        setLoadingAuth(false);
        return false;
      }
    } catch (error) {
      let description = error.message || "Erreur inconnue lors de la connexion.";
      toast({ variant: "destructive", title: "Erreur de connexion", description });
      setLoadingAuth(false);
      return false;
    }
  };

  const handleSignup = async (email, password, nom, prenom, role) => {
    setLoadingAuth(true);
    try {
      const data = await registerUser(null, { email, password, nom, prenom, role });
      if (data && data.token && data.user) {
        localStorage.setItem('token', data.token);
        // Suppression du champ password si jamais il existe
        const userSafe = { ...data.user };
        delete userSafe.password;
        setCurrentUser(userSafe);
        localStorage.setItem('profile', JSON.stringify(userSafe)); // Ajout stockage profil
        toast({ title: "Inscription et connexion réussies", description: `Bienvenue ${userSafe.email}!` });
        setLoadingAuth(false);
        return true;
      } else {
        toast({ variant: "destructive", title: "Erreur d'inscription", description: "Utilisateur créé mais connexion impossible." });
        setLoadingAuth(false);
        return false;
      }
    } catch (error) {
      let description = error.message;
      if (description && description.includes('email') && description.includes('existe')) {
        description = "Cet email est déjà utilisé. Veuillez en choisir un autre.";
      } else if (description && description.includes('E11000')) {
        description = "Cet email est déjà utilisé. Veuillez en choisir un autre.";
      }
      toast({ variant: "destructive", title: "Erreur d'inscription", description });
      setLoadingAuth(false);
      return false;
    }
  };

  const handleSignout = async () => {
    // TODO: Implémenter la déconnexion via l'API backend (suppression du token côté client)
    setCurrentUser(null);
    localStorage.removeItem('token');
    navigate('/login');
  };

  return { handleLogin, handleSignup, handleSignout };
}