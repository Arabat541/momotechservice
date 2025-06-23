import mongoose, { Document, Schema } from 'mongoose';

export interface IRepair extends Document {
  numeroReparation: string;
  type_reparation: 'place' | 'rdv';
  client_nom: string;
  client_telephone: string;
  appareil_marque_modele: string;
  pannes_services: { description: string; montant: number }[];
  pieces_rechange_utilisees: { stockId: string; nom: string; quantiteUtilisee: number }[];
  total_reparation: number;
  montant_paye: number;
  reste_a_payer: number;
  statut_reparation: string;
  date_creation: Date;
  date_mise_en_reparation?: Date;
  date_rendez_vous?: Date;
  date_retrait?: Date;
  etat_paiement: string;
  userId: string;
}

const RepairSchema = new Schema<IRepair>({
  numeroReparation: { type: String, required: true, unique: true },
  type_reparation: { type: String, enum: ['place', 'rdv'], required: true },
  client_nom: { type: String, required: true },
  client_telephone: { type: String, required: true },
  appareil_marque_modele: { type: String, required: true },
  pannes_services: [
    {
      description: { type: String, required: true },
      montant: { type: Number, required: true },
    },
  ],
  pieces_rechange_utilisees: [
    {
      stockId: { type: String, required: true },
      nom: { type: String, required: true },
      quantiteUtilisee: { type: Number, required: true },
    },
  ],
  total_reparation: { type: Number, required: true },
  montant_paye: { type: Number, required: true },
  reste_a_payer: { type: Number, required: true },
  statut_reparation: { type: String, required: true },
  date_creation: { type: Date, default: Date.now },
  date_mise_en_reparation: { type: Date },
  date_rendez_vous: { type: Date },
  date_retrait: { type: Date, default: null },
  etat_paiement: { type: String, required: true },
  userId: { type: String, required: true },
});

export default mongoose.model<IRepair>('Repair', RepairSchema);
