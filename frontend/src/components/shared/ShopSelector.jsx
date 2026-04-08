import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { Store, ChevronDown, Plus, Check } from 'lucide-react';

const ShopSelector = ({ shops, currentShop, onSelectShop, onCreateShop, isPatron }) => {
  const [isOpen, setIsOpen] = useState(false);
  const [showCreateForm, setShowCreateForm] = useState(false);
  const [newShopName, setNewShopName] = useState('');
  const [newShopAdresse, setNewShopAdresse] = useState('');
  const [newShopTel, setNewShopTel] = useState('');

  const handleSelect = (shop) => {
    onSelectShop(shop);
    setIsOpen(false);
  };

  const handleCreate = async (e) => {
    e.preventDefault();
    if (!newShopName.trim()) return;
    await onCreateShop({ nom: newShopName.trim(), adresse: newShopAdresse.trim(), telephone: newShopTel.trim() });
    setNewShopName('');
    setNewShopAdresse('');
    setNewShopTel('');
    setShowCreateForm(false);
    setIsOpen(false);
  };

  return (
    <div className="relative">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="flex items-center gap-2 bg-white/10 hover:bg-white/20 text-white px-3 py-2 rounded-lg transition-colors text-sm"
      >
        <Store size={16} />
        <span className="truncate max-w-[140px]">{currentShop?.nom || 'Aucune boutique'}</span>
        <ChevronDown size={14} className={`transition-transform ${isOpen ? 'rotate-180' : ''}`} />
      </button>

      {isOpen && (
        <div className="absolute top-full left-0 mt-1 w-64 bg-white rounded-lg shadow-xl border border-gray-200 z-50 overflow-hidden">
          <div className="py-1 max-h-60 overflow-y-auto">
            {shops.map((shop) => {
              const shopId = shop._id || shop.id;
              const isActive = currentShop && (currentShop._id || currentShop.id) === shopId;
              return (
                <button
                  key={shopId}
                  onClick={() => handleSelect(shop)}
                  className={`w-full flex items-center gap-2 px-4 py-2 text-sm text-left hover:bg-blue-50 transition-colors ${
                    isActive ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'
                  }`}
                >
                  <Store size={14} />
                  <span className="flex-1 truncate">{shop.nom}</span>
                  {isActive && <Check size={14} className="text-blue-600" />}
                </button>
              );
            })}
            {shops.length === 0 && (
              <p className="px-4 py-2 text-sm text-gray-400 italic">Aucune boutique</p>
            )}
          </div>

          {isPatron && (
            <div className="border-t border-gray-100">
              {!showCreateForm ? (
                <button
                  onClick={() => setShowCreateForm(true)}
                  className="w-full flex items-center gap-2 px-4 py-2 text-sm text-blue-600 hover:bg-blue-50 transition-colors"
                >
                  <Plus size={14} />
                  Nouvelle boutique
                </button>
              ) : (
                <form onSubmit={handleCreate} className="p-3 space-y-2">
                  <input
                    type="text"
                    value={newShopName}
                    onChange={(e) => setNewShopName(e.target.value)}
                    placeholder="Nom de la boutique *"
                    className="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-gray-900"
                    autoFocus
                    required
                  />
                  <input
                    type="text"
                    value={newShopAdresse}
                    onChange={(e) => setNewShopAdresse(e.target.value)}
                    placeholder="Adresse"
                    className="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-gray-900"
                  />
                  <input
                    type="text"
                    value={newShopTel}
                    onChange={(e) => setNewShopTel(e.target.value)}
                    placeholder="Téléphone"
                    className="w-full px-2 py-1.5 text-sm border border-gray-300 rounded focus:ring-blue-500 focus:border-blue-500 text-gray-900"
                  />
                  <div className="flex gap-2">
                    <button
                      type="submit"
                      className="flex-1 bg-blue-600 text-white text-xs py-1.5 rounded hover:bg-blue-700"
                    >
                      Créer
                    </button>
                    <button
                      type="button"
                      onClick={() => setShowCreateForm(false)}
                      className="flex-1 bg-gray-200 text-gray-700 text-xs py-1.5 rounded hover:bg-gray-300"
                    >
                      Annuler
                    </button>
                  </div>
                </form>
              )}
            </div>
          )}
        </div>
      )}
    </div>
  );
};

ShopSelector.propTypes = {
  shops: PropTypes.array.isRequired,
  currentShop: PropTypes.object,
  onSelectShop: PropTypes.func.isRequired,
  onCreateShop: PropTypes.func.isRequired,
  isPatron: PropTypes.bool,
};

export default ShopSelector;
