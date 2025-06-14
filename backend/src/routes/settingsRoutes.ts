import express from 'express';
import { getSettings, updateSettings } from '../controllers/settingsController';
import { authenticateJWT } from '../middlewares/auth';

const router = express.Router();

// Récupérer les paramètres globaux
router.get('/', authenticateJWT, getSettings);
// Mettre à jour les paramètres globaux
router.put('/', authenticateJWT, updateSettings);

export default router;
