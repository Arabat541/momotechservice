import mongoose, { Document, Schema } from 'mongoose';

export interface IShop extends Document {
  nom: string;
  adresse: string;
  telephone: string;
  createdBy: mongoose.Types.ObjectId;
}

const ShopSchema = new Schema<IShop>({
  nom: { type: String, required: true },
  adresse: { type: String, default: '' },
  telephone: { type: String, default: '' },
  createdBy: { type: Schema.Types.ObjectId, ref: 'User', required: true },
}, { timestamps: true });

export default mongoose.model<IShop>('Shop', ShopSchema);
