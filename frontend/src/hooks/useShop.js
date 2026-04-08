import { useState, useEffect, useCallback } from 'react';
import { toast } from '@/components/ui/use-toast';
import { getMyShops, createShop as apiCreateShop, updateShop as apiUpdateShop, deleteShopById, addUserToShop as apiAddUser, removeUserFromShop as apiRemoveUser } from '@/lib/api';

export function useShop() {
  const [shops, setShops] = useState([]);
  const [currentShop, setCurrentShop] = useState(null);
  const [loadingShops, setLoadingShops] = useState(true);

  const fetchShops = useCallback(async () => {
    setLoadingShops(true);
    try {
      const token = localStorage.getItem('token');
      if (!token || token === 'null' || token === 'undefined') {
        setLoadingShops(false);
        setShops([]);
        return [];
      }
      const data = await getMyShops(token);
      setShops(data);

      // Restore selected shop from localStorage or auto-select first
      const savedShopId = localStorage.getItem('currentShopId');
      const savedShop = data.find(s => (s._id || s.id) === savedShopId);
      if (savedShop) {
        setCurrentShop(savedShop);
      } else if (data.length > 0) {
        setCurrentShop(data[0]);
        localStorage.setItem('currentShopId', data[0]._id || data[0].id);
      } else {
        setCurrentShop(null);
        localStorage.removeItem('currentShopId');
      }
      return data;
    } catch (e) {
      setShops([]);
      return [];
    } finally {
      setLoadingShops(false);
    }
  }, []);

  useEffect(() => {
    fetchShops();
  }, [fetchShops]);

  const selectShop = useCallback((shop) => {
    setCurrentShop(shop);
    localStorage.setItem('currentShopId', shop._id || shop.id);
  }, []);

  const createShop = async (shopData) => {
    try {
      const token = localStorage.getItem('token');
      const newShop = await apiCreateShop(token, shopData);
      setShops(prev => [...prev, newShop]);
      // Auto-select if first shop
      if (!currentShop) {
        selectShop(newShop);
      }
      toast({ title: 'Succès', description: `Boutique "${newShop.nom}" créée.` });
      return newShop;
    } catch (e) {
      toast({ variant: 'destructive', title: 'Erreur', description: 'Impossible de créer la boutique.' });
      return null;
    }
  };

  const editShop = async (shopId, updates) => {
    try {
      const token = localStorage.getItem('token');
      const updated = await apiUpdateShop(token, shopId, updates);
      setShops(prev => prev.map(s => ((s._id || s.id) === shopId ? updated : s)));
      if (currentShop && (currentShop._id || currentShop.id) === shopId) {
        setCurrentShop(updated);
      }
      toast({ title: 'Succès', description: 'Boutique modifiée.' });
      return updated;
    } catch (e) {
      toast({ variant: 'destructive', title: 'Erreur', description: 'Impossible de modifier la boutique.' });
      return null;
    }
  };

  const deleteShop = async (shopId) => {
    try {
      const token = localStorage.getItem('token');
      await deleteShopById(token, shopId);
      setShops(prev => {
        const updated = prev.filter(s => (s._id || s.id) !== shopId);
        // If we deleted the current shop, switch to another
        if (currentShop && (currentShop._id || currentShop.id) === shopId) {
          if (updated.length > 0) {
            selectShop(updated[0]);
          } else {
            setCurrentShop(null);
            localStorage.removeItem('currentShopId');
          }
        }
        return updated;
      });
      toast({ title: 'Succès', description: 'Boutique supprimée.' });
      return true;
    } catch (e) {
      toast({ variant: 'destructive', title: 'Erreur', description: 'Impossible de supprimer la boutique.' });
      return false;
    }
  };

  const addUserToShop = async (shopId, userId) => {
    try {
      const token = localStorage.getItem('token');
      await apiAddUser(token, shopId, userId);
      toast({ title: 'Succès', description: 'Utilisateur ajouté à la boutique.' });
      return true;
    } catch (e) {
      toast({ variant: 'destructive', title: 'Erreur', description: "Impossible d'ajouter l'utilisateur." });
      return false;
    }
  };

  const removeUserFromShop = async (shopId, userId) => {
    try {
      const token = localStorage.getItem('token');
      await apiRemoveUser(token, shopId, userId);
      toast({ title: 'Succès', description: 'Utilisateur retiré de la boutique.' });
      return true;
    } catch (e) {
      toast({ variant: 'destructive', title: 'Erreur', description: "Impossible de retirer l'utilisateur." });
      return false;
    }
  };

  return {
    shops,
    currentShop,
    loadingShops,
    fetchShops,
    selectShop,
    createShop,
    editShop,
    deleteShop,
    addUserToShop,
    removeUserFromShop,
  };
}
