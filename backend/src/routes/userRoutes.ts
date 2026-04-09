import express from 'express';
import { register, login, getProfile, updateProfile, getAllUsers, deleteUser, updateUserRole, resetPassword, confirmResetPassword } from '../controllers/userController';
import { authenticateJWT, authorizeRole } from '../middlewares/auth';
import { validate } from '../middlewares/validate';
import { registerSchema, loginSchema, resetPasswordSchema, confirmResetSchema } from '../validators/schemas';

const router = express.Router();

router.post('/register', authenticateJWT, validate(registerSchema), register);
router.post('/login', validate(loginSchema), login);
router.get('/me', authenticateJWT, getProfile);
router.put('/me', authenticateJWT, updateProfile);
router.post('/reset-password', validate(resetPasswordSchema), resetPassword);
router.post('/confirm-reset-password', validate(confirmResetSchema), confirmResetPassword);

// Patron-only user management
router.get('/users', authenticateJWT, authorizeRole(['patron']), getAllUsers);
router.delete('/users/:id', authenticateJWT, authorizeRole(['patron']), deleteUser);
router.put('/users/:id/role', authenticateJWT, authorizeRole(['patron']), updateUserRole);

export default router;
