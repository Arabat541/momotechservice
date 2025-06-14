import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import { motion } from 'framer-motion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from '@/components/ui/use-toast';
import { LogIn, UserPlus, Smartphone, Loader2, AlertTriangle } from 'lucide-react';
import { getProfile, updateProfile, resetPasswordRequest } from '../lib/api';
import { useLocation } from 'react-router-dom';

const AuthPage = ({ onLogin, onSignup, authTimedOut }) => {
  const location = useLocation();
  const [isLogin, setIsLogin] = useState(true);
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [confirmPassword, setConfirmPassword] = useState('');
  const [isFirstSignupAttempt, setIsFirstSignupAttempt] = useState(false);
  const [loadingPage, setLoadingPage] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [profil, setProfil] = useState(null);
  const [form, setForm] = useState({ nom: '', prenom: '', email: '', password: '' });
  const [message, setMessage] = useState('');
  const [showResetPassword, setShowResetPassword] = useState(false); // Ajout du flag

  const token = localStorage.getItem('token');

  const checkFirstUser = async () => {
    // TODO: Remplacer la vérification du premier utilisateur par un appel à l'API backend
    setIsFirstSignupAttempt(false);
    setLoadingPage(false);
  };

  useEffect(() => {
    checkFirstUser();
  }, []);

  useEffect(() => {
    if (authTimedOut) {
      // If auth timed out globally, ensure this page isn't stuck in its own loading state
      setLoadingPage(false);
      setIsSubmitting(false);
    }
  }, [authTimedOut]);

  useEffect(() => {
    // Ne charge le profil que si on n'est PAS sur /auth
    if (!token) return;
    if (location.pathname !== '/auth') {
      getProfile(token).then(setProfil);
    } else {
      setProfil(null);
    }
  }, [token, location.pathname]);

  useEffect(() => {
    if (profil) setForm({ nom: profil.nom, prenom: profil.prenom, email: profil.email, password: '' });
  }, [profil]);

  useEffect(() => {
    // Réinitialise l'état local à chaque fois qu'on arrive sur /auth
    if (location.pathname === '/auth') {
      setShowResetPassword(false);
      setProfil(null);
      setForm({ nom: '', prenom: '', email: '', password: '' });
      setMessage('');
      setPassword('');
      setConfirmPassword('');
      setIsLogin(true);
    }
  }, [location.pathname]);

  const handleChange = e => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (authTimedOut) {
        toast({
            variant: "destructive",
            title: "Authentification bloquée",
            description: "Le processus d'authentification a expiré. Veuillez rafraîchir la page et réessayer.",
            duration: 7000
        });
        return;
    }
    setIsSubmitting(true);
    let success = false;
    if (isLogin) {
      success = await onLogin(email, password);
    } else {
      if (form.password !== confirmPassword) {
        toast({
          variant: "destructive",
          title: "Erreur d'inscription",
          description: "Les mots de passe ne correspondent pas.",
        });
        setIsSubmitting(false);
        return;
      }
      success = await onSignup(form.email, form.password, form.nom, form.prenom, form.role);
      if (success) {
        await checkFirstUser(); 
      }
    }
    setIsSubmitting(false);
  };

  const handleProfileSubmit = async e => {
    e.preventDefault();
    if (showResetPassword) {
      setIsSubmitting(true);
      try {
        await resetPasswordRequest(form.email, form.password);
        setMessage('Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.');
        setShowResetPassword(false);
      } catch (e) {
        setMessage('Erreur lors de la réinitialisation du mot de passe');
      }
      setIsSubmitting(false);
      return;
    }
    try {
      const updated = await updateProfile(token, form);
      setProfil(updated);
      setMessage('Profil mis à jour !');
    } catch (e) {
      setMessage('Erreur lors de la mise à jour');
    }
  };

  if (loadingPage && !isSubmitting && !authTimedOut) {
    return (
      <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 p-4">
        <Loader2 className="h-12 w-12 animate-spin text-purple-400" />
      </div>
    );
  }

  // Affiche l'écran de chargement uniquement si on n'est PAS sur /auth
  if (!profil && token && location.pathname !== '/auth') return <div>Chargement...</div>;

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 p-4">
      <motion.div
        initial={{ opacity: 0, y: -50 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        className="w-full max-w-md bg-white/10 backdrop-filter backdrop-blur-lg shadow-2xl rounded-xl p-8 glass-effect"
      >
        {showResetPassword ? (
          <form onSubmit={handleProfileSubmit} className="space-y-6">
            <h2 className="text-xl font-semibold text-white mb-4">Réinitialiser le mot de passe</h2>
            <div>
              <Label htmlFor="email" className="text-purple-300">Email</Label>
              <Input
                id="email"
                name="email"
                type="email"
                placeholder="exemple@mail.com"
                value={form.email || email}
                onChange={e => setForm({ ...form, email: e.target.value })}
                required
                className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                disabled={isSubmitting || authTimedOut}
              />
            </div>
            <div>
              <Label htmlFor="password" className="text-purple-300">Nouveau mot de passe</Label>
              <Input
                id="password"
                name="password"
                type="password"
                placeholder="Nouveau mot de passe"
                value={form.password}
                onChange={e => setForm({ ...form, password: e.target.value })}
                required
                className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                disabled={isSubmitting || authTimedOut}
              />
            </div>
            <Button 
              type="submit" 
              className="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white py-3 text-lg font-semibold" 
              disabled={isSubmitting || loadingPage || authTimedOut}
            >
              {isSubmitting ? <Loader2 size={20} className="mr-2 animate-spin" /> : 'Réinitialiser'}
            </Button>
            <div className="mt-4 text-center">
              <button type="button" className="text-sm text-purple-300 hover:text-purple-100" onClick={() => setShowResetPassword(false)}>
                Retour à la connexion
              </button>
            </div>
            {message && <div className="text-center text-purple-200">{message}</div>}
          </form>
        ) : !profil ? (
          <>
            <div className="text-center mb-8">
              <Smartphone size={48} className="mx-auto text-purple-400 mb-4" />
              <h1 className="text-3xl font-bold text-white">MOMO TECH SERVICE</h1>
              <p className="text-purple-300 mt-1">
                {isLogin 
                  ? 'Connectez-vous à votre compte' 
                  : isFirstSignupAttempt 
                    ? 'Créez le compte Patron initial' 
                    : 'Créez un nouveau compte employé'}
              </p>
            </div>

            {authTimedOut && (
              <div className="mb-6 p-4 bg-red-500/30 border border-red-700 rounded-md text-center">
                <AlertTriangle className="mx-auto h-8 w-8 text-red-400 mb-2" />
                <p className="text-red-200 font-semibold">L&apos;authentification a expiré.</p>
                <p className="text-red-300 text-sm">Veuillez rafraîchir la page et réessayer.</p>
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-6">
              {!isLogin && (
                <>
                  <div>
                    <Label htmlFor="nom" className="text-purple-300">Nom</Label>
                    <Input
                      id="nom"
                      type="text"
                      placeholder="Nom"
                      value={form.nom}
                      name="nom"
                      onChange={handleChange}
                      required
                      className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                      disabled={isSubmitting || authTimedOut}
                    />
                  </div>
                  <div>
                    <Label htmlFor="prenom" className="text-purple-300">Prénoms</Label>
                    <Input
                      id="prenom"
                      type="text"
                      placeholder="Prénoms"
                      value={form.prenom}
                      name="prenom"
                      onChange={handleChange}
                      required
                      className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                      disabled={isSubmitting || authTimedOut}
                    />
                  </div>
                </>
              )}
              <div>
                <Label htmlFor={isLogin ? "login-email" : "signup-email"} className="text-purple-300">Email</Label>
                <Input
                  id={isLogin ? "login-email" : "signup-email"}
                  type="email"
                  placeholder="exemple@mail.com"
                  value={isLogin ? email : form.email}
                  name="email"
                  onChange={isLogin ? (e) => setEmail(e.target.value) : handleChange}
                  required
                  className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                  disabled={isSubmitting || authTimedOut}
                />
              </div>
              <div>
                <Label htmlFor={isLogin ? "login-password" : "signup-password"} className="text-purple-300">Mot de passe</Label>
                <Input
                  id={isLogin ? "login-password" : "signup-password"}
                  type="password"
                  placeholder="********"
                  value={isLogin ? password : form.password}
                  name="password"
                  onChange={isLogin ? (e) => setPassword(e.target.value) : handleChange}
                  required
                  className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                  disabled={isSubmitting || authTimedOut}
                />
              </div>
              {!isLogin && (
                <>
                  <div>
                    <Label htmlFor="confirmPassword" className="text-purple-300">Confirmer le mot de passe</Label>
                    <Input
                      id="confirmPassword"
                      type="password"
                      placeholder="********"
                      value={confirmPassword}
                      onChange={(e) => setConfirmPassword(e.target.value)}
                      required
                      className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                      disabled={isSubmitting || authTimedOut}
                    />
                  </div>
                  <div>
                    <Label htmlFor="role" className="text-purple-300">Rôle</Label>
                    <select
                      id="role"
                      name="role"
                      value={form.role || ''}
                      onChange={handleChange}
                      required
                      className="w-full bg-white/20 border border-purple-500 text-white rounded px-3 py-2 focus:ring-purple-400"
                      disabled={isSubmitting || authTimedOut}
                    >
                      <option value="">Sélectionner un rôle</option>
                      <option value="patron">Patron</option>
                      <option value="employé">Employé</option>
                    </select>
                  </div>
                </>
              )}
              <Button 
                type="submit" 
                className="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white py-3 text-lg font-semibold" 
                disabled={isSubmitting || loadingPage || authTimedOut}
              >
                {isSubmitting || (loadingPage && !isSubmitting && !authTimedOut) ? <Loader2 size={20} className="mr-2 animate-spin" /> : (isLogin ? <><LogIn size={20} className="mr-2" /> Se connecter</> : <><UserPlus size={20} className="mr-2" /> S&apos;inscrire</>)}
              </Button>
            </form>

            <div className="mt-6 text-center">
              <button
                onClick={() => {
                  if (!isSubmitting && !authTimedOut) {
                    setIsLogin(!isLogin);
                    if (!isLogin === false) { 
                        checkFirstUser();
                    }
                  }
                }}
                className="text-sm text-purple-300 hover:text-purple-100 transition-colors"
                disabled={isSubmitting || loadingPage || authTimedOut}
              >
                {isLogin ? "Pas encore de compte ? S'inscrire" : "Déjà un compte ? Se connecter"}
              </button>
            </div>
            {isLogin && (
              <div className="mt-2 text-center">
                <button
                  type="button"
                  className="text-xs text-purple-400 hover:text-purple-200 underline"
                  onClick={() => {
                    setShowResetPassword(true);
                    setForm({ ...form, email, password: '' });
                  }}
                  disabled={isSubmitting || loadingPage || authTimedOut}
                >
                  Mot de passe oublié ?
                </button>
              </div>
            )}
          </>
        ) : (
          <form onSubmit={handleProfileSubmit} className="mt-8 space-y-6">
            <h2 className="text-xl font-semibold text-white">Mon profil</h2>
            <div>
              <Label htmlFor="nom" className="text-purple-300">Nom</Label>
              <Input
                id="nom"
                name="nom"
                placeholder="Nom"
                value={form.nom}
                onChange={handleChange}
                required
                className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                disabled={isSubmitting || authTimedOut}
              />
            </div>
            <div>
              <Label htmlFor="prenom" className="text-purple-300">Prénom</Label>
              <Input
                id="prenom"
                name="prenom"
                placeholder="Prénom"
                value={form.prenom}
                onChange={handleChange}
                required
                className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                disabled={isSubmitting || authTimedOut}
              />
            </div>
            <div>
              <Label htmlFor="email" className="text-purple-300">Email</Label>
              <Input
                id="email"
                name="email"
                type="email"
                placeholder="exemple@mail.com"
                value={form.email}
                onChange={handleChange}
                required
                className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                disabled={isSubmitting || authTimedOut}
              />
            </div>
            <div>
              <Label htmlFor="password" className="text-purple-300">Nouveau mot de passe</Label>
              <Input
                id="password"
                name="password"
                type="password"
                placeholder="Laissez vide pour ne pas changer"
                value={form.password}
                onChange={handleChange}
                className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                disabled={isSubmitting || authTimedOut}
              />
            </div>
            <Button 
              type="submit" 
              className="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white py-3 text-lg font-semibold" 
              disabled={isSubmitting || loadingPage || authTimedOut}
            >
              {isSubmitting ? <Loader2 size={20} className="mr-2 animate-spin" /> : 'Enregistrer'}
            </Button>
            {message && <div className="text-center text-purple-200">{message}</div>}
          </form>
        )}
      </motion.div>
    </div>
  );
};

AuthPage.propTypes = {
  onLogin: PropTypes.func.isRequired,
  onSignup: PropTypes.func.isRequired,
  authTimedOut: PropTypes.bool,
};

export default AuthPage;