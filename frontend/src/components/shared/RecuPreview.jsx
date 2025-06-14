import React, { useState, useEffect } from 'react';
import PropTypes from 'prop-types';
import Barcode from 'react-barcode';
import { useStocks } from '@/hooks/useStocks'; // Import useStocks to get available stocks
import { getSettings } from '@/lib/api';

const RecuPreview = React.forwardRef(({ repairData }, ref) => {
  const [companyInfo, setCompanyInfo] = useState({
    nomEntreprise: "MOMO TECH SERVICE",
    adresse: "Face Grande mosquée à côté de moov",
    telephone: "0710510157",
    slogan: "[la technologie au bout des doigts...]",
  });
  const [garantieInfo, setGarantieInfo] = useState({
    duree: "7",
    message: "Garantit une semaine. Passé ce délai, MOMO TECH SERVICE décline toute responsabilité.",
  });
  const { stocks: availableStocks } = useStocks();

  useEffect(() => {
    const fetchSettings = async () => {
      try {
        const token = localStorage.getItem('token');
        if (!token) return;
        const settings = await getSettings(token);
        if (settings && settings.companyInfo) {
          setCompanyInfo({
            nomEntreprise: settings.companyInfo.nom || '',
            adresse: settings.companyInfo.adresse || '',
            telephone: settings.companyInfo.telephone || '',
            slogan: settings.companyInfo.slogan || '',
          });
        }
        if (settings && settings.warranty) {
          setGarantieInfo({
            duree: settings.warranty.duree || '',
            message: settings.warranty.conditions || '',
          });
        }
      } catch (e) {
        // fallback localStorage si erreur
        const savedCompanyInfo = localStorage.getItem('companyInfo');
        if (savedCompanyInfo) setCompanyInfo(JSON.parse(savedCompanyInfo));
        const savedGarantieInfo = localStorage.getItem('garantieInfo');
        if (savedGarantieInfo) setGarantieInfo(JSON.parse(savedGarantieInfo));
      }
    };
    fetchSettings();
  }, []);

  if (!repairData) {
    return (
      <div className="bg-white p-8 rounded-lg shadow-xl text-center">Chargement des données du reçu...</div>
    );
  }

  const isRdv = repairData.type_reparation === 'rdv';
  const resteAPayer = (repairData.total || 0) - (repairData.paye || 0);
  const etatPaiement = resteAPayer <= 0 ? 'Soldé' : 'Non soldé';

  const renderContent = () => {
    // Correction : toujours utiliser repairData.numeroReparation (champ unique)
    const numero = repairData.numeroReparation || repairData.numero_reparation || '';

    return (
      <div className="bg-white text-gray-800 text-sm w-[302px] p-3 shadow-lg border border-gray-300">
        <div className="text-center mb-2">
          <h2 className="text-lg font-bold uppercase">{companyInfo.nomEntreprise}</h2>
          <p className="text-xs">{companyInfo.adresse}</p>
          <p className="text-xs">Tél: {companyInfo.telephone}</p>
          <p className="text-xs italic">{companyInfo.slogan}</p>
        </div>
        <div className="mb-2">
          <div className="flex justify-between">
            <span className="font-semibold">N° Réparation:</span>
            <span>{numero}</span>
          </div>
          <div className="flex justify-between">
            <span className="font-semibold">Date création:</span>
            <span>{repairData.date_creation ? new Date(repairData.date_creation).toLocaleDateString('fr-FR') : 'N/A'}</span>
          </div>
          {isRdv && (
            <>
              <div className="flex justify-between">
                <span className="font-semibold">Date RDV:</span>
                <span>{repairData.dateRendezVous ? new Date(repairData.dateRendezVous).toLocaleDateString('fr-FR') : 'N/A'}</span>
              </div>
            </>
          )}
        </div>
        <div className="mb-2 border-t border-b border-dashed border-gray-400 py-1">
          <p><span className="font-semibold">Client:</span> {repairData.client}</p>
          <p><span className="font-semibold">Téléphone:</span> {repairData.telephone}</p>
          <p><span className="font-semibold">Appareil:</span> {repairData.appareil}</p>
        </div>
        <div className="mb-2">
          <p className="font-semibold underline mb-0.5">Pannes / Services:</p>
          {Array.isArray(repairData.pannes) && Array.isArray(repairData.montants) && repairData.pannes.map((panne, index) => (
            panne && repairData.montants[index] > 0 && (
              <div key={`panne-${index}`} className="flex justify-between text-xs">
                <span>- {panne}</span>
                <span>{repairData.montants[index].toLocaleString('fr-FR')} cfa</span>
              </div>
            )
          ))}
        </div>
        {repairData.piecesRechange && repairData.piecesRechange.length > 0 && repairData.piecesRechange.some(p => p.stockId && p.quantiteUtilisee > 0) && (
          <div className="mb-2">
            <p className="font-semibold underline mb-0.5">Pièces de rechange:</p>
            {repairData.piecesRechange.filter(p => p.stockId && p.quantiteUtilisee > 0).map((piece, index) => {
              const stockItem = availableStocks.find(s => (s.id === piece.stockId || s._id === piece.stockId));
              const prixTotalPiece = (stockItem?.prixVente || 0) * piece.quantiteUtilisee;
              return (
                <div key={`piece-${index}`} className="flex justify-between text-xs">
                  <span>- {piece.nom || stockItem?.nom} (x{piece.quantiteUtilisee})</span>
                  <span>{prixTotalPiece.toLocaleString('fr-FR')} cfa</span>
                </div>
              );
            })}
          </div>
        )}
        <div className="border-t border-gray-400 pt-1 mb-2">
          <div className="flex justify-between font-bold text-base">
            <span>TOTAL:</span>
            <span>{(repairData.total || 0).toLocaleString('fr-FR')} cfa</span>
          </div>
          <div className="flex justify-between text-xs">
            <span>Payé:</span>
            <span>{(repairData.paye || 0).toLocaleString('fr-FR')} cfa</span>
          </div>
          <div className="flex justify-between text-xs font-semibold">
            <span>Reste à payer:</span>
            <span>{resteAPayer.toLocaleString('fr-FR')} cfa</span>
          </div>
        </div>
        <div className="text-center mb-2">
          <Barcode value={numero || "N/A"} height={30} width={1.2} fontSize={10} />
        </div>
        <div className="text-xs text-center border-t border-dashed border-gray-400 pt-1">
          <p className="font-semibold">Merci pour votre confiance!</p>
          <p>{garantieInfo.message} (Durée: {garantieInfo.duree} jours)</p>
          <p className="font-bold mt-1">Statut: {repairData.statut} | Paiement: {etatPaiement}</p>
        </div>
      </div>
    );
  };

  return <div ref={ref}>{renderContent()}</div>;
});

RecuPreview.displayName = 'RecuPreview';
RecuPreview.propTypes = {
  repairData: PropTypes.shape({
    numeroReparation: PropTypes.string,
    numero_reparation: PropTypes.string,
    type_reparation: PropTypes.string,
    date_mise_en_reparation: PropTypes.oneOfType([PropTypes.string, PropTypes.instanceOf(Date)]),
    date_creation: PropTypes.oneOfType([PropTypes.string, PropTypes.instanceOf(Date)]),
    total: PropTypes.number,
    paye: PropTypes.number,
    statut: PropTypes.string,
    dateRendezVous: PropTypes.oneOfType([PropTypes.string, PropTypes.instanceOf(Date)]),
    client: PropTypes.string,
    telephone: PropTypes.string,
    appareil: PropTypes.string,
    pannes: PropTypes.arrayOf(PropTypes.string),
    montants: PropTypes.arrayOf(PropTypes.number),
    piecesRechange: PropTypes.arrayOf(PropTypes.shape({
      stockId: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
      nom: PropTypes.string,
      quantiteUtilisee: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    })),
  }),
};

export default RecuPreview;
