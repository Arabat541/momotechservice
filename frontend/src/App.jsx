import React, { useState, useEffect, useRef } from 'react';
import { Routes, Route, Navigate, useLocation, useNavigate } from 'react-router-dom';
import { Toaster } from '@/components/ui/toaster';
import AuthPage from '@/pages/AuthPage';
import DashboardLayout from '@/components/layout/DashboardLayout';
import ReparationsPlace from '@/pages/ReparationsPlace';
import ReparationsRdv from '@/pages/ReparationsRdv';
import ListeReparations from '@/pages/ListeReparations';
import GestionStocks from '@/pages/GestionStocks';
import Parametres from '@/pages/Parametres';
import RecuPreview from '@/components/shared/RecuPreview';
import { useReactToPrint } from 'react-to-print';
import Barcode from 'react-barcode';
import { Loader2 } from 'lucide-react';
import { toast } from '@/components/ui/use-toast';
import PropTypes from 'prop-types';
import Article from './pages/Article';

import { useAuth } from '@/hooks/useAuth';
import { useStocks } from '@/hooks/useStocks';
import { useReparations } from '@/hooks/useReparations';

const EtiquettePreview = React.forwardRef(({ repairData }, ref) => {
  const [companyName, setCompanyName] = useState("MOMO TECH");

  useEffect(() => {
    if (!repairData) return; 
    const companyInfoRaw = localStorage.getItem('companyInfo');
    if (companyInfoRaw) {
      try {
        const companyInfo = JSON.parse(companyInfoRaw);
        if (companyInfo.nomEntreprise) {
          setCompanyName(companyInfo.nomEntreprise);
        }
      } catch (e) {
        console.error("Failed to parse companyInfo from localStorage", e);
      }
    }
  }, [repairData]);

  if (!repairData) return null;

  // Correction : toujours utiliser repairData.numeroReparation (champ unique)
  const numero = repairData.numeroReparation || repairData.numero_reparation || '';
  const client = repairData.client || repairData.client_nom || '';
  const appareil = repairData.appareil || repairData.appareil_marque_modele || '';
  const displayDate = repairData.type_reparation === 'place' 
    ? (repairData.date_creation ? new Date(repairData.date_creation).toLocaleDateString('fr-FR') : new Date().toLocaleDateString('fr-FR'))
    : (repairData.date_rendez_vous ? new Date(repairData.date_rendez_vous).toLocaleDateString('fr-FR') : 'N/A');
  const dateLabel = repairData.type_reparation === 'place' ? 'Date:' : 'RDV:';

  return (
    <div ref={ref} className="p-2 m-0 text-xs bg-white" style={{ width: '150px', border: '1px solid black'}}>
      <p className="font-bold text-center">{companyName.substring(0,15)}</p>
      <p>N°: {numero}</p>
      <p>Client: {client.substring(0, 18)}</p>
      <p>App: {appareil.substring(0, 20)}</p>
      <p>{dateLabel} {displayDate}</p>
       <div className="flex justify-center my-1">
         <Barcode value={numero || "N/A"} height={20} width={1} fontSize={8} displayValue={false} />
      </div>
    </div>
  );
});
EtiquettePreview.displayName = "EtiquettePreview";


EtiquettePreview.propTypes = {
  repairData: PropTypes.shape({
    numeroReparation: PropTypes.string,
    numero_reparation: PropTypes.string,
    client: PropTypes.string,
    client_nom: PropTypes.string,
    appareil: PropTypes.string,
    appareil_marque_modele: PropTypes.string,
    type_reparation: PropTypes.string,
    date_creation: PropTypes.oneOfType([PropTypes.string, PropTypes.instanceOf(Date)]),
    date_rendez_vous: PropTypes.oneOfType([PropTypes.string, PropTypes.instanceOf(Date)]),
    // Ajoute d'autres champs utilisés si besoin
  })
};


