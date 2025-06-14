import React from "react";
import PropTypes from "prop-types";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Loader2, Save } from "lucide-react";

const StockAddForm = React.memo(function StockAddForm({ item, onSubmit, onCancel, onChange, isSubmitting }) {
  return (
    <form onSubmit={onSubmit} className="space-y-4">
      <div>
        <Label htmlFor="nom" className="text-sm font-medium text-gray-700">Nom de l&apos;article</Label>
        <Input id="nom" name="nom" type="text" value={item?.nom || ''} onChange={onChange} required className="mt-1" autoFocus autoComplete="off" />
      </div>
      <div>
        <Label htmlFor="quantite" className="text-sm font-medium text-gray-700">Quantité</Label>
        <Input id="quantite" name="quantite" type="number" inputMode="decimal" pattern="[0-9]*" value={item?.quantite} onChange={onChange} required className="mt-1 no-spinner" style={{ MozAppearance: 'textfield' }} onWheel={e => e.target.blur()} />
      </div>
      <div>
        <Label htmlFor="prixAchat" className="text-sm font-medium text-gray-700">Prix d&apos;achat (cfa)</Label>
        <Input id="prixAchat" name="prixAchat" type="number" step="0.01" inputMode="decimal" pattern="[0-9]*" value={item?.prixAchat} onChange={onChange} required className="mt-1 no-spinner" style={{ MozAppearance: 'textfield' }} onWheel={e => e.target.blur()} />
      </div>
      <div>
        <Label htmlFor="prixVente" className="text-sm font-medium text-gray-700">Prix de vente (cfa)</Label>
        <Input id="prixVente" name="prixVente" type="number" step="0.01" inputMode="decimal" pattern="[0-9]*" value={item?.prixVente} onChange={onChange} required className="mt-1 no-spinner" style={{ MozAppearance: 'textfield' }} onWheel={e => e.target.blur()} />
      </div>
      <div>
        <Label htmlFor="beneficeNetAttendu" className="text-sm font-medium text-gray-700">Bénéfice net attendu (cfa)</Label>
        <Input id="beneficeNetAttendu" name="beneficeNetAttendu" type="number" step="0.01" inputMode="decimal" pattern="[0-9]*" value={item?.beneficeNetAttendu || ''} readOnly disabled className="mt-1 no-spinner bg-gray-100 text-gray-500 cursor-not-allowed" style={{ MozAppearance: 'textfield' }} />
      </div>
      <div className="flex justify-end space-x-3 pt-2">
        <Button type="button" variant="outline" onClick={onCancel} disabled={isSubmitting}>Annuler</Button>
        <Button type="submit" className="bg-gradient-to-r from-green-500 to-blue-500 hover:from-green-600 hover:to-blue-600 text-white" disabled={isSubmitting}>
          {isSubmitting ? <Loader2 size={18} className="mr-2 animate-spin" /> : <Save size={18} className="mr-2" />}
          Ajouter
        </Button>
      </div>
    </form>
  );
});

StockAddForm.propTypes = {
  item: PropTypes.shape({
    nom: PropTypes.string,
    quantite: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    prixAchat: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    prixVente: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
    beneficeNetAttendu: PropTypes.oneOfType([PropTypes.string, PropTypes.number]),
  }),
  onSubmit: PropTypes.func.isRequired,
  onCancel: PropTypes.func.isRequired,
  onChange: PropTypes.func.isRequired,
  isSubmitting: PropTypes.bool,
};

export default StockAddForm;
