import React, { useState, useEffect, useRef } from 'react';
import PropTypes from 'prop-types';
import { motion } from 'framer-motion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import RecuPreview from '@/components/shared/RecuPreview';
import { toast } from '@/components/ui/use-toast';
import { useReactToPrint } from 'react-to-print';
import Barcode from 'react-barcode';
import { PlusCircle, Trash2, PackageSearch } from 'lucide-react';

const EtiquettePreviewPageContent = React.forwardRef(({ repairData }, ref) => {
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
  
  return (
    <div ref={ref} className="p-2 m-0 text-xs bg-white" style={{ width: '150px', border: '1px solid black' }}>
      <p className="font-bold text-center">{companyName.substring(0, 15)}</p>
      <p>N°: {repairData.numeroReparation}</p>
      <p>Client: {repairData.client?.substring(0, 18)}</p>
      <p>App: {repairData.appareil?.substring(0, 20)}</p>
      <p>Date: {new Date().toLocaleDateString('fr-FR')}</p>
      <div className="flex justify-center my-1">
        <Barcode value={repairData.numeroReparation || "N/A"} height={20} width={1} fontSize={8} displayValue={false} />
      </div>
    </div>
  );
});
EtiquettePreviewPageContent.displayName = "EtiquettePreviewPageContent";

// Ajout PropTypes pour EtiquettePreviewPageContent
EtiquettePreviewPageContent.propTypes = {
  repairData: PropTypes.shape({
    numeroReparation: PropTypes.string,
    client: PropTypes.string,
    appareil: PropTypes.string,
  }),
};

