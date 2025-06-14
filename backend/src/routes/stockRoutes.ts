import express from 'express';
import {
  getAllStocks,
  getStockById,
  createStock,
  updateStock,
  deleteStock
} from '../controllers/stockController';
import { authenticateJWT } from '../middlewares/auth';

const router = express.Router();

router.get('/', authenticateJWT, getAllStocks);
router.get('/:id', authenticateJWT, getStockById);
router.post('/', authenticateJWT, createStock);
router.put('/:id', authenticateJWT, updateStock);
router.delete('/:id', authenticateJWT, deleteStock);

export default router;
