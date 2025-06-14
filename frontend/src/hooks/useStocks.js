import { useState, useEffect, useCallback } from 'react';
import { toast } from '@/components/ui/use-toast';
import { fetchStocks as apiFetchStocks, createStock, updateStock, deleteStock as apiDeleteStock } from '@/lib/api';

export function useStocks() {
  const [stocks, setStocks] = useState([]);
  const [loadingStocks, setLoadingStocks] = useState(true);

  const fetchStocks = useCallback(async () => {
    setLoadingStocks(true);
    try {
      const token = localStorage.getItem('token');
      if (!token || token === 'null' || token === 'undefined') {
        setLoadingStocks(false);
        setStocks([]);
        toast({ variant: "destructive", title: "Non authentifié", description: "Veuillez vous reconnecter." });
        return;
      }
      const data = await apiFetchStocks(token);
      setStocks(data);
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible de charger les stocks." });
      setStocks([]);
    }
    setLoadingStocks(false);
  }, []);

  useEffect(() => {
    fetchStocks();
  }, [fetchStocks]);
  
  const addStockItem = async (item) => {
    setLoadingStocks(true);
    try {
      const token = localStorage.getItem('token');
      const newStock = await createStock(token, item);
      setStocks(prev => [newStock, ...prev]);
      toast({ title: "Succès", description: "Article ajouté au stock." });
      return newStock;
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible d'ajouter l'article." });
      return null;
    } finally {
      setLoadingStocks(false);
    }
  };

  const editStockItem = async (item) => {
    setLoadingStocks(true);
    try {
      const token = localStorage.getItem('token');
      const updated = await updateStock(token, item._id || item.id, item);
      setStocks(prev => prev.map(s => (s._id === updated._id ? updated : s)));
      toast({ title: "Succès", description: "Article modifié." });
      return updated;
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible de modifier l'article." });
      return null;
    } finally {
      setLoadingStocks(false);
    }
  };

  const deleteStockItem = async (itemId) => {
    setLoadingStocks(true);
    try {
      const token = localStorage.getItem('token');
      await apiDeleteStock(token, itemId);
      setStocks(prev => prev.filter(s => (s._id || s.id) !== itemId));
      toast({ title: "Succès", description: "Article supprimé du stock." });
    } catch (e) {
      toast({ variant: "destructive", title: "Erreur", description: "Impossible de supprimer l'article." });
    }
    setLoadingStocks(false);
  };

  const updateStockQuantities = async (piecesUtilisees, operation = 'decrease') => {
    if (!piecesUtilisees || piecesUtilisees.length === 0) return;
    setLoadingStocks(true);
    const token = localStorage.getItem('token');
    const updates = piecesUtilisees.map(async (piece) => {
      const stockItem = stocks.find(s => (s._id || s.id) === piece.stockId);
      if (!stockItem) {
        console.warn(`Stock item with id ${piece.stockId} not found for update.`);
        return null;
      }
      let newQuantity;
      if (operation === 'decrease') {
        newQuantity = stockItem.quantite - piece.quantiteUtilisee;
      } else {
        newQuantity = stockItem.quantite + piece.quantiteUtilisee;
      }
      if (newQuantity < 0) newQuantity = 0;
      try {
        const updated = await updateStock(token, piece.stockId, { quantite: newQuantity });
        return { id: piece.stockId, quantite: updated.quantite };
      } catch (e) {
        toast({ variant: "destructive", title: "Erreur Stock", description: `Échec de la mise à jour du stock pour ${stockItem.nom}.` });
        return null;
      }
    });
    const results = (await Promise.all(updates)).filter(Boolean);
    if (results.length > 0) {
      setStocks(prevStocks => {
        return prevStocks.map(s => {
          const updatedItem = results.find(ui => ui.id === (s._id || s.id));
          return updatedItem ? { ...s, quantite: updatedItem.quantite } : s;
        });
      });
    }
    setLoadingStocks(false);
  };


  return { stocks, setStocks, loadingStocks, addStockItem, editStockItem, deleteStockItem, updateStockQuantities, fetchStocks };
}
