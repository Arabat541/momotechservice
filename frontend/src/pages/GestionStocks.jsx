import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { motion } from 'framer-motion';
import { Plus, Edit, Trash2, Package, CreditCard, AlertCircle, X, Loader2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import StockAddForm from '@/components/ui/StockAddForm';
import { toast } from '@/components/ui/use-toast';

const StockModal = ({ isOpen, onClose, title, children }) => {
  if (!isOpen) return null;

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4"
      onClick={onClose}
    >
      <motion.div
        initial={{ scale: 0.9, opacity: 0 }}
        animate={{ scale: 1, opacity: 1 }}
        exit={{ scale: 0.9, opacity: 0 }}
        className="bg-white rounded-xl shadow-2xl w-full max-w-md max-h-[90vh] flex flex-col"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="p-4 border-b border-gray-200 flex justify-between items-center">
          <h3 className="text-lg font-semibold text-gray-800">{title}</h3>
          <Button variant="ghost" size="sm" onClick={onClose}>
            <X className="h-5 w-5" />
          </Button>
        </div>
        <div className="p-6 overflow-y-auto">
          {children}
        </div>
      </motion.div>
    </motion.div>
  );
};
StockModal.propTypes = {
  isOpen: PropTypes.bool.isRequired,
  onClose: PropTypes.func.isRequired,
  title: PropTypes.string.isRequired,
  children: PropTypes.node,
};

