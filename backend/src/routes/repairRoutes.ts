import express from 'express';
import {
  getAllRepairs,
  getRepairById,
  createRepair,
  updateRepair,
  deleteRepair
} from '../controllers/repairController';
import { authenticateJWT, requireShop } from '../middlewares/auth';
import { validate } from '../middlewares/validate';
import { createRepairSchema } from '../validators/schemas';

const router = express.Router();

router.get('/', authenticateJWT, requireShop, getAllRepairs);
router.get('/:id', authenticateJWT, requireShop, getRepairById);
router.post('/', authenticateJWT, requireShop, validate(createRepairSchema), createRepair);
router.put('/:id', authenticateJWT, requireShop, updateRepair);
router.delete('/:id', authenticateJWT, requireShop, deleteRepair);

export default router;
