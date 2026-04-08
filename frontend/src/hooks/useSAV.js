import { useState, useEffect, useCallback } from 'react';
import { toast } from '@/components/ui/use-toast';
import { fetchSAVs, createSAV as apiCreateSAV, updateSAV as apiUpdateSAV, deleteSAV as apiDeleteSAV } from '@/lib/api';

export function useSAV() {
  const [savList, setSavList] = useState([]);
  const [loadingSAV, setLoadingSAV] = useState(true);

  const fetchAll = useCallback(async () => {
    setLoadingSAV(true);
    try {
      const token = localStorage.getItem('token');
      if (!token || token === 'null' || token === 'undefined') {
        setLoadingSAV(false);
        setSavList([]);
        return;
      }
      const data = await fetchSAVs(token);
      setSavList(data);
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible de charger les SAV." });
      setSavList([]);
    }
    setLoadingSAV(false);
  }, []);

  useEffect(() => {
    fetchAll();
  }, [fetchAll]);

  const addSAV = async (savData) => {
    setLoadingSAV(true);
    try {
      const token = localStorage.getItem('token');
      const newSAV = await apiCreateSAV(token, savData);
      setSavList(prev => [newSAV, ...prev]);
      toast({ title: "Succès", description: "Demande SAV créée." });
      return newSAV;
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible de créer le SAV." });
      return null;
    } finally {
      setLoadingSAV(false);
    }
  };

  const editSAV = async (id, updates) => {
    setLoadingSAV(true);
    try {
      const token = localStorage.getItem('token');
      const updated = await apiUpdateSAV(token, id, updates);
      setSavList(prev => prev.map(s => (s._id === updated._id ? updated : s)));
      toast({ title: "Succès", description: "SAV mis à jour." });
      return updated;
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible de mettre à jour le SAV." });
      return null;
    } finally {
      setLoadingSAV(false);
    }
  };

  const removeSAV = async (id) => {
    setLoadingSAV(true);
    try {
      const token = localStorage.getItem('token');
      await apiDeleteSAV(token, id);
      setSavList(prev => prev.filter(s => s._id !== id));
      toast({ title: "Succès", description: "SAV supprimé." });
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible de supprimer le SAV." });
    } finally {
      setLoadingSAV(false);
    }
  };

  return {
    savList,
    loadingSAV,
    addSAV,
    editSAV,
    removeSAV,
    refreshSAV: fetchAll,
  };
}
