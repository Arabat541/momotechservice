// Point d'entrée principal du backend CRM
import express from 'express';
import cors from 'cors';
import mongoose from 'mongoose';
import dotenv from 'dotenv';

import userRoutes from './routes/userRoutes';
import exportRoutes from './routes/exportRoutes'; // Import des routes d'export CSV/PDF
import repairRoutes from './routes/repairRoutes';
import stockRoutes from './routes/stockRoutes';
import settingsRoutes from './routes/settingsRoutes'; // Import des routes de paramètres

dotenv.config();
console.log('MONGODB_URI utilisé par le backend:', process.env.MONGODB_URI);

const app = express();

// Configuration CORS pour Netlify
app.use(cors({
  origin: 'https://momotechservice.com',
  credentials: true
}));

app.use(express.json());



// Connexion MongoDB Atlas
mongoose.connect(process.env.MONGODB_URI as string)
  .then(() => console.log('MongoDB connecté'))
  .catch((err) => console.error('Erreur MongoDB:', err));

// Routes principales
app.use('/api/users', userRoutes);
app.use('/api/export', exportRoutes); // Montage des routes d'export
app.use('/api/repairs', repairRoutes);
app.use('/api/stocks', stockRoutes);
app.use('/api/settings', settingsRoutes); // Montage des routes de paramètres

// [FORCE REBUILD] 2025-06-12: Ensure repairs and stocks routes are included in build

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
  console.log(`Serveur backend CRM démarré sur le port ${PORT}`);
});