const GestionStocks = ({ stocks, addStockItem, editStockItem, deleteStockItem, loadingStocks }) => {
  const [showAddModal, setShowAddModal] = useState(false);
  const [showEditModal, setShowEditModal] = useState(false);
  const [editingStock, setEditingStock] = useState(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const [newStockItem, setNewStockItem] = useState({
    nom: '',
    quantite: '',
    prixAchat: '',
    prixVente: '',
    beneficeNetAttendu: '',
  });

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    if (name === 'prixAchat' || name === 'prixVente') {
      let prixAchat = name === 'prixAchat' ? Number(value) : Number(showEditModal && editingStock ? editingStock.prixAchat : newStockItem.prixAchat);
      let prixVente = name === 'prixVente' ? Number(value) : Number(showEditModal && editingStock ? editingStock.prixVente : newStockItem.prixVente);
      const benefice = prixVente - prixAchat;
      if (showEditModal && editingStock) {
        setEditingStock(prev => ({ ...prev, [name]: value, beneficeNetAttendu: !isNaN(benefice) ? benefice : '' }));
      } else {
        setNewStockItem(prev => ({ ...prev, [name]: value, beneficeNetAttendu: !isNaN(benefice) ? benefice : '' }));
      }
    } else {
      if (showEditModal && editingStock) {
        setEditingStock(prev => ({ ...prev, [name]: value }));
      } else {
        setNewStockItem(prev => ({ ...prev, [name]: value }));
      }
    }
  };

  const handleAddStockSubmit = async (e) => {
    e.preventDefault();
    const itemToAdd = {
      ...newStockItem,
      quantite: Number(newStockItem.quantite),
      prixAchat: Number(newStockItem.prixAchat),
      prixVente: Number(newStockItem.prixVente),
      beneficeNetAttendu: newStockItem.beneficeNetAttendu !== '' ? Number(newStockItem.beneficeNetAttendu) : 0,
    };
    if (!itemToAdd.nom || !itemToAdd.quantite || !itemToAdd.prixAchat || !itemToAdd.prixVente) {
      toast({ variant: "destructive", title: "Erreur", description: "Tous les champs sont requis." });
      return;
    }
    setIsSubmitting(true);
    await addStockItem(itemToAdd);
    setIsSubmitting(false);
    setShowAddModal(false);
    setNewStockItem({ nom: '', quantite: '', prixAchat: '', prixVente: '', beneficeNetAttendu: '' });
  };

  const handleEditStockSubmit = async (e) => {
    e.preventDefault();
    const itemToEdit = {
      ...editingStock,
      quantite: Number(editingStock.quantite),
      prixAchat: Number(editingStock.prixAchat),
      prixVente: Number(editingStock.prixVente),
      beneficeNetAttendu: editingStock.beneficeNetAttendu !== '' ? Number(editingStock.beneficeNetAttendu) : 0,
    };
    if (!itemToEdit.nom || !itemToEdit.quantite || !itemToEdit.prixAchat || !itemToEdit.prixVente) {
      toast({ variant: "destructive", title: "Erreur", description: "Tous les champs sont requis." });
      return;
    }
    setIsSubmitting(true);
    await editStockItem(itemToEdit);
    setIsSubmitting(false);
    setShowEditModal(false);
    setEditingStock(null);
  };

  const openEditModal = (stockItem) => {
    setEditingStock(stockItem);
    setShowEditModal(true);
  };

  const handleDeleteStock = async (id) => {
    setIsSubmitting(true);
    await deleteStockItem(id);
    setIsSubmitting(false);
  };

  // Ouvre le modal d'ajout et réinitialise le state à chaque fois
  const openAddModal = () => {
    setNewStockItem({ nom: '', quantite: '', prixAchat: '', prixVente: '', beneficeNetAttendu: '' });
    setShowAddModal(true);
  };

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h3 className="text-xl font-semibold text-gray-800">Gestion des stocks</h3>
        <Button onClick={openAddModal} className="bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700">
          <Plus size={20} className="mr-2" />
          Ajouter un article
        </Button>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          className="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white"
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-blue-100">Articles en stock</p>
              <p className="text-3xl font-bold">{stocks?.length || 0}</p>
            </div>
            <Package size={40} className="text-blue-200" />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0, transition: { delay: 0.1 } }}
          className="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white"
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-green-100">Valeur totale</p>
              <p className="text-3xl font-bold">{stocks?.reduce((acc, item) => acc + (item.quantite * item.prixVente), 0) || 0} cfa</p>
            </div>
            <CreditCard size={40} className="text-green-200" />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0, transition: { delay: 0.2 } }}
          className="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-6 text-white"
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-orange-100">Stock faible</p>
              <p className="text-3xl font-bold">{stocks?.filter(item => item.quantite < 10).length || 0}</p>
            </div>
            <AlertCircle size={40} className="text-orange-200" />
          </div>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0, transition: { delay: 0.15 } }}
          className="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white"
        >
          <div className="flex items-center justify-between">
            <div>
              <p className="text-purple-100">Bénéfice net total attendu</p>
              <p className="text-3xl font-bold">{stocks?.reduce((acc, item) => acc + (((item.prixVente - item.prixAchat) * (item.quantite || 0)) || 0), 0).toLocaleString('fr-FR')} cfa</p>
            </div>
            <CreditCard size={40} className="text-purple-200" />
          </div>
        </motion.div>
      </div>

      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0, transition: { delay: 0.3 } }}
        className="bg-white rounded-xl shadow-lg overflow-hidden"
      >
        <div className="p-6 border-b border-gray-200">
          <h4 className="text-lg font-semibold text-gray-800">Inventaire</h4>
        </div>
        {loadingStocks && (
          <div className="p-6 text-center">
            <Loader2 className="h-8 w-8 animate-spin text-blue-600 mx-auto" />
            <p className="text-gray-500 mt-2">Chargement de l&apos;inventaire...</p>
          </div>
        )}
        {!loadingStocks && stocks && stocks.length === 0 && (
          <p className="p-6 text-center text-gray-500">Aucun article en stock pour le moment. Cliquez sur &quot;Ajouter un article&quot; pour commencer.</p>
        )}
        {!loadingStocks && stocks && stocks.length > 0 && (
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50">
                <tr>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Article</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantité</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix d&apos;achat</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prix de vente</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Valeur stock</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bénéfice net attendu</th>
                  <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody className="bg-white divide-y divide-gray-200">
                {stocks.map((item) => (
                  <tr key={item.id || item._id} className="hover:bg-gray-50">
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{item.nom}</td>
                    <td className="px-6 py-4 whitespace-nowrap">
                      <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${
                        item.quantite < 10 
                          ? 'bg-red-100 text-red-800' 
                          : 'bg-green-100 text-green-800'
                      }`}>
                        {item.quantite}
                      </span>
                    </td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{item.prixAchat} cfa</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{item.prixVente} cfa</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{item.quantite * item.prixVente} cfa</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{((item.prixVente - item.prixAchat) * (item.quantite || 0)).toLocaleString('fr-FR')} cfa</td>
                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                      <Button size="sm" variant="ghost" onClick={() => openEditModal(item)} disabled={isSubmitting}>
                        <Edit size={16} />
                      </Button>
                      <Button size="sm" variant="ghost" onClick={() => handleDeleteStock(item.id || item._id)} disabled={isSubmitting}>
                        <Trash2 size={16} />
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </motion.div>

      {showAddModal && (
        <StockModal isOpen={showAddModal} onClose={() => setShowAddModal(false)} title="Ajouter un nouvel article">
          <StockAddForm
            item={newStockItem}
            onSubmit={handleAddStockSubmit}
            onCancel={() => setShowAddModal(false)}
            onChange={handleInputChange}
            isSubmitting={isSubmitting}
          />
        </StockModal>
      )}

      {showEditModal && editingStock && (
        <StockModal isOpen={showEditModal} onClose={() => { setShowEditModal(false); setEditingStock(null); }} title="Modifier l'article">
          <StockAddForm
            item={editingStock}
            onSubmit={handleEditStockSubmit}
            onCancel={() => { setShowEditModal(false); setEditingStock(null); }}
            onChange={handleInputChange}
            isSubmitting={isSubmitting}
          />
        </StockModal>
      )}

    </div>
  );
};

GestionStocks.propTypes = {
  stocks: PropTypes.arrayOf(PropTypes.shape({
    id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    _id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    nom: PropTypes.string,
    quantite: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    prixAchat: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    prixVente: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  })),
  addStockItem: PropTypes.func.isRequired,
  editStockItem: PropTypes.func.isRequired,
  deleteStockItem: PropTypes.func.isRequired,
  loadingStocks: PropTypes.bool,
};

export default GestionStocks;
