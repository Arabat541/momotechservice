import express from 'express';
import {
  getAllRepairs,
  getRepairById,
  createRepair,
  updateRepair,
  deleteRepair
} from '../controllers/repairController';
import { authenticateJWT } from '../middlewares/auth';

const router = express.Router();

router.get('/', authenticateJWT, getAllRepairs);
router.get('/:id', authenticateJWT, getRepairById);
router.post('/', authenticateJWT, createRepair);
router.put('/:id', authenticateJWT, updateRepair);
router.delete('/:id', authenticateJWT, deleteRepair);

export default router;
