import React, { useState, useEffect, useRef } from 'react';
import { motion } from 'framer-motion';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import RecuPreview from '@/components/shared/RecuPreview'; 
import { toast } from '@/components/ui/use-toast';
import { useReactToPrint } from 'react-to-print';
import Barcode from 'react-barcode';


const Modal = ({ type, repair, onClose, onSave }) => {
  const initialFormData = {
    client: '',
    telephone: '',
    appareil: '',
    pannes: [''],
    montants: [0],
    total: 0,
    paye: 0,
    statut: 'En cours',
    numeroReparation: '',
    dateRendezVous: '',
    dateRetrait: '',
    etatPaiement: 'Non soldé',
    miseEnReparation: new Date().toISOString().slice(0, 16) 
  };

  const [formData, setFormData] = useState(initialFormData);
  const recuPreviewModalRef = useRef();
  const etiquettePreviewModalRef = useRef();

  const typeString = type.includes('place') ? 'Réparation sur Place' : 'Réparation sur RdV';

  const handlePrintFromModal = useReactToPrint({
    content: () => recuPreviewModalRef.current,
    documentTitle: `Recu_${formData.numeroReparation || 'reparation'}`,
    onAfterPrint: () => toast({ title: "Impression Ticket", description: "Ticket envoyé à l'imprimante."}),
  });

  const handlePrintEtiquetteFromModal = useReactToPrint({
    content: () => etiquettePreviewModalRef.current,
    documentTitle: `Etiquette_${formData.numeroReparation || 'reparation'}`,
    onAfterPrint: () => toast({ title: "Impression Étiquette", description: "Étiquette envoyée à l'imprimante."}),
  });


  useEffect(() => {
    const generateNumeroReparation = () => {
      return type.includes('place') ? `RP${Date.now().toString().slice(-6)}` : `2AR${Math.floor(Math.random() * 1000).toString().padStart(3, '0')}`;
    };

    if (repair) {
      setFormData({
        client: repair.client || '',
        telephone: repair.telephone || '',
        appareil: repair.appareil || '',
        pannes: repair.pannes && repair.pannes.length > 0 ? repair.pannes : [''],
        montants: repair.montants && repair.montants.length > 0 ? repair.montants : [0],
        total: repair.total || 0,
        paye: repair.paye || 0,
        statut: repair.statut || 'En cours',
        numeroReparation: repair.numeroReparation || generateNumeroReparation(),
        dateRendezVous: repair.dateRendezVous || '',
        dateRetrait: repair.dateRetrait || '',
        etatPaiement: repair.etatPaiement || 'Non soldé',
        miseEnReparation: repair.miseEnReparation || new Date().toISOString().slice(0, 16),
        id: repair.id 
      });
    } else {
      setFormData({
        ...initialFormData,
        numeroReparation: generateNumeroReparation(),
        id: Date.now() 
      });
    }
  }, [repair, type]);


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
    const newTotal = newMontants.reduce((sum, acc) => sum + acc, 0);
    setFormData(prev => ({ ...prev, montants: newMontants, total: newTotal }));
  };
  
  const addPanneMontantField = () => {
    if (formData.pannes.length < 4) {
      setFormData(prev => ({
        ...prev,
        pannes: [...prev.pannes, ''],
        montants: [...prev.montants, 0]
      }));
    } else {
      toast({variant: "destructive", title: "Limite atteinte", description: "Vous ne pouvez pas ajouter plus de 4 pannes/montants."})
    }
  };

  const removePanneMontantField = (index) => {
    const newPannes = formData.pannes.filter((_, i) => i !== index);
    const newMontants = formData.montants.filter((_, i) => i !== index);
    const newTotal = newMontants.reduce((sum, acc) => sum + acc, 0);
    setFormData(prev => ({ ...prev, pannes: newPannes, montants: newMontants, total: newTotal }));
  };


  const handleSubmit = (e) => {
    e.preventDefault();
    let finalData = { ...formData };
    if (type.includes('rdv')) {
      finalData.resteAPayer = finalData.total - finalData.paye;
      finalData.etatPaiement = finalData.resteAPayer <= 0 ? 'Soldé' : 'Non soldé';
    }
    onSave(finalData); 
  };
  
  const isRdv = type.includes('rdv');

  return (
    <motion.div
      initial={{ opacity: 0 }}
      animate={{ opacity: 1 }}
      exit={{ opacity: 0 }}
      className="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-2 sm:p-4 overflow-auto"
      onClick={onClose}
    >
      <motion.div
        initial={{ scale: 0.95, opacity: 0 }}
        animate={{ scale: 1, opacity: 1 }}
        exit={{ scale: 0.95, opacity: 0 }}
        className="bg-gray-50 rounded-xl shadow-2xl w-full max-w-4xl max-h-[95vh] flex flex-col"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="p-4 border-b border-gray-200 sticky top-0 bg-gray-50 z-10 rounded-t-xl">
          <h3 className="text-lg font-semibold text-gray-800">
            {type.includes('add') ? (isRdv ? 'Nouvelle réparation RDV' : 'Nouvelle réparation sur place') : 
             (isRdv ? 'Modifier réparation RDV' : 'Modifier réparation sur place')}
          </h3>
        </div>

        <div className="flex flex-col md:flex-row flex-1 overflow-hidden">
          <form onSubmit={handleSubmit} className="w-full md:w-3/5 p-4 space-y-3 overflow-y-auto md:border-r border-gray-200">
            <div>
              <Label htmlFor="numeroReparation" className="text-xs font-medium text-gray-600">N° Réparation</Label>
              <Input
                id="numeroReparation"
                name="numeroReparation"
                type="text"
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
                  value={formData.telephone}
                  onChange={handleInputChange}
                  className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                  required
                />
              </div>
            </div>

            <div>
              <Label htmlFor="appareil" className="text-xs font-medium text-gray-600">Appareil (Marque et Modèle)</Label>
              <Input
                id="appareil"
                name="appareil"
                type="text"
                value={formData.appareil}
                onChange={handleInputChange}
                className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                required
              />
            </div>

            <div>
              <Label className="text-xs font-medium text-gray-600 mb-1 block">Pannes et Montants</Label>
              {formData.pannes.map((panne, index) => (
                <div key={index} className="flex items-center space-x-2 mb-1.5">
                  <Input
                    type="text"
                    placeholder={`Panne ${index + 1}`}
                    value={panne}
                    onChange={(e) => handlePanneChange(index, e.target.value)}
                    className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                  />
                  <Input
                    type="number"
                    placeholder={`Montant`}
                    value={formData.montants[index]}
                    onChange={(e) => handleMontantChange(index, e.target.value)}
                    className="w-1/3 text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                  />
                  {formData.pannes.length > 1 && (
                    <Button type="button" variant="destructive" size="sm" onClick={() => removePanneMontantField(index)} className="px-2 py-1 text-xs">X</Button>
                  )}
                </div>
              ))}
              {formData.pannes.length < 4 && (
                 <Button type="button" variant="outline" size="sm" onClick={addPanneMontantField} className="text-xs py-1">Ajouter Panne/Montant</Button>
              )}
            </div>
            
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <Label htmlFor="total" className="text-xs font-medium text-gray-600">Total</Label>
                <Input
                  id="total"
                  name="total"
                  type="number"
                  value={formData.total}
                  onChange={handleInputChange}
                  disabled={formData.pannes.length > 0 && formData.pannes.some(p => p !== '')}
                  className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500 bg-gray-100"
                  required
                />
              </div>
              <div>
                <Label htmlFor="paye" className="text-xs font-medium text-gray-600">Payé</Label>
                <Input
                  id="paye"
                  name="paye"
                  type="number"
                  value={formData.paye}
                  onChange={handleInputChange}
                  className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                  required
                />
              </div>
            </div>

            {isRdv && (
              <>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <Label htmlFor="resteAPayer" className="text-xs font-medium text-gray-600">Reste à Payer</Label>
                    <Input
                      id="resteAPayer"
                      name="resteAPayer"
                      type="number"
                      value={formData.total - formData.paye}
                      disabled
                      className="w-full text-sm py-1.5 border-gray-300 rounded-md bg-gray-100"
                    />
                  </div>
                  <div>
                    <Label htmlFor="etatPaiement" className="text-xs font-medium text-gray-600">État du Paiement</Label>
                    <Input
                      id="etatPaiement"
                      name="etatPaiement"
                      type="text"
                      value={(formData.total - formData.paye) <= 0 ? 'Soldé' : 'Non soldé'}
                      disabled
                      className="w-full text-sm py-1.5 border-gray-300 rounded-md bg-gray-100"
                    />
                  </div>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div>
                    <Label htmlFor="miseEnReparation" className="text-xs font-medium text-gray-600">Mise en réparation</Label>
                    <Input
                      id="miseEnReparation"
                      name="miseEnReparation"
                      type="datetime-local"
                      value={formData.miseEnReparation}
                      onChange={handleInputChange}
                      className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                    />
                  </div>
                  <div>
                    <Label htmlFor="dateRendezVous" className="text-xs font-medium text-gray-600">Date Rendez-vous</Label>
                    <Input
                      id="dateRendezVous"
                      name="dateRendezVous"
                      type="date"
                      value={formData.dateRendezVous}
                      onChange={handleInputChange}
                      className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                    />
                  </div>
                </div>
                 <div>
                    <Label htmlFor="dateRetrait" className="text-xs font-medium text-gray-600">Date Retrait</Label>
                    <Input
                      id="dateRetrait"
                      name="dateRetrait"
                      type="datetime-local"
                      value={formData.dateRetrait}
                      onChange={handleInputChange}
                      className="w-full text-sm py-1.5 border-gray-300 rounded-md focus:ring-blue-500"
                    />
                  </div>
              </>
            )}

            <div>
              <Label htmlFor="statut" className="text-xs font-medium text-gray-600">Statut</Label>
              <Select 
                value={formData.statut} 
                onValueChange={(value) => setFormData({...formData, statut: value})}
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
              <Button type="button" variant="outline" onClick={onClose} className="w-full sm:w-auto text-xs">
                Annuler
              </Button>
              <Button type="submit" className="w-full sm:w-auto text-xs bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700">
                {type.includes('add') ? 'Enregistrer Réparation' : 'Mettre à Jour Réparation'}
              </Button>
            </div>
          </form>

          <div className="w-full md:w-2/5 p-1 md:p-2 bg-white overflow-y-auto flex flex-col items-center justify-start">
            <div className="transform scale-[0.85] origin-top my-2">
              <RecuPreview
                ref={recuPreviewModalRef}
                repairData={{...formData, typeString: typeString }}
                typeString={typeString}
                onClose={() => {}} 
                onPrintTicket={handlePrintFromModal}
                onPrintEtiquette={handlePrintEtiquetteFromModal}
                onSave={() => {}}
                isPreviewInModal={true}
              />
            </div>
            <div style={{ display: "none" }}>
               <EtiquettePreviewModalContent ref={etiquettePreviewModalRef} repairData={formData} />
            </div>
          </div>
        </div>
      </motion.div>
    </motion.div>
  );
};

const EtiquettePreviewModalContent = React.forwardRef(({ repairData }, ref) => {
  if (!repairData) return null;
  const companyInfo = JSON.parse(localStorage.getItem('companyInfo')) || {};
  const companyName = companyInfo.nomEntreprise || "MOMO TECH";
  return (
    <div ref={ref} className="p-2 m-0 text-xs bg-white" style={{ width: '150px', border: '1px solid black'}}>
      <p className="font-bold text-center">{companyName.substring(0,15)}</p>
      <p>N°: {repairData.numeroReparation}</p>
      <p>Client: {repairData.client?.substring(0, 18)}</p>
      <p>App: {repairData.appareil?.substring(0, 20)}</p>
      <p>Date: {repairData.date || (repairData.miseEnReparation ? new Date(repairData.miseEnReparation).toLocaleDateString('fr-FR') : new Date().toLocaleDateString('fr-FR'))}</p>
       <div className="flex justify-center my-1">
         <Barcode value={repairData.numeroReparation || "N/A"} height={20} width={1} fontSize={8} displayValue={false} />
      </div>
    </div>
  );
});
EtiquettePreviewModalContent.displayName = "EtiquettePreviewModalContent";

export default Modal;