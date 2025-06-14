import express from 'express';
import { exportUsersCSV, exportUsersPDF } from '../controllers/exportController';
import { authenticateJWT } from '../middlewares/auth';

const router = express.Router();

// Endpoints d'export (protégés)
router.get('/users/csv', authenticateJWT, exportUsersCSV);
router.get('/users/pdf', authenticateJWT, exportUsersPDF);
// Ajouter d'autres exports (clients, réparations, etc.)

export default router;