const ReparationsPlace = ({ onSave, generateNumeroReparation, availableStocks = [] }) => {
  const initialFormData = {
    client: '',
    telephone: '',
    appareil: '',
    pannes: [''],
    montants: [0],
    piecesRechange: [],
    total: 0,
    paye: 0,
    statut: 'En cours',
    numeroReparation: '',
  };

  const [formData, setFormData] = useState(initialFormData);
  const recuPreviewRef = useRef();
  const etiquettePreviewRef = useRef();
  const typeString = 'Réparation sur Place';

  const handlePrintTicket = useReactToPrint({
    content: () => recuPreviewRef.current,
    documentTitle: `Recu_${formData.numeroReparation || 'reparation'}`,
    onAfterPrint: () => toast({ title: "Impression Ticket", description: "Ticket envoyé à l'imprimante." }),
  });

  const handlePrintEtiquette = useReactToPrint({
    content: () => etiquettePreviewRef.current,
    documentTitle: `Etiquette_${formData.numeroReparation || 'reparation'}`,
    onAfterPrint: () => toast({ title: "Impression Étiquette", description: "Étiquette envoyée à l'imprimante." }),
  });

  const resetForm = () => {
    setFormData({
      ...initialFormData,
      numeroReparation: generateNumeroReparation()
    });
  };

  useEffect(() => {
    resetForm();
  }, [generateNumeroReparation]);
  
  useEffect(() => {
    const calculateTotal = () => {
      const pannesTotal = formData.montants.reduce((sum, acc) => sum + (parseFloat(acc) || 0), 0);
      const piecesTotal = formData.piecesRechange.reduce((sum, piece) => {
        const stockItem = availableStocks.find(s => (s.id || s._id) === piece.stockId);
        return sum + ((stockItem?.prixVente || 0) * (parseInt(piece.quantiteUtilisee) || 0));
      }, 0);
      setFormData(prev => ({ ...prev, total: pannesTotal + piecesTotal }));
    };
    calculateTotal();
  }, [formData.montants, formData.piecesRechange, availableStocks]);


  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({ ...prev, [name]: value }));
  };

  const handlePanneChange = (index, value) => {
    const newPannes = [...formData.pannes];
    newPannes[index] = value;
    setFormData(prev => ({ ...prev, pannes: newPannes }));
  };

  const handleMontantChange = (index, value) => {
    const newMontants = [...formData.montants];
    newMontants[index] = parseFloat(value) || 0;
    setFormData(prev => ({ ...prev, montants: newMontants }));
  };

  const addPanneMontantField = () => {
    if (formData.pannes.length < 4) {
      setFormData(prev => ({
        ...prev,
        pannes: [...prev.pannes, ''],
        montants: [...prev.montants, 0]
      }));
    } else {
      toast({ variant: "destructive", title: "Limite atteinte", description: "Vous ne pouvez pas ajouter plus de 4 pannes/montants." })
    }
  };

  const removePanneMontantField = (index) => {
    const newPannes = formData.pannes.filter((_, i) => i !== index);
    const newMontants = formData.montants.filter((_, i) => i !== index);
    setFormData(prev => ({ ...prev, pannes: newPannes, montants: newMontants }));
  };

  const handlePieceChange = (index, field, value) => {
    const newPieces = [...formData.piecesRechange];
    if (field === 'stockId') {
      // Toujours utiliser id || _id pour le stockId
      const stockItem = availableStocks.find(s => (s.id || s._id) === value);
      newPieces[index] = {
        ...newPieces[index],
        stockId: value,
        nom: stockItem?.nom || '',
      };
    } else if (field === 'quantiteUtilisee') {
      newPieces[index] = {
        ...newPieces[index],
        quantiteUtilisee: value
      };
    }
    setFormData(prev => ({ ...prev, piecesRechange: newPieces }));
  };

  const addPieceField = () => {
     if (formData.piecesRechange.length < 5) {
        setFormData(prev => ({
            ...prev,
            piecesRechange: [...prev.piecesRechange, { stockId: '', nom: '', quantiteUtilisee: 1 }]
        }));
     } else {
        toast({ variant: "destructive", title: "Limite atteinte", description: "Maximum 5 pièces de rechange." });
     }
  };

  const removePieceField = (index) => {
    const newPieces = formData.piecesRechange.filter((_, i) => i !== index);
    setFormData(prev => ({ ...prev, piecesRechange: newPieces }));
  };


  const handleSubmit = (e) => {
    e.preventDefault();
    const finalPieces = formData.piecesRechange.filter(p => p.stockId && p.quantiteUtilisee > 0);
    // Récupérer l'id utilisateur depuis le localStorage (ou adapter selon votre logique d'auth)
    const userProfile = JSON.parse(localStorage.getItem('profile') || '{}');
    const userId = userProfile.id || userProfile._id || userProfile.userId || '';
    if (!userId) {
      toast({ variant: "destructive", title: "Erreur utilisateur", description: "Impossible de récupérer l'utilisateur. Veuillez vous reconnecter." });
      return;
    }
    // Mapping pour le backend
    const repairToSend = {
      numeroReparation: formData.numeroReparation,
      type_reparation: 'place',
      client_nom: formData.client,
      client_telephone: formData.telephone,
      appareil_marque_modele: formData.appareil,
      pannes_services: formData.pannes.map((desc, i) => ({ description: desc, montant: parseFloat(formData.montants[i]) || 0 })),
      pieces_rechange_utilisees: finalPieces,
      total_reparation: formData.total,
      montant_paye: parseFloat(formData.paye) || 0,
      reste_a_payer: (formData.total || 0) - (parseFloat(formData.paye) || 0),
      statut_reparation: formData.statut,
      date_creation: new Date(),
      date_mise_en_reparation: new Date(),
      etat_paiement: (formData.total - (parseFloat(formData.paye) || 0)) <= 0 ? 'Soldé' : 'Non soldé',
      userId,
    };
    onSave(repairToSend);
  };

  const IPHONE_MODELS = [
    "iPhone 16e","iPhone 16","iPhone 16 Plus","iPhone 16 Pro","iPhone 16 Pro Max",
    "iPhone 15","iPhone 15 Plus","iPhone 15 Pro","iPhone 15 Pro Max",
    "iPhone 14","iPhone 14 Plus","iPhone 14 Pro","iPhone 14 ProMax",
    "iPhone 13","iPhone 13 Mini","iPhone 13 Pro","iPhone 13 Pro Max",
    "iPhone 12 classique","iPhone 12 Mini","iPhone 12 Pro","iPhone 12 Pro Max",
    "iPhone 11 classique","iPhone 11 Pro","iPhone 11 Pro Max",
    "iPhone X classique","iPhone XR","iPhone XS","iPhone XS Max",
    "iPhone SE","iPhone SE 2020","iPhone SE 2022",
    "iPhone 8","iPhone 8 Plus","iPhone 7","iPhone 7 Plus","iPhone 6"
  ];

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      className="bg-gray-50 rounded-xl shadow-2xl w-full max-h-[calc(100vh-100px)] flex flex-col p-2 sm:p-4"
    >
      <div className="p-4 border-b border-gray-200 sticky top-0 bg-gray-50 z-10 rounded-t-xl">
        <h1 className="text-xl font-semibold text-gray-800">
          Nouvelle Réparation sur Place
        </h1>
      </div>

      <div className="flex flex-col md:flex-row flex-1 overflow-hidden">
        <form onSubmit={handleSubmit} className="w-full md:w-3/5 p-4 space-y-3 overflow-y-auto md:border-r border-gray-200">
          <div>
            <Label htmlFor="numeroReparation" className="text-xs font-medium text-gray-600">N° Réparation</Label>
            <Input
              id="numeroReparation"
              name="numeroReparation"
              type="text"
              autoComplete="off"
              value={formData.numeroReparation}
              onChange={handleInputChange}
              disabled
              className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500 bg-gray-100"
            />
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <Label htmlFor="client" className="text-xs font-medium text-gray-600">Client</Label>
              <Input
                id="client"
                name="client"
                type="text"
                autoComplete="name"
                value={formData.client}
                onChange={handleInputChange}
                className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                required
              />
            </div>
            <div>
              <Label htmlFor="telephone" className="text-xs font-medium text-gray-600">Téléphone</Label>
              <Input
                id="telephone"
                name="telephone"
                type="text"
                autoComplete="tel"
                value={formData.telephone}
                onChange={handleInputChange}
                className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                required
              />
            </div>
          </div>

          <div>
            <Label htmlFor="appareil" className="text-xs font-medium text-gray-600">Appareil (Marque et Modèle)</Label>
            <select
              id="appareil"
              name="appareil"
              className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
              value={IPHONE_MODELS.includes(formData.appareil) ? formData.appareil : formData.appareil === '' ? '' : 'autre'}
              onChange={e => {
                if (e.target.value === 'autre') {
                  setFormData(prev => ({ ...prev, appareil: '' }));
                } else {
                  setFormData(prev => ({ ...prev, appareil: e.target.value }));
                }
              }}
              required
            >
              <option value="">Sélectionner un modèle</option>
              {IPHONE_MODELS.map(model => (
                <option key={model} value={model}>{model}</option>
              ))}
              <option value="autre">Autre...</option>
            </select>
            {!IPHONE_MODELS.includes(formData.appareil) && (
              <Input
                id="appareil-autre"
                name="appareil-autre"
                type="text"
                autoComplete="off"
                placeholder="Saisir un autre modèle"
                className="w-full text-sm py-1.5 border-gray-300 rounded-md mt-2"
                value={formData.appareil}
                onChange={e => setFormData(prev => ({ ...prev, appareil: e.target.value }))}
                required
              />
            )}
          </div>

          <div>
            <Label className="text-xs font-medium text-gray-600 mb-1 block">Pannes et Montants (Services)</Label>
            {formData.pannes.map((panne, index) => (
              <div key={index} className="flex items-center space-x-2 mb-1.5">
                <Input
                  id={`panne-${index}`}
                  name={`panne-${index}`}
                  type="text"
                  autoComplete="off"
                  placeholder={`Service ${index + 1}`}
                  value={panne}
                  onChange={(e) => handlePanneChange(index, e.target.value)}
                  className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                />
                <Input
                  id={`montant-${index}`}
                  name={`montant-${index}`}
                  type="number"
                  autoComplete="off"
                  inputMode="decimal"
                  pattern="[0-9]*"
                  placeholder={`Montant`}
                  value={formData.montants[index] === 0 ? '' : formData.montants[index]}
                  onChange={(e) => handleMontantChange(index, e.target.value)}
                  className="w-1/3 text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500 no-spinner"
                  style={{ MozAppearance: 'textfield' }}
                  onWheel={e => e.target.blur()}
                />
                {formData.pannes.length > 1 && (
                  <Button type="button" variant="ghost" size="sm" onClick={() => removePanneMontantField(index)} className="text-red-500 hover:text-red-700 px-2 py-1 text-xs"><Trash2 size={14}/></Button>
                )}
              </div>
            ))}
            {formData.pannes.length < 4 && (
              <Button type="button" variant="outline" size="sm" onClick={addPanneMontantField} className="text-xs py-1"><PlusCircle size={14} className="mr-1"/> Ajouter Service</Button>
            )}
          </div>
          
          <div>
            <Label className="text-xs font-medium text-gray-600 mb-1 block">Pièces de rechange utilisées</Label>
            {formData.piecesRechange.map((piece, index) => (
              <div key={index} className="flex items-center space-x-2 mb-1.5">
                <Select
                  value={piece.stockId}
                  onValueChange={(value) => handlePieceChange(index, 'stockId', value)}
                >
                  <SelectTrigger className="w-2/3 text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500">
                    <SelectValue placeholder="Choisir une pièce..." />
                  </SelectTrigger>
                  <SelectContent>
                    {availableStocks.map(stockItem => (
                      <SelectItem key={stockItem.id || stockItem._id} value={stockItem.id || stockItem._id} disabled={stockItem.quantite <= 0 && !formData.piecesRechange.some(p => p.stockId === (stockItem.id || stockItem._id) && p.quantiteUtilisee > 0 )}>
                        {stockItem.nom} (Stock: {stockItem.quantite}, P.V: {stockItem.prixVente} cfa)
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <Input
                  id={`piece-qty-${index}`}
                  name={`piece-qty-${index}`}
                  type="number"
                  autoComplete="off"
                  placeholder="Qté"
                  min="1"
                  value={piece.quantiteUtilisee}
                  onChange={(e) => handlePieceChange(index, 'quantiteUtilisee', parseInt(e.target.value) || 1)}
                  className="w-1/4 text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                />
                <Button type="button" variant="ghost" size="sm" onClick={() => removePieceField(index)} className="text-red-500 hover:text-red-700 px-2 py-1 text-xs"><Trash2 size={14}/></Button>
              </div>
            ))}
             {availableStocks && availableStocks.length > 0 && formData.piecesRechange.length < 5 && (
                <Button type="button" variant="outline" size="sm" onClick={addPieceField} className="text-xs py-1"><PackageSearch size={14} className="mr-1"/> Ajouter Pièce</Button>
             )}
             {(!availableStocks || availableStocks.length === 0) && <p className="text-xs text-gray-500">Aucune pièce en stock. Ajoutez des articles dans &quot;Gestion des Stocks&quot;.</p>}
          </div>


          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <Label htmlFor="total" className="text-xs font-medium text-gray-600">Total</Label>
              <Input
                id="total"
                name="total"
                type="number"
                autoComplete="off"
                value={formData.total}
                readOnly
                className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500 bg-gray-100 font-semibold"
              />
            </div>
            <div>
              <Label htmlFor="paye" className="text-xs font-medium text-gray-600">Payé</Label>
              <Input
                id="paye"
                name="paye"
                type="number"
                autoComplete="off"
                inputMode="decimal"
                pattern="[0-9]*"
                value={formData.paye === 0 ? '' : formData.paye}
                onChange={handleInputChange}
                className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500 no-spinner"
                required
                style={{ MozAppearance: 'textfield' }}
                onWheel={e => e.target.blur()}
              />
            </div>
          </div>
          
          <div>
            <Label htmlFor="statut" className="text-xs font-medium text-gray-600">Statut</Label>
            <Select
              value={formData.statut}
              onValueChange={(value) => setFormData({ ...formData, statut: value })}
            >
              <SelectTrigger className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500">
                <SelectValue placeholder="Sélectionner statut" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="En cours">En cours</SelectItem>
                <SelectItem value="Terminé">Terminé</SelectItem>
                <SelectItem value="Annulé">Annulé</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div className="flex flex-col sm:flex-row justify-end space-y-2 sm:space-y-0 sm:space-x-3 pt-3 sticky bottom-0 bg-gray-50 py-3 border-t border-gray-200 mt-auto">
            <Button type="button" variant="outline" onClick={resetForm} className="w-full sm:w-auto text-xs bg-red-100 text-red-700 hover:bg-red-200 border-red-200">
              Effacer
            </Button>
            <Button type="submit" className="w-full sm:w-auto text-xs bg-green-600 hover:bg-green-700 text-white">
              Enregistrer
            </Button>
          </div>
        </form>

        <div className="w-full md:w-2/5 p-1 md:p-2 bg-white overflow-y-auto flex flex-col items-center justify-start">
          <div className="transform scale-[0.85] origin-top my-2">
            <RecuPreview
              ref={recuPreviewRef}
              repairData={{ ...formData, typeString: typeString, date: new Date().toLocaleDateString('fr-FR') }}
              typeString={typeString}
              onClose={() => { }}
              onPrintTicket={handlePrintTicket}
              onPrintEtiquette={handlePrintEtiquette}
              onSave={() => { }}
              isPreviewInModal={false} // Désactive l'affichage modal
            />
          </div>
          <div className="flex flex-row gap-2 mb-2">
            <Button type="button" onClick={handlePrintTicket} className="bg-blue-600 hover:bg-blue-700 text-white text-xs">Imprimer ticket</Button>
            <Button type="button" onClick={handlePrintEtiquette} className="bg-purple-600 hover:bg-purple-700 text-white text-xs">Imprimer code barre</Button>
          </div>
          <div style={{ display: "none" }}>
            <EtiquettePreviewPageContent ref={etiquettePreviewRef} repairData={formData} />
          </div>
        </div>
      </div>
    </motion.div>
  );
};

ReparationsPlace.propTypes = {
  onSave: PropTypes.func.isRequired,
  generateNumeroReparation: PropTypes.func.isRequired,
  availableStocks: PropTypes.arrayOf(PropTypes.shape({
    id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    _id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    nom: PropTypes.string,
    quantite: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    prixAchat: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    prixVente: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  })),
};

// CSS pour cacher les flèches sur input type number
// Ajoute dans le fichier css global ou local :
// .no-spinner::-webkit-outer-spin-button, .no-spinner::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
// .no-spinner { -moz-appearance: textfield; }
export default ReparationsPlace;
