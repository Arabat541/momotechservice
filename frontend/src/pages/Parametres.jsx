import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import { motion } from 'framer-motion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from '@/components/ui/use-toast';
import { Trash2, ShieldCheck, User as UserIcon, Save, FileText, Loader2 } from 'lucide-react';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog";
import { exportUsersCSV, exportUsersPDF, getSettings, updateSettings } from '../lib/api';


const Parametres = ({ currentUser, users = [], onDeleteUser, fetchAllUsers }) => {
  const [companyInfo, setCompanyInfo] = useState({
    nomEntreprise: "",
    adresse: "",
    telephone: "",
    slogan: "",
  });
  const [garantieInfo, setGarantieInfo] = useState({
    duree: "",
    message: "",
  });
  const [isSubmittingUser, setIsSubmittingUser] = useState(false);
  const [isLoadingUsers, setIsLoadingUsers] = useState(false);


  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        const settings = await getSettings(token);
        if (settings && settings.companyInfo) {
          setCompanyInfo({
            nomEntreprise: settings.companyInfo.nom || '',
            adresse: settings.companyInfo.adresse || '',
            telephone: settings.companyInfo.telephone || '',
            slogan: settings.companyInfo.slogan || '',
          });
        }
        if (settings && settings.warranty) {
          setGarantieInfo({
            duree: settings.warranty.duree || '',
            message: settings.warranty.conditions || '',
          });
        }
      } catch (e) {
        // fallback localStorage si erreur
        const savedCompanyInfo = localStorage.getItem('companyInfo');
        if (savedCompanyInfo) setCompanyInfo(JSON.parse(savedCompanyInfo));
        const savedGarantieInfo = localStorage.getItem('garantieInfo');
        if (savedGarantieInfo) setGarantieInfo(JSON.parse(savedGarantieInfo));
      }
    };
    fetchSettings();
  }, []);

  useEffect(() => {
    if (currentUser && currentUser.role === 'patron' && fetchAllUsers && users.length === 0) {
        setIsLoadingUsers(true);
        fetchAllUsers().finally(() => setIsLoadingUsers(false));
    }
  }, [currentUser, users.length]);


  const handleCompanyInfoChange = (e) => {
    const { name, value } = e.target;
    setCompanyInfo(prev => ({ ...prev, [name]: value }));
  };

  const handleGarantieInfoChange = (e) => {
    const { name, value } = e.target;
    setGarantieInfo(prev => ({ ...prev, [name]: value }));
  };

  const saveCompanyInfo = async () => {
    try {
      const token = localStorage.getItem('token');
      await updateSettings(token, {
        companyInfo: {
          nom: companyInfo.nomEntreprise,
          adresse: companyInfo.adresse,
          telephone: companyInfo.telephone,
          slogan: companyInfo.slogan,
        },
        warranty: {
          duree: garantieInfo.duree,
          conditions: garantieInfo.message,
        },
      });
      toast({ title: "Succès", description: "Informations de l'entreprise enregistrées sur le serveur." });
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible d'enregistrer sur le serveur." });
    }
  };

  const saveGarantieInfo = async () => {
    try {
      const token = localStorage.getItem('token');
      await updateSettings(token, {
        companyInfo: {
          nom: companyInfo.nomEntreprise,
          adresse: companyInfo.adresse,
          telephone: companyInfo.telephone,
          slogan: companyInfo.slogan,
        },
        warranty: {
          duree: garantieInfo.duree,
          conditions: garantieInfo.message,
        },
      });
      toast({ title: "Succès", description: "Paramètres de garantie enregistrés sur le serveur." });
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible d'enregistrer sur le serveur." });
    }
  };

  const handleDeleteUserWrapper = async (userId) => {
    setIsSubmittingUser(true);
    if (typeof onDeleteUser === 'function') {
        await onDeleteUser(userId);
    } else {
        toast({ variant: "destructive", title: "Erreur Système", description: "La fonction de suppression d'utilisateur n'est pas disponible."})
    }
    setIsSubmittingUser(false);
  };

  const token = localStorage.getItem('token');

  const handleExportCSV = async () => {
    try {
      const blob = await exportUsersCSV(token);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'utilisateurs.csv';
      a.click();
      window.URL.revokeObjectURL(url);
    } catch (e) {
      alert('Erreur export CSV');
    }
  };

  const handleExportPDF = async () => {
    try {
      const blob = await exportUsersPDF(token);
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'utilisateurs.pdf';
      a.click();
      window.URL.revokeObjectURL(url);
    } catch (e) {
      alert('Erreur export PDF');
    }
  };

  const isPatron = currentUser && currentUser.role === 'patron';

  return (
    <div className="space-y-8 pb-12">
      <h3 className="text-2xl font-bold text-gradient">Paramètres de l&apos;application</h3>
      
      <div className="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ type: 'spring', stiffness: 100 }}
          className="bg-white rounded-xl shadow-xl p-6 border border-slate-200"
        >
          <h4 className="text-xl font-semibold mb-4 text-slate-700 flex items-center">
            <FileText size={22} className="mr-2 text-blue-600" />
            Informations de l&apos;entreprise
          </h4>
          <div className="space-y-4">
            <div>
              <Label htmlFor="nomEntreprise" className="block text-sm font-medium text-slate-600 mb-1">Nom de l&apos;entreprise</Label>
              <Input
                id="nomEntreprise"
                name="nomEntreprise"
                type="text"
                value={companyInfo.nomEntreprise}
                onChange={handleCompanyInfoChange}
                className="w-full px-4 py-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
             <div>
              <Label htmlFor="slogan" className="block text-sm font-medium text-slate-600 mb-1">Slogan</Label>
              <Input
                id="slogan"
                name="slogan"
                type="text"
                value={companyInfo.slogan}
                onChange={handleCompanyInfoChange}
                className="w-full px-4 py-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <Label htmlFor="adresse" className="block text-sm font-medium text-slate-600 mb-1">Adresse</Label>
              <Input
                id="adresse"
                name="adresse"
                type="text"
                value={companyInfo.adresse}
                onChange={handleCompanyInfoChange}
                className="w-full px-4 py-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <Label htmlFor="telephone" className="block text-sm font-medium text-slate-600 mb-1">Téléphone</Label>
              <Input
                id="telephone"
                name="telephone"
                type="text"
                value={companyInfo.telephone}
                onChange={handleCompanyInfoChange}
                className="w-full px-4 py-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
          </div>
          <Button onClick={saveCompanyInfo} className="mt-6 w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white">
            <Save size={18} className="mr-2" /> Enregistrer Infos Entreprise
          </Button>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0, transition: { delay: 0.1, type: 'spring', stiffness: 100 } }}
          className="bg-white rounded-xl shadow-xl p-6 border border-slate-200"
        >
          <h4 className="text-xl font-semibold mb-4 text-slate-700 flex items-center">
            <ShieldCheck size={22} className="mr-2 text-green-600" />
            Paramètres de garantie
          </h4>
          <div className="space-y-4">
            <div>
              <Label htmlFor="duree" className="block text-sm font-medium text-slate-600 mb-1">Durée de garantie (jours)</Label>
              <Input
                id="duree"
                name="duree"
                type="number"
                value={garantieInfo.duree}
                onChange={handleGarantieInfoChange}
                className="w-full px-4 py-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              />
            </div>
            <div>
              <Label htmlFor="message" className="block text-sm font-medium text-slate-600 mb-1">Message de garantie</Label>
              <textarea
                id="message"
                name="message"
                value={garantieInfo.message}
                onChange={handleGarantieInfoChange}
                rows="3"
                className="w-full px-4 py-2 border-slate-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"
              />
            </div>
          </div>
          <Button onClick={saveGarantieInfo} className="mt-6 w-full bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 text-white">
            <Save size={18} className="mr-2" /> Enregistrer Paramètres Garantie
          </Button>
        </motion.div>
      </div>

      {isPatron && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0, transition: { delay: 0.2, type: 'spring', stiffness: 100 } }}
          className="bg-white rounded-xl shadow-xl p-6 border border-slate-200"
        >
          <h4 className="text-xl font-semibold mb-4 text-slate-700 flex items-center">
            <UserIcon size={22} className="mr-2 text-indigo-600" />
            Gestion des utilisateurs
          </h4>
          <h5 className="text-md font-medium text-slate-600 mb-3">Liste des utilisateurs</h5>
          {isLoadingUsers && (
            <div className="text-center p-4">
                <Loader2 className="h-6 w-6 animate-spin text-indigo-600 mx-auto" />
                <p className="text-sm text-slate-500">Chargement des utilisateurs...</p>
            </div>
          )}
          {!isLoadingUsers && users && users.length === 0 && (
            <p className="text-center text-slate-500 p-4">Aucun utilisateur trouvé.</p>
          )}
          {!isLoadingUsers && users && users.length > 0 && (
            <div className="space-y-3">
              {users.map(user => (
                <div key={user.id || user._id} className="flex items-center justify-between p-3 bg-slate-50 rounded-lg border border-slate-200 shadow-sm hover:shadow-md transition-shadow">
                  <div className="flex items-center">
                    {user.role === 'patron' ? <ShieldCheck size={24} className="mr-3 text-amber-500" /> : <UserIcon size={24} className="mr-3 text-blue-500" />}
                    <div>
                      <p className="font-medium text-slate-800">{user.email}</p>
                      <p className="text-xs text-slate-500 capitalize bg-slate-200 px-2 py-0.5 rounded-full inline-block">{user.role}</p>
                    </div>
                  </div>
                  {currentUser && currentUser.id !== user.id && (
                    <AlertDialog>
                      <AlertDialogTrigger asChild>
                        <Button variant="ghost" size="sm" className="text-red-500 hover:bg-red-100 hover:text-red-600" disabled={isSubmittingUser}>
                          <Trash2 size={16} className="mr-1" /> Supprimer
                        </Button>
                      </AlertDialogTrigger>
                      <AlertDialogContent>
                        <AlertDialogHeader>
                          <AlertDialogTitle>Êtes-vous sûr?</AlertDialogTitle>
                          <AlertDialogDescription>
                            Cette action est irréversible et supprimera le compte de {user.email}.
                          </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                          <AlertDialogCancel>Annuler</AlertDialogCancel>
                          <AlertDialogAction 
                            onClick={() => handleDeleteUserWrapper(user.id || user._id)} 
                            className="bg-red-600 hover:bg-red-700 text-white"
                            disabled={isSubmittingUser}
                          >
                           {isSubmittingUser ? <Loader2 size={16} className="animate-spin" /> : "Supprimer"}
                          </AlertDialogAction>
                        </AlertDialogFooter>
                      </AlertDialogContent>
                    </AlertDialog>
                  )}
                </div>
              ))}
            </div>
          )}
        </motion.div>
      )}

      <div className="mt-8 p-4 bg-slate-50 rounded-lg border border-slate-200">
        <h4 className="text-lg font-semibold mb-4 text-slate-700 flex items-center">
          <Save size={22} className="mr-2 text-green-600" />
          Exporter les utilisateurs
        </h4>
        <Button 
          onClick={handleExportCSV} 
          className="bg-gradient-to-r from-green-400 to-teal-400 hover:from-green-500 hover:to-teal-500 text-white mr-2"
        >
          Exporter en CSV
        </Button>
        <Button 
          onClick={handleExportPDF} 
          className="bg-gradient-to-r from-red-400 to-pink-400 hover:from-red-500 hover:to-pink-500 text-white"
        >
          Exporter en PDF
        </Button>
        <p className="text-xs text-slate-500 mt-3">Utilisez les boutons ci-dessus pour exporter la liste des utilisateurs au format CSV ou PDF.</p>
      </div>
    </div>
  );
};

Parametres.propTypes = {
  currentUser: PropTypes.shape({
    id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    email: PropTypes.string,
    role: PropTypes.string,
  }),
  users: PropTypes.arrayOf(PropTypes.shape({
    id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    _id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    email: PropTypes.string,
    role: PropTypes.string,
  })),
  onDeleteUser: PropTypes.func,
  fetchAllUsers: PropTypes.func,
};

export default Parametres;