import mongoose, { Document, Schema } from 'mongoose';

export interface ISAV extends Document {
  shopId: mongoose.Types.ObjectId;
  numeroSAV: string;
  repairId?: mongoose.Types.ObjectId;
  numeroReparationOrigine?: string;
  client_nom: string;
  client_telephone: string;
  appareil_marque_modele: string;
  description_probleme: string;
  sous_garantie: boolean;
  date_fin_garantie?: Date;
  statut: 'En attente' | 'En cours' | 'Résolu' | 'Refusé';
  decision: string;
  date_creation: Date;
  date_resolution?: Date;
  notes: string;
  userId: string;
}

const SAVSchema = new Schema<ISAV>({
  shopId: { type: Schema.Types.ObjectId, ref: 'Shop', required: true, index: true },
  numeroSAV: { type: String, required: true, unique: true },
  repairId: { type: Schema.Types.ObjectId, ref: 'Repair' },
  numeroReparationOrigine: { type: String, default: '' },
  client_nom: { type: String, required: true },
  client_telephone: { type: String, required: true },
  appareil_marque_modele: { type: String, required: true },
  description_probleme: { type: String, required: true },
  sous_garantie: { type: Boolean, default: false },
  date_fin_garantie: { type: Date },
  statut: { type: String, enum: ['En attente', 'En cours', 'Résolu', 'Refusé'], default: 'En attente' },
  decision: { type: String, default: '' },
  date_creation: { type: Date, default: Date.now },
  date_resolution: { type: Date },
  notes: { type: String, default: '' },
  userId: { type: String, required: true },
});

SAVSchema.index({ shopId: 1, date_creation: -1 });

export default mongoose.model<ISAV>('SAV', SAVSchema);
