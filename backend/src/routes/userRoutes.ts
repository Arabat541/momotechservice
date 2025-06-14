import express from 'express';
import { register, login, getProfile, updateProfile, getAllUsers, deleteUser, updateUserRole, resetPassword } from '../controllers/userController';
import { authenticateJWT, authorizeRole } from '../middlewares/auth';

const router = express.Router();

router.post('/register', register);
router.post('/login', login);
router.get('/me', authenticateJWT, getProfile);
router.put('/me', authenticateJWT, updateProfile);
router.post('/reset-password', resetPassword);

// Patron-only user management
router.get('/users', authenticateJWT, authorizeRole(['patron']), getAllUsers);
router.delete('/users/:id', authenticateJWT, authorizeRole(['patron']), deleteUser);
router.put('/users/:id/role', authenticateJWT, authorizeRole(['patron']), updateUserRole);

export default router;
