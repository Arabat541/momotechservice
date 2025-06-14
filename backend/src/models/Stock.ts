import mongoose, { Document, Schema } from 'mongoose';

export interface IStock extends Document {
  nom: string;
  quantite: number;
  prixAchat: number;
  prixVente: number;
  beneficeNetAttendu?: number;
}

const StockSchema = new Schema<IStock>({
  nom: { type: String, required: true },
  quantite: { type: Number, required: true },
  prixAchat: { type: Number, required: true },
  prixVente: { type: Number, required: true },
  beneficeNetAttendu: { type: Number, default: 0 },
});

export default mongoose.model<IStock>('Stock', StockSchema);
