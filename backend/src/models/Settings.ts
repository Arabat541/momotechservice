import mongoose, { Schema } from 'mongoose';

const settingsSchema = new Schema({
  companyInfo: {
    nom: { type: String, default: '' },
    adresse: { type: String, default: '' },
    telephone: { type: String, default: '' },
    slogan: { type: String, default: '' },
    email: { type: String, default: '' },
    siret: { type: String, default: '' },
    tva: { type: String, default: '' },
    logoUrl: { type: String, default: '' },
  },
  warranty: {
    duree: { type: String, default: '' },
    conditions: { type: String, default: '' },
  }
}, { timestamps: true });

export default mongoose.models.Settings || mongoose.model('Settings', settingsSchema);
