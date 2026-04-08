import express from 'express';
import {
  getAllStocks,
  getStockById,
  createStock,
  updateStock,
  deleteStock
} from '../controllers/stockController';
import { authenticateJWT, requireShop } from '../middlewares/auth';
import { validate } from '../middlewares/validate';
import { createStockSchema } from '../validators/schemas';

const router = express.Router();

router.get('/', authenticateJWT, requireShop, getAllStocks);
router.get('/:id', authenticateJWT, requireShop, getStockById);
router.post('/', authenticateJWT, requireShop, validate(createStockSchema), createStock);
router.put('/:id', authenticateJWT, requireShop, updateStock);
router.delete('/:id', authenticateJWT, requireShop, deleteStock);

export default router;
