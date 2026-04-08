import mongoose, { Document, Schema } from 'mongoose';

export interface IStock extends Document {
  shopId: mongoose.Types.ObjectId;
  nom: string;
  quantite: number;
  prixAchat: number;
  prixVente: number;
  beneficeNetAttendu?: number;
}

const StockSchema = new Schema<IStock>({
  shopId: { type: Schema.Types.ObjectId, ref: 'Shop', required: true, index: true },
  nom: { type: String, required: true },
  quantite: { type: Number, required: true, min: 0 },
  prixAchat: { type: Number, required: true, min: 0 },
  prixVente: { type: Number, required: true, min: 0 },
  beneficeNetAttendu: { type: Number, default: 0 },
});

export default mongoose.model<IStock>('Stock', StockSchema);
