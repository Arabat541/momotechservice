import express from 'express';
import {
  getAllSAV,
  getSAVById,
  createSAV,
  updateSAV,
  deleteSAV,
  lookupRepair,
} from '../controllers/savController';
import { authenticateJWT, requireShop } from '../middlewares/auth';

const router = express.Router();

router.get('/', authenticateJWT, requireShop, getAllSAV);
router.get('/lookup/:numero', authenticateJWT, requireShop, lookupRepair);
router.get('/:id', authenticateJWT, requireShop, getSAVById);
router.post('/', authenticateJWT, requireShop, createSAV);
router.put('/:id', authenticateJWT, requireShop, updateSAV);
router.delete('/:id', authenticateJWT, requireShop, deleteSAV);

export default router;
