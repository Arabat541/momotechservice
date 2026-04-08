// Point d'entrée principal du backend CRM
import express from 'express';
import cors from 'cors';
import helmet from 'helmet';
import rateLimit from 'express-rate-limit';
import mongoose from 'mongoose';
import dotenv from 'dotenv';

import userRoutes from './routes/userRoutes';
import exportRoutes from './routes/exportRoutes';
import repairRoutes from './routes/repairRoutes';
import stockRoutes from './routes/stockRoutes';
import settingsRoutes from './routes/settingsRoutes';
import shopRoutes from './routes/shopRoutes';
import storefrontRoutes from './routes/storefrontRoutes';
import savRoutes from './routes/savRoutes';
import { logger } from './utils/logger';

dotenv.config();

const app = express();

// Security headers
app.use(helmet());

// Configuration CORS
const corsOrigin = process.env.CORS_ORIGIN || 'http://localhost:5173';
app.use(cors({
  origin: corsOrigin.split(',').map(o => o.trim()),
  credentials: true
}));

app.use(express.json({ limit: '1mb' }));

// Rate limiting on auth endpoints
const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 20,
  message: { error: 'Trop de tentatives, réessayez dans 15 minutes.' },
  standardHeaders: true,
  legacyHeaders: false,
});
app.use('/api/users/login', authLimiter);
app.use('/api/users/register', authLimiter);
app.use('/api/users/reset-password', authLimiter);
app.use('/api/users/confirm-reset-password', authLimiter);

// Connexion MongoDB
mongoose.connect(process.env.MONGODB_URI as string)
  .then(() => logger.info('MongoDB connecté'))
  .catch((err) => logger.error('Erreur MongoDB', { error: String(err) }));

// Routes principales
app.use('/api/users', userRoutes);
app.use('/api/export', exportRoutes);
app.use('/api/repairs', repairRoutes);
app.use('/api/stocks', stockRoutes);
app.use('/api/settings', settingsRoutes);
app.use('/api/shops', shopRoutes);
app.use('/api/storefront', storefrontRoutes);
app.use('/api/sav', savRoutes);

// Global error handler
app.use((err: any, _req: express.Request, res: express.Response, _next: express.NextFunction) => {
  logger.error('Unhandled error', { error: err.message || String(err), stack: err.stack });
  res.status(err.status || 500).json({ error: 'Erreur serveur interne.' });
});

const PORT = process.env.PORT || 5000;
app.listen(PORT, () => {
  logger.info(`Serveur démarré sur le port ${PORT}`);
});
