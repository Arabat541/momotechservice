import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import { motion } from 'framer-motion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { toast } from '@/components/ui/use-toast';
import { Trash2, ShieldCheck, User as UserIcon, Save, FileText, Loader2, Store, UserPlus, UserMinus } from 'lucide-react';
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


const Parametres = ({ currentUser, users = [], onCreateUser, onDeleteUser, fetchAllUsers, shops = [], currentShop, onEditShop, onDeleteShop, onAddUserToShop, onRemoveUserFromShop }) => {
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
  const [newEmployee, setNewEmployee] = useState({ nom: '', prenom: '', email: '', password: '' });


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
          {currentShop && (
            <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
              <p className="text-sm font-medium text-blue-800 mb-1 flex items-center gap-1">
                <Store size={16} /> Lien vitrine publique
              </p>
              <div className="flex items-center gap-2">
                <code className="text-xs bg-white px-3 py-1.5 rounded border border-blue-200 flex-1 break-all select-all">
                  {window.location.origin}/shop/{currentShop._id || currentShop.id}
                </code>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() => {
                    navigator.clipboard.writeText(`${window.location.origin}/shop/${currentShop._id || currentShop.id}`);
                    toast({ title: "Copié !", description: "Lien copié dans le presse-papier." });
                  }}
                >
                  Copier
                </Button>
              </div>
              <p className="text-xs text-blue-600 mt-1">Partagez ce lien avec vos clients pour qu&apos;ils puissent voir vos infos et suivre leurs réparations.</p>
            </div>
          )}
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
                      <p className="font-medium text-slate-800">{user.nom} {user.prenom}</p>
                      <p className="text-xs text-slate-500">{user.email}</p>
                      <p className="text-xs text-slate-500 capitalize bg-slate-200 px-2 py-0.5 rounded-full inline-block">{user.role}</p>
                    </div>
                  </div>
                  {currentUser && currentUser.id !== user.id && user.role !== 'patron' && (
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

          {/* Formulaire de création d'employé */}
          <div className="mt-6 pt-6 border-t border-slate-200">
            <h5 className="text-md font-medium text-slate-600 mb-3 flex items-center">
              <UserPlus size={18} className="mr-2 text-green-600" />
              Créer un compte employé
            </h5>
            <form onSubmit={async (e) => {
              e.preventDefault();
              setIsSubmittingUser(true);
              const success = await onCreateUser(newEmployee.email, newEmployee.password, newEmployee.nom, newEmployee.prenom, 'employé');
              if (success) {
                setNewEmployee({ nom: '', prenom: '', email: '', password: '' });
                if (fetchAllUsers) await fetchAllUsers();
              }
              setIsSubmittingUser(false);
            }} className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <Label htmlFor="new-emp-nom" className="block text-sm font-medium text-slate-600 mb-1">Nom</Label>
                <Input id="new-emp-nom" type="text" placeholder="Nom" value={newEmployee.nom} onChange={e => setNewEmployee({ ...newEmployee, nom: e.target.value })} required disabled={isSubmittingUser} className="w-full" />
              </div>
              <div>
                <Label htmlFor="new-emp-prenom" className="block text-sm font-medium text-slate-600 mb-1">Prénoms</Label>
                <Input id="new-emp-prenom" type="text" placeholder="Prénoms" value={newEmployee.prenom} onChange={e => setNewEmployee({ ...newEmployee, prenom: e.target.value })} required disabled={isSubmittingUser} className="w-full" />
              </div>
              <div>
                <Label htmlFor="new-emp-email" className="block text-sm font-medium text-slate-600 mb-1">Email</Label>
                <Input id="new-emp-email" type="email" placeholder="email@example.com" value={newEmployee.email} onChange={e => setNewEmployee({ ...newEmployee, email: e.target.value })} required disabled={isSubmittingUser} className="w-full" />
              </div>
              <div>
                <Label htmlFor="new-emp-password" className="block text-sm font-medium text-slate-600 mb-1">Mot de passe</Label>
                <Input id="new-emp-password" type="password" placeholder="Min. 8 caractères" value={newEmployee.password} onChange={e => setNewEmployee({ ...newEmployee, password: e.target.value })} required minLength={8} disabled={isSubmittingUser} className="w-full" />
              </div>
              <div className="sm:col-span-2">
                <Button type="submit" className="w-full bg-gradient-to-r from-green-500 to-teal-500 hover:from-green-600 hover:to-teal-600 text-white" disabled={isSubmittingUser}>
                  {isSubmittingUser ? <Loader2 size={16} className="mr-2 animate-spin" /> : <UserPlus size={16} className="mr-2" />}
                  Créer l&apos;employé
                </Button>
              </div>
            </form>
          </div>}
        </motion.div>
      )}

      {isPatron && (
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0, transition: { delay: 0.3, type: 'spring', stiffness: 100 } }}
          className="bg-white rounded-xl shadow-xl p-6 border border-slate-200"
        >
          <h4 className="text-xl font-semibold mb-4 text-slate-700 flex items-center">
            <Store size={22} className="mr-2 text-purple-600" />
            Gestion des boutiques
          </h4>
          {shops.length === 0 && (
            <p className="text-center text-slate-500 p-4">Aucune boutique configurée.</p>
          )}
          {shops.length > 0 && (
            <div className="space-y-4">
              {shops.map(shop => {
                const shopId = shop._id || shop.id;
                const isCurrent = currentShop && (currentShop._id || currentShop.id) === shopId;
                return (
                  <div key={shopId} className={`p-4 rounded-lg border ${isCurrent ? 'border-purple-300 bg-purple-50' : 'border-slate-200 bg-slate-50'} shadow-sm`}>
                    <div className="flex items-center justify-between mb-2">
                      <div className="flex items-center gap-2">
                        <Store size={18} className="text-purple-500" />
                        <span className="font-semibold text-slate-800">{shop.nom}</span>
                        {isCurrent && <span className="text-xs bg-purple-200 text-purple-700 px-2 py-0.5 rounded-full">Active</span>}
                      </div>
                      <AlertDialog>
                        <AlertDialogTrigger asChild>
                          <Button variant="ghost" size="sm" className="text-red-500 hover:bg-red-100 hover:text-red-600">
                            <Trash2 size={14} className="mr-1" /> Supprimer
                          </Button>
                        </AlertDialogTrigger>
                        <AlertDialogContent>
                          <AlertDialogHeader>
                            <AlertDialogTitle>Supprimer la boutique ?</AlertDialogTitle>
                            <AlertDialogDescription>
                              Cette action supprimera la boutique &quot;{shop.nom}&quot; et toutes ses données associées.
                            </AlertDialogDescription>
                          </AlertDialogHeader>
                          <AlertDialogFooter>
                            <AlertDialogCancel>Annuler</AlertDialogCancel>
                            <AlertDialogAction onClick={() => onDeleteShop && onDeleteShop(shopId)} className="bg-red-600 hover:bg-red-700 text-white">
                              Supprimer
                            </AlertDialogAction>
                          </AlertDialogFooter>
                        </AlertDialogContent>
                      </AlertDialog>
                    </div>
                    {shop.adresse && <p className="text-sm text-slate-600">Adresse : {shop.adresse}</p>}
                    {shop.telephone && <p className="text-sm text-slate-600">Tél : {shop.telephone}</p>}
                    
                    {/* Assign user to this shop */}
                    <div className="mt-3 pt-3 border-t border-slate-200">
                      <p className="text-sm font-medium text-slate-600 mb-2">Membres de cette boutique :</p>
                      <div className="flex flex-wrap gap-1 mb-2">
                        {users.filter(u => u.shops && u.shops.some(s => (typeof s === 'string' ? s : (s._id || s.id)) === shopId)).map(u => (
                          <span key={u._id || u.id} className="inline-flex items-center gap-1 text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full">
                            {u.email}
                            <button onClick={() => onRemoveUserFromShop && onRemoveUserFromShop(shopId, u._id || u.id)} className="hover:text-red-500" title="Retirer">
                              <UserMinus size={12} />
                            </button>
                          </span>
                        ))}
                      </div>
                      <div className="flex flex-wrap gap-1">
                        {users.filter(u => !u.shops || !u.shops.some(s => (typeof s === 'string' ? s : (s._id || s.id)) === shopId)).map(u => (
                          <button
                            key={u._id || u.id}
                            onClick={() => onAddUserToShop && onAddUserToShop(shopId, u._id || u.id)}
                            className="inline-flex items-center gap-1 text-xs bg-gray-100 text-gray-600 px-2 py-1 rounded-full hover:bg-green-100 hover:text-green-700 transition-colors"
                            title={`Ajouter ${u.email}`}
                          >
                            <UserPlus size={12} /> {u.email}
                          </button>
                        ))}
                      </div>
                    </div>
                  </div>
                );
              })}
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
    shops: PropTypes.array,
  })),
  onCreateUser: PropTypes.func,
  onDeleteUser: PropTypes.func,
  fetchAllUsers: PropTypes.func,
  shops: PropTypes.array,
  currentShop: PropTypes.object,
  onEditShop: PropTypes.func,
  onDeleteShop: PropTypes.func,
  onAddUserToShop: PropTypes.func,
  onRemoveUserFromShop: PropTypes.func,
};

export default Parametres;