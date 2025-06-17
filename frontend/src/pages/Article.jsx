import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useStocks } from '@/hooks/useStocks';
import { useReparations } from '@/hooks/useReparations';

// Onglet Article : vente et suivi des sorties de stock
const Article = ({ onVente, onAnnulerVente, ventes = [], sortiesRechange = [] }) => {
  const { stocks } = useStocks();
  const [selectedArticle, setSelectedArticle] = useState('');
  const [quantite, setQuantite] = useState(1);
  const [client, setClient] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleVendre = async () => {
    if (!selectedArticle || quantite < 1 || !client) return;
    setIsSubmitting(true);
    await onVente({ articleId: selectedArticle, quantite, client });
    setIsSubmitting(false);
    setSelectedArticle('');
    setQuantite(1);
    setClient('');
  };

  return (
    <div className="space-y-6 p-4">
      <div className="flex flex-col sm:flex-row justify-between items-center mb-4">
        <h2 className="text-xl font-bold text-gray-800">Vente d'article</h2>
        <Button
          className="bg-gradient-to-r from-green-600 to-blue-600 hover:from-green-700 hover:to-blue-700"
          onClick={handleVendre}
          disabled={isSubmitting || !selectedArticle || !client || quantite < 1}
        >
          Vendre
        </Button>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <Select value={selectedArticle} onValueChange={setSelectedArticle}>
          <SelectTrigger className="w-full">
            <SelectValue placeholder="Choisir un article en stock..." />
          </SelectTrigger>
          <SelectContent>
            {stocks.filter(s => s.quantite > 0).map(stock => (
              <SelectItem key={stock.id || stock._id} value={stock.id || stock._id}>
                {stock.nom} (Stock: {stock.quantite}, P.V: {stock.prixVente} cfa)
              </SelectItem>
            ))}
          </SelectContent>
        </Select>
        <input
          type="number"
          min={1}
          value={quantite}
          onChange={e => setQuantite(Number(e.target.value))}
          className="border rounded px-2 py-1"
          placeholder="Quantité"
        />
        <input
          type="text"
          value={client}
          onChange={e => setClient(e.target.value)}
          className="border rounded px-2 py-1"
          placeholder="Nom du client"
        />
      </div>
      <div>
        <h3 className="text-lg font-semibold mb-2">Articles sortis du stock</h3>
        <table className="w-full bg-white rounded shadow">
          <thead>
            <tr>
              <th className="p-2 text-left">Article</th>
              <th className="p-2 text-left">Quantité</th>
              <th className="p-2 text-left">Client</th>
              <th className="p-2 text-left">Type de sortie</th>
              <th className="p-2 text-left">Date</th>
              <th className="p-2 text-left">Actions</th>
            </tr>
          </thead>
          <tbody>
            {ventes.map((v, i) => (
              <tr key={i} className="border-t">
                <td className="p-2">{v.nom}</td>
                <td className="p-2">{v.quantite}</td>
                <td className="p-2">{v.client}</td>
                <td className="p-2">Vendu</td>
                <td className="p-2">{new Date(v.date).toLocaleString('fr-FR')}</td>
                <td className="p-2">
                  <Button size="sm" variant="ghost" className="text-red-600 hover:text-red-800" onClick={() => onAnnulerVente && onAnnulerVente(v, i)}>
                    Annuler
                  </Button>
                </td>
              </tr>
            ))}
            {sortiesRechange.map((s, i) => (
              <tr key={`rechange-${i}`} className="border-t">
                <td className="p-2">{s.nom}</td>
                <td className="p-2">{s.quantite}</td>
                <td className="p-2">-</td>
                <td className="p-2">Pièce de réchange</td>
                <td className="p-2">{new Date(s.date).toLocaleString('fr-FR')}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default Article;
