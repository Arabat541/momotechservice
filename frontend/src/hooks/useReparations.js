import { useState, useEffect, useCallback } from 'react';
import { toast } from '@/components/ui/use-toast';
import { fetchRepairs, createRepair, updateRepair as apiUpdateRepair, deleteRepair as apiDeleteRepair } from '@/lib/api';

export function useReparations(stocks, updateStockQuantities) {
  const [reparations, setReparations] = useState([]);
  const [loadingReparations, setLoadingReparations] = useState(true);

  const fetchReparations = useCallback(async () => {
    setLoadingReparations(true);
    try {
      const token = localStorage.getItem('token');
      if (!token || token === 'null' || token === 'undefined') {
        setLoadingReparations(false);
        setReparations([]);
        toast({ variant: "destructive", title: "Non authentifié", description: "Veuillez vous reconnecter." });
        return;
      }
      const data = await fetchRepairs(token);
      setReparations(data);
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible de charger les réparations." });
      setReparations([]);
    }
    setLoadingReparations(false);
  }, []);

  useEffect(() => {
    fetchReparations();
  }, [fetchReparations]);

  const generateNumeroReparation = (type) => {
    const prefix = type === 'place' ? 'RP' : '2AR';
    const timestamp = Date.now().toString().slice(-6);
    const random = Math.floor(Math.random() * 1000).toString().padStart(3, '0');
    return `${prefix}${timestamp}${random}`;
  };

  const handleSaveNewRepair = async (repairData, type) => {
    setLoadingReparations(true);
    try {
      const token = localStorage.getItem('token');
      const newRepair = await createRepair(token, repairData);
      setReparations(prev => [newRepair, ...prev]);
      // Décrémente le stock utilisé
      if (repairData.pieces_rechange_utilisees && repairData.pieces_rechange_utilisees.length > 0) {
        await updateStockQuantities(repairData.pieces_rechange_utilisees, 'decrease');
      }
      toast({ title: "Succès", description: "Réparation enregistrée." });
      return newRepair;
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible d'enregistrer la réparation." });
      return null;
    } finally {
      setLoadingReparations(false);
    }
  };

  const handleSaveFromStandalonePreview = async (repairData, originalTypeString) => {
    setLoadingReparations(true);
    try {
      const token = localStorage.getItem('token');
      const updated = await apiUpdateRepair(token, repairData._id || repairData.id, repairData);
      setReparations(prev => prev.map(r => (r._id === updated._id ? updated : r)));
      toast({ title: "Succès", description: "Réparation mise à jour." });
      return updated;
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible de mettre à jour la réparation." });
      return null;
    } finally {
      setLoadingReparations(false);
    }
  };

  const deleteRepair = async (repairId) => {
    setLoadingReparations(true);
    const repairToDelete = reparations.find(r => r._id === repairId || r.id === repairId);
    if (!repairToDelete) {
      toast({ variant: "destructive", title: "Erreur", description: "Réparation non trouvée." });
      setLoadingReparations(false);
      return;
    }
    try {
      const token = localStorage.getItem('token');
      await apiDeleteRepair(token, repairId);
      setReparations(prev => prev.filter(r => (r._id || r.id) !== repairId));
      toast({ title: "Succès", description: "Réparation supprimée." });
      if (repairToDelete.pieces_rechange_utilisees && repairToDelete.pieces_rechange_utilisees.length > 0) {
        await updateStockQuantities(repairToDelete.pieces_rechange_utilisees, 'increase');
      }
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible de supprimer la réparation." });
    }
    setLoadingReparations(false);
  };


  return {
    reparations,
    setReparations,
    loadingReparations,
    handleSaveNewRepair,
    handleSaveFromStandalonePreview,
    generateNumeroReparation,
    deleteRepair,
    fetchReparations,
    updateRepair: apiUpdateRepair // <-- Ajouté pour exposer la fonction d'update
  };
}