function App() {
  const { currentUser, users, loadingAuth, handleLogin, handleSignup, handleLogout, createUser, deleteUser, fetchAllUsers } = useAuth();
  const { stocks, loadingStocks, addStockItem, editStockItem, deleteStockItem, updateStockQuantities, fetchStocks: refreshStocks } = useStocks();
  const { 
    reparations, 
    loadingReparations,
    handleSaveNewRepair, 
    handleSaveFromStandalonePreview,
    generateNumeroReparation,
    deleteRepair,
    fetchReparations: refreshReparations,
    updateRepair // <-- à ajouter dans le hook useReparations si manquant
  } = useReparations(stocks, updateStockQuantities);

  const location = useLocation();
  const navigate = useNavigate();
  
  const [showStandalonePreviewModal, setShowStandalonePreviewModal] = useState(false);
  const [standalonePreviewData, setStandalonePreviewData] = useState(null);
  const [authTimedOut, setAuthTimedOut] = useState(false);
  const [ventes, setVentes] = useState([]); // ventes directes
  const [sortiesRechange, setSortiesRechange] = useState([]); // sorties pour réparation

  // Met à jour la liste des pièces de rechange sorties pour réparation
  useEffect(() => {
    const sorties = [];
    reparations.forEach(r => {
      if (Array.isArray(r.pieces_rechange_utilisees)) {
        r.pieces_rechange_utilisees.forEach(piece => {
          sorties.push({
            nom: piece.nom,
            quantite: piece.quantiteUtilisee,
            date: r.date_mise_en_reparation || r.date_creation || new Date(),
            numeroReparation: r.numeroReparation || r.numero_reparation || '', // Ajout du numéro de réparation
          });
        });
      }
    });
    setSortiesRechange(sorties);
  }, [reparations]);

  const componentRef = useRef();
  const handlePrint = useReactToPrint({
    content: () => componentRef.current,
    documentTitle: `Recu_${standalonePreviewData?.numero_reparation || 'reparation'}`,
    pageStyle: `
      @page {
        size: 80mm auto; /* Standard thermal receipt paper width */
        margin: 3mm;
      }
      @media print {
        body {
          -webkit-print-color-adjust: exact;
          print-color-adjust: exact;
        }
        html, body {
          width: 80mm; /* Standard thermal receipt paper width */
          height: auto;
        }
      }
    `
  });

  const etiquetteRef = useRef();
  const handlePrintEtiquette = useReactToPrint({
    content: () => etiquetteRef.current,
    documentTitle: `Etiquette_${standalonePreviewData?.numero_reparation || 'reparation'}`,
    pageStyle: `
      @page {
        size: auto;
        margin: 2mm;
      }
      @media print {
        body {
          -webkit-print-color-adjust: exact;
          print-color-adjust: exact;
        }
        html, body {
          width: 150px; /* Approx width of the etiquette */
          height: auto;
        }
      }
    `
  });
  
  // This useEffect handles redirection based on auth state
  useEffect(() => {
    console.log("App.jsx useEffect (redirection): loadingAuth:", loadingAuth, "currentUser:", !!currentUser, "pathname:", location.pathname, "authTimedOut:", authTimedOut);
    if (authTimedOut) {
        // If auth timed out, we assume no user is logged in for redirection purposes.
        // User will see the timeout message on the auth page.
        if (location.pathname !== '/auth') {
            console.log("Auth timed out, redirecting to /auth");
            navigate('/auth', { replace: true });
        }
        return; // Stop further processing if auth timed out
    }

    if (!loadingAuth) {
      if (!currentUser && location.pathname !== '/auth') {
        console.log("Redirecting to /auth (no user, not on auth page)");
        navigate('/auth', { replace: true });
      } else if (currentUser && location.pathname === '/auth') {
        console.log("Redirecting to /reparations-place (user exists, on auth page)");
        navigate('/reparations-place', { replace: true });
      }
    }
  }, [currentUser, loadingAuth, location.pathname, navigate, authTimedOut]);

  // Timeout for loadingAuth
  useEffect(() => {
    let timer;
    if (loadingAuth) {
      console.log("App.jsx: loadingAuth is true, starting timeout.");
      setAuthTimedOut(false); // Reset timeout flag
      timer = setTimeout(() => {
        if (loadingAuth) { // Check again, in case it became false in the meantime
          console.error("App.jsx: Authentication timed out after 15 seconds.");
          toast({
            variant: "destructive",
            title: "Délai d'authentification dépassé",
            description: "Le processus d'authentification a pris trop de temps. Veuillez vérifier votre connexion et réessayer.",
            duration: 10000, 
          });
          // Forcibly end loading state. This might leave currentUser in an inconsistent state
          // but will unlock the UI. The redirection logic should then send to /auth.
          // We need to find a way to signal useAuth to stop its process or set its loading to false.
          // For now, App.jsx will manage its view based on this timeout.
          setAuthTimedOut(true); 
          // Note: This doesn't directly set useAuth's loadingAuth to false.
          // The redirection logic will now use authTimedOut.
        }
      }, 15000); // 15 seconds timeout
    } else {
      console.log("App.jsx: loadingAuth is false, clearing timeout.");
      setAuthTimedOut(false); // Clear timeout flag if loading finishes normally
    }
    return () => {
      console.log("App.jsx: Clearing auth timeout timer.");
      clearTimeout(timer);
    };
  }, [loadingAuth]);


  const openStandalonePreviewModal = (repairData) => {
    // Mapping pour compatibilité avec RecuPreview
    const mapped = {
      ...repairData,
      numeroReparation: repairData.numeroReparation || repairData.numero_reparation || '',
      client: repairData.client || repairData.client_nom || '',
      telephone: repairData.telephone || repairData.client_telephone || '',
      appareil: repairData.appareil || repairData.appareil_marque_modele || '',
      pannes: Array.isArray(repairData.pannes) ? repairData.pannes : (repairData.pannes_services ? repairData.pannes_services.map(p => p.description) : []),
      montants: Array.isArray(repairData.montants) ? repairData.montants : (repairData.pannes_services ? repairData.pannes_services.map(p => p.montant) : []),
      piecesRechange: repairData.piecesReChange || repairData.pieces_rechange_utilisees || [],
      total: repairData.total || repairData.total_reparation || 0,
      paye: repairData.paye || repairData.montant_paye || 0,
      statut: repairData.statut || repairData.statut_reparation || '',
      type_reparation: repairData.type_reparation,
      date_creation: repairData.date_creation,
      date_mise_en_reparation: repairData.date_mise_en_reparation,
      dateRendezVous: repairData.dateRendezVous || repairData.date_rendez_vous,
      dateRetrait: repairData.dateRetrait || repairData.date_retrait,
    };
    setStandalonePreviewData(mapped);
    setShowStandalonePreviewModal(true);
  };

  const closeStandalonePreviewModal = () => {
    setShowStandalonePreviewModal(false);
    setStandalonePreviewData(null);
  };
  
  const onSaveNewRepair = async (repairData, type) => {
    const newRepair = await handleSaveNewRepair(repairData, type);
    if (newRepair) {
      openStandalonePreviewModal(newRepair);
      await refreshStocks();
      await refreshReparations();
    }
  };

  const onSaveFromPreview = async (repairData) => {
    const updatedRepair = await handleSaveFromStandalonePreview(repairData);
    if (updatedRepair) {
      setStandalonePreviewData(updatedRepair);
      await refreshStocks(); 
      await refreshReparations();
    }
  };

  // Handler pour la mise à jour du statut (et autres modifs)
  const updateRepairHandler = async (repair) => {
    try {
      console.log('updateRepairHandler called with:', repair);
      const token = localStorage.getItem('token');
      // On retrouve la réparation d'origine pour compléter les champs manquants
      const original = reparations.find(r => (r._id === repair._id || r.id === repair.id || r.numeroReparation === repair.numeroReparation));
      if (!original) throw new Error('Réparation introuvable');
      // Mapping strict et fusion : priorité à la modif, fallback sur l'original
      const repairToSend = {
        numeroReparation: repair.numeroReparation || repair.numero_reparation || original.numeroReparation || original.numero_reparation,
        type_reparation: repair.type_reparation || original.type_reparation,
        client_nom: repair.client_nom || original.client_nom,
        client_telephone: repair.client_telephone || original.client_telephone,
        appareil_marque_modele: repair.appareil_marque_modele || original.appareil_marque_modele,
        pannes_services: repair.pannes_services || original.pannes_services,
        pieces_rechange_utilisees: repair.pieces_rechange_utilisees || original.pieces_rechange_utilisees,
        total_reparation: Number(repair.total_reparation ?? original.total_reparation),
        montant_paye: Number(repair.montant_paye ?? original.montant_paye),
        reste_a_payer: Number(repair.reste_a_payer ?? original.reste_a_payer),
        statut_reparation: repair.statut_reparation || original.statut_reparation,
        date_creation: repair.date_creation || original.date_creation,
        date_mise_en_reparation: repair.date_mise_en_reparation || original.date_mise_en_reparation,
        date_rendez_vous: repair.hasOwnProperty('date_rendez_vous') ? repair.date_rendez_vous : original.date_rendez_vous,
        date_retrait: repair.hasOwnProperty('date_retrait') ? repair.date_retrait : original.date_retrait,
        etat_paiement: repair.etat_paiement || original.etat_paiement,
        userId: repair.userId || original.userId
      };
      console.log('Appel API updateRepair avec :', repair._id || repair.id, repairToSend);
      const updated = await updateRepair(token, repair._id || repair.id, repairToSend);
      // Met à jour le state local (supprimé car on utilise refreshReparations)
      // if (typeof setReparations === 'function') {
      //   setReparations(prev => prev.map(r => (r._id === updated._id ? updated : r)));
      // }
      toast({ title: "Succès", description: "Réparation mise à jour." });
      // Rafraîchit la liste après mise à jour
      await refreshReparations();
      return updated;
    } catch (e) {
      console.error('Erreur JS dans updateRepairHandler:', e);
      toast({ variant: "destructive", title: "Erreur", description: "Impossible de mettre à jour la réparation." });
      return null;
    }
  };

  // Handler pour la vente d'article
  const handleVenteArticle = async ({ articleId, quantite, client }) => {
    // Met à jour le stock (décrémentation)
    const stockItem = stocks.find(s => (s.id || s._id) === articleId);
    if (!stockItem || stockItem.quantite < quantite) {
      toast({ variant: 'destructive', title: 'Stock insuffisant', description: 'Quantité demandée non disponible.' });
      return;
    }
    await editStockItem({ ...stockItem, quantite: stockItem.quantite - quantite });
    const vente = {
      id: Date.now(),
      nom: stockItem.nom,
      quantite,
      client,
      date: new Date().toISOString(),
    };
    setVentes(prev => [vente, ...prev]);
    toast({ title: 'Vente enregistrée', description: `${quantite} ${stockItem.nom} vendu à ${client}` });
  };

  // Handler pour annuler une vente
  const handleAnnulerVente = async (vente, index) => {
    // Remettre la quantité dans le stock
    const stockItem = stocks.find(s => s.nom === vente.nom);
    if (stockItem) {
      await editStockItem({ ...stockItem, quantite: Number(stockItem.quantite) + Number(vente.quantite) });
    }
    setVentes(prev => prev.filter((_, i) => i !== index));
    toast({ title: 'Vente annulée', description: `${vente.quantite} ${vente.nom} remis en stock.` });
  };

  // Centralized loading screen
  // Show loading screen if loadingAuth is true AND auth has not timed out.
  if (loadingAuth && !authTimedOut) { 
    return (
      <div className="flex items-center justify-center h-screen bg-gradient-to-br from-slate-900 to-slate-800">
        <div className="text-center">
          <Loader2 className="w-16 h-16 animate-spin text-blue-500 mx-auto mb-4" />
          <p className="text-xl font-semibold text-white">Authentification en cours...</p>
          <p className="text-sm text-slate-300">Veuillez patienter pendant que nous vérifions votre session.</p>
        </div>
      </div>
    );
  }

  // Routes definition
  const AppRoutes = () => (
    <Routes>
      <Route 
        path="/auth" 
        element={
          authTimedOut || !currentUser ? (
            <AuthPage 
              onLogin={handleLogin} 
              onSignup={handleSignup} 
              authTimedOut={authTimedOut} // Pass the flag to AuthPage
            />
          ) : (
            <Navigate to="/reparations-place" replace />
          )
        } 
      />
      
      <Route 
        path="/" 
        element={
          currentUser && !authTimedOut ? ( // Ensure user is present and no timeout
            <DashboardLayout currentUser={currentUser} onLogout={handleLogout} loadingApp={loadingStocks || loadingReparations} />
          ) : (
            <Navigate to="/auth" replace /> 
          )
        }
      >
        <Route index element={<Navigate to="/reparations-place" replace />} /> 
        <Route 
          path="reparations-place" 
          element={
            <ReparationsPlace 
              onSave={(data) => onSaveNewRepair(data, 'place')}
              generateNumeroReparation={() => generateNumeroReparation('place')}
              availableStocks={stocks}
            />
          } 
        />
        <Route 
          path="reparations-rdv" 
          element={
            <ReparationsRdv 
              onSave={(data) => onSaveNewRepair(data, 'rdv')}
              generateNumeroReparation={() => generateNumeroReparation('rdv')}
              availableStocks={stocks}
            />
          } 
        />
        <Route 
          path="liste-reparations" 
          element={
            <ListeReparations 
              reparations={reparations}
              onView={openStandalonePreviewModal}
              onDeleteRepair={deleteRepair}
              loadingReparations={loadingReparations}
              onUpdateRepair={async (repair) => {
                await updateRepairHandler(repair);
              }}
            />
          } 
        />
        <Route 
          path="stocks" 
          element={
            <GestionStocks 
              stocks={stocks} 
              addStockItem={addStockItem}
              editStockItem={editStockItem}
              deleteStockItem={deleteStockItem}
              loadingStocks={loadingStocks}
            />
          } 
        />
        <Route 
          path="parametres" 
          element={
            <Parametres 
              currentUser={currentUser}
              users={users}
              onCreateUser={createUser}
              onDeleteUser={deleteUser}
              fetchAllUsers={fetchAllUsers}
            />
          } 
        />
        <Route 
          path="article" 
          element={
            <Article 
              onVente={handleVenteArticle}
              onAnnulerVente={handleAnnulerVente}
              ventes={ventes}
              sortiesRechange={sortiesRechange}
            />
          }
        />
      </Route>
      <Route path="*" element={<Navigate to={currentUser && !authTimedOut ? "/reparations-place" : "/auth"} replace />} />
    </Routes>
  );

  return (
    <>
      <AppRoutes />
      {showStandalonePreviewModal && standalonePreviewData && (
        <>
          {/* Overlay sombre animé */}
          <div
            className="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center animate-fadeIn"
            tabIndex={-1}
            aria-modal="true"
            role="dialog"
            onClick={closeStandalonePreviewModal}
            onKeyDown={e => { if (e.key === 'Escape') closeStandalonePreviewModal(); }}
            style={{ animation: 'fadeIn 0.2s' }}
          >
            {/* Boîte modale centrée, stopPropagation pour éviter fermeture sur clic intérieur */}
            <div
              className="relative bg-white rounded-xl shadow-2xl p-4 max-w-full w-[340px] animate-modalPop"
              onClick={e => e.stopPropagation()}
              tabIndex={0}
              style={{ outline: 'none' }}
            >
              {/* Bouton croix fermeture */}
              <button
                onClick={closeStandalonePreviewModal}
                aria-label="Fermer l'aperçu"
                className="absolute top-2 right-2 text-gray-500 hover:text-red-600 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full"
                tabIndex={0}
                autoFocus
              >
                <svg width="24" height="24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
              {/* Aperçu reçu */}
              <RecuPreview
                ref={componentRef}
                repairData={standalonePreviewData}
                onClose={closeStandalonePreviewModal}
                onPrintTicket={handlePrint}
                onPrintEtiquette={handlePrintEtiquette}
                onSave={onSaveFromPreview}
                isPreviewInModal={true}
              />
              {/* Actions impression (optionnel) */}
              <div className="flex flex-row gap-2 mt-4 justify-center">
                <button type="button" onClick={handlePrint} className="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded">Imprimer ticket</button>
                <button type="button" onClick={handlePrintEtiquette} className="bg-purple-600 hover:bg-purple-700 text-white text-xs px-3 py-1 rounded">Imprimer code barre</button>
              </div>
            </div>
          </div>
          {/* Etiquette cachée pour impression */}
          <div style={{ display: "none" }}>
            <EtiquettePreview ref={etiquetteRef} repairData={standalonePreviewData} />
          </div>
        </>
      )}
      <Toaster />
    </>
  );
}

export default App;