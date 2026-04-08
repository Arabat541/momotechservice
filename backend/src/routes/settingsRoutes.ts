import express from 'express';
import { getSettings, updateSettings } from '../controllers/settingsController';
import { authenticateJWT, requireShop } from '../middlewares/auth';

const router = express.Router();

// Récupérer les paramètres de la boutique
router.get('/', authenticateJWT, requireShop, getSettings);
// Mettre à jour les paramètres de la boutique
router.put('/', authenticateJWT, requireShop, updateSettings);

export default router;
