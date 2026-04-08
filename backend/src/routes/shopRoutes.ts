import express from 'express';
import {
  getMyShops,
  createShop,
  updateShop,
  deleteShop,
  addUserToShop,
  removeUserFromShop,
} from '../controllers/shopController';
import { authenticateJWT, authorizeRole } from '../middlewares/auth';

const router = express.Router();

// Get shops for current user
router.get('/', authenticateJWT, getMyShops);

// Create a new shop (patron only)
router.post('/', authenticateJWT, authorizeRole(['patron']), createShop);

// Update a shop (patron only)
router.put('/:id', authenticateJWT, authorizeRole(['patron']), updateShop);

// Delete a shop (patron only)
router.delete('/:id', authenticateJWT, authorizeRole(['patron']), deleteShop);

// Add user to shop (patron only)
router.post('/:id/users', authenticateJWT, authorizeRole(['patron']), addUserToShop);

// Remove user from shop (patron only)
router.delete('/:id/users', authenticateJWT, authorizeRole(['patron']), removeUserFromShop);

export default router;
