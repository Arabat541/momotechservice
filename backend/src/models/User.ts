import mongoose, { Document, Schema } from 'mongoose';

export type UserRole = 'employé' | 'patron';

export interface IUser extends Document {
  email: string;
  password: string;
  nom: string;
  prenom: string;
  role: UserRole;
  shops: mongoose.Types.ObjectId[];
}

const UserSchema = new Schema<IUser>({
  email: { type: String, required: true, unique: true },
  password: { type: String, required: true },
  nom: { type: String, required: true },
  prenom: { type: String, required: true },
  role: { type: String, enum: ['employé', 'patron'], default: 'employé' },
  shops: [{ type: Schema.Types.ObjectId, ref: 'Shop' }],
}, { timestamps: true });

export default mongoose.model<IUser>('User', UserSchema);
