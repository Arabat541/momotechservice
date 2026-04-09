import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import { motion } from 'framer-motion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from '@/components/ui/use-toast';
import { LogIn, Smartphone, Loader2, AlertTriangle } from 'lucide-react';
import { resetPasswordRequest, confirmResetPassword } from '../lib/api';
import { useLocation } from 'react-router-dom';

const AuthPage = ({ onLogin, authTimedOut }) => {
  const location = useLocation();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [showResetPassword, setShowResetPassword] = useState(false);
  const [resetStep, setResetStep] = useState(1);
  const [resetEmail, setResetEmail] = useState('');
  const [resetForm, setResetForm] = useState({ email: '', code: '', password: '' });
  const [message, setMessage] = useState('');

  useEffect(() => {
    if (authTimedOut) {
      setIsSubmitting(false);
    }
  }, [authTimedOut]);

  useEffect(() => {
    if (location.pathname === '/auth') {
      setShowResetPassword(false);
      setMessage('');
      setPassword('');
      setEmail('');
    }
  }, [location.pathname]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (authTimedOut) {
      toast({
        variant: "destructive",
        title: "Authentification bloquée",
        description: "Le processus d'authentification a expiré. Veuillez rafraîchir la page et réessayer.",
        duration: 7000,
      });
      return;
    }
    setIsSubmitting(true);
    await onLogin(email, password);
    setIsSubmitting(false);
  };

  const handleResetSubmit = async (e) => {
    e.preventDefault();
    setIsSubmitting(true);
    try {
      if (resetStep === 1) {
        await resetPasswordRequest(resetForm.email || email);
        setResetEmail(resetForm.email || email);
        setResetStep(2);
        setMessage('Un code de réinitialisation a été envoyé (vérifiez les logs serveur).');
      } else {
        await confirmResetPassword(resetEmail, resetForm.code, resetForm.password);
        setMessage('Mot de passe réinitialisé avec succès. Vous pouvez vous connecter.');
        setShowResetPassword(false);
        setResetStep(1);
        setResetEmail('');
      }
    } catch (err) {
      setMessage(err.message || 'Erreur lors de la réinitialisation du mot de passe');
    }
    setIsSubmitting(false);
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 p-4">
      <motion.div
        initial={{ opacity: 0, y: -50 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ duration: 0.5 }}
        className="w-full max-w-md bg-white/10 backdrop-filter backdrop-blur-lg shadow-2xl rounded-xl p-8 glass-effect"
      >
        {showResetPassword ? (
          <form onSubmit={handleResetSubmit} className="space-y-6">
            <h2 className="text-xl font-semibold text-white mb-4">Réinitialiser le mot de passe</h2>
            {resetStep === 1 ? (
              <div>
                <Label htmlFor="reset-email" className="text-purple-300">Email</Label>
                <Input
                  id="reset-email"
                  type="email"
                  placeholder="exemple@mail.com"
                  value={resetForm.email || email}
                  onChange={e => setResetForm({ ...resetForm, email: e.target.value })}
                  required
                  className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                  disabled={isSubmitting || authTimedOut}
                />
              </div>
            ) : (
              <>
                <div>
                  <Label htmlFor="code" className="text-purple-300">Code reçu</Label>
                  <Input
                    id="code"
                    type="text"
                    placeholder="123456"
                    value={resetForm.code}
                    onChange={e => setResetForm({ ...resetForm, code: e.target.value })}
                    required
                    maxLength={6}
                    className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                    disabled={isSubmitting || authTimedOut}
                  />
                </div>
                <div>
                  <Label htmlFor="new-password" className="text-purple-300">Nouveau mot de passe</Label>
                  <Input
                    id="new-password"
                    type="password"
                    placeholder="Minimum 8 caractères"
                    value={resetForm.password}
                    onChange={e => setResetForm({ ...resetForm, password: e.target.value })}
                    required
                    minLength={8}
                    className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                    disabled={isSubmitting || authTimedOut}
                  />
                </div>
              </>
            )}
            <Button
              type="submit"
              className="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white py-3 text-lg font-semibold"
              disabled={isSubmitting || authTimedOut}
            >
              {isSubmitting ? <Loader2 size={20} className="mr-2 animate-spin" /> : resetStep === 1 ? 'Envoyer le code' : 'Réinitialiser'}
            </Button>
            <div className="mt-4 text-center">
              <button type="button" className="text-sm text-purple-300 hover:text-purple-100" onClick={() => { setShowResetPassword(false); setResetStep(1); setResetEmail(''); setMessage(''); }}>
                Retour à la connexion
              </button>
            </div>
            {message && <div className="text-center text-purple-200">{message}</div>}
          </form>
        ) : (
          <>
            <div className="text-center mb-8">
              <Smartphone size={48} className="mx-auto text-purple-400 mb-4" />
              <h1 className="text-3xl font-bold text-white">MOMO TECH SERVICE</h1>
              <p className="text-purple-300 mt-1">Connectez-vous à votre compte</p>
            </div>

            {authTimedOut && (
              <div className="mb-6 p-4 bg-red-500/30 border border-red-700 rounded-md text-center">
                <AlertTriangle className="mx-auto h-8 w-8 text-red-400 mb-2" />
                <p className="text-red-200 font-semibold">L&apos;authentification a expiré.</p>
                <p className="text-red-300 text-sm">Veuillez rafraîchir la page et réessayer.</p>
              </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-6">
              <div>
                <Label htmlFor="login-email" className="text-purple-300">Email</Label>
                <Input
                  id="login-email"
                  type="email"
                  placeholder="exemple@mail.com"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  required
                  className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                  disabled={isSubmitting || authTimedOut}
                />
              </div>
              <div>
                <Label htmlFor="login-password" className="text-purple-300">Mot de passe</Label>
                <Input
                  id="login-password"
                  type="password"
                  placeholder="********"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  required
                  className="bg-white/20 border-purple-500 text-white placeholder-purple-400 focus:ring-purple-400"
                  disabled={isSubmitting || authTimedOut}
                />
              </div>
              <Button
                type="submit"
                className="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700 text-white py-3 text-lg font-semibold"
                disabled={isSubmitting || authTimedOut}
              >
                {isSubmitting ? <Loader2 size={20} className="mr-2 animate-spin" /> : <><LogIn size={20} className="mr-2" /> Se connecter</>}
              </Button>
            </form>

            <div className="mt-4 text-center">
              <button
                type="button"
                className="text-xs text-purple-400 hover:text-purple-200 underline"
                onClick={() => {
                  setShowResetPassword(true);
                  setResetForm({ ...resetForm, email });
                }}
                disabled={isSubmitting || authTimedOut}
              >
                Mot de passe oublié ?
              </button>
            </div>
          </>
        )}
      </motion.div>
    </div>
  );
};

AuthPage.propTypes = {
  onLogin: PropTypes.func.isRequired,
  authTimedOut: PropTypes.bool,
};

export default AuthPage;