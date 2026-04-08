import { Response, NextFunction, Request } from 'express';
import crypto from 'crypto';
import User from '../models/User';
import Shop from '../models/Shop';
import Settings from '../models/Settings';
import PasswordResetToken from '../models/PasswordResetToken';
import jwt from 'jsonwebtoken';
import bcrypt from 'bcryptjs';
import { AuthRequest } from '../middlewares/auth';

export const register = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    let { email, password, nom, prenom, role } = req.body as { email: string; password: string; nom: string; prenom: string; role: string };
    // Normalisation de l'email
    email = email.trim().toLowerCase();
    if (password.length < 8) {
      res.status(400).json({ error: 'Le mot de passe doit contenir au moins 8 caractères.' });
      return;
    }
    // Normalisation du rôle (accepte employe ou employé)
    if (role === 'employe') role = 'employé';
    if (!['employé', 'patron'].includes(role)) {
      res.status(400).json({ error: 'Rôle invalide' });
      return;
    }
    // Vérifier si l'email existe déjà
    const existingUser = await User.findOne({ email });
    if (existingUser) {
      res.status(400).json({ error: "L'email existe déjà. Veuillez en choisir un autre." });
      return;
    }
    const hashedPassword = await bcrypt.hash(password, 10);
    const user = new User({ email, password: hashedPassword, nom, prenom, role, shops: [] });

    // If patron registers (self-signup), create a default shop
    // If created by another patron (req.user exists), assign to the patron's current shop
    if (req.user) {
      // Created by an authenticated patron — assign to their current shop
      const shopId = req.shopId || req.headers['x-shop-id'] as string;
      if (shopId) {
        user.shops = [shopId as any];
      }
    } else if (role === 'patron') {
      // Self-registration as patron — create a default shop
      const defaultShop = new Shop({
        nom: `Boutique de ${prenom} ${nom}`,
        adresse: '',
        telephone: '',
        createdBy: user._id,
      });
      await defaultShop.save();
      user.shops = [defaultShop._id as any];
      // Create default settings for the shop
      await Settings.create({ shopId: defaultShop._id });
    }

    await user.save();
    // Générer un token JWT comme pour le login
    const token = jwt.sign({ id: user._id, role: user.role }, process.env.JWT_SECRET as string, { expiresIn: '1d' });
    // Supprimer le champ password de l'objet utilisateur retourné (TypeScript safe)
    const { password: _pw, ...userSafe } = user.toObject();
    res.status(201).json({ message: 'Utilisateur créé', token, user: userSafe });
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(400).json({ error: error.message });
  }
};

export const login = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    let { email, password } = req.body as { email: string; password: string };
    // Normalisation de l'email
    email = email.trim().toLowerCase();
    const user = await User.findOne({ email });
    if (!user) {
      res.status(401).json({ error: 'Utilisateur non trouvé' });
      return;
    }
    const isMatch = await bcrypt.compare(password, user.password);
    if (!isMatch) {
      res.status(401).json({ error: 'Mot de passe incorrect' });
      return;
    }
    const { password: _pw, ...userSafe } = user.toObject();
    const token = jwt.sign({ id: userSafe._id, role: userSafe.role }, process.env.JWT_SECRET as string, { expiresIn: '1d' });
    res.json({ token, user: userSafe });
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(400).json({ error: error.message });
  }
};

export const getProfile = async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    // Cast req as AuthRequest to access req.user
    const userId = (req as AuthRequest).user?.id;
    const user = await User.findById(userId).select('-password').populate('shops');
    res.json(user);
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(400).json({ error: error.message });
  }
};

export const updateProfile = async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const updates = req.body as Partial<{ nom: string; prenom: string; email: string; password: string }>;

    if (updates.password) {
      updates.password = await bcrypt.hash(updates.password, 10);
    }

    const userId = (req as AuthRequest).user?.id;
    const user = await User.findByIdAndUpdate(userId, updates, { new: true }).select('-password');
    res.json(user);
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(400).json({ error: error.message });
  }
};

// Get all users (patron only) — filtered by current shop
export const getAllUsers = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    const shopId = req.shopId;
    let filter = {};
    if (shopId) {
      filter = { shops: shopId };
    }
    const users = await User.find(filter).select('-password');
    res.json(users);
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(400).json({ error: error.message });
  }
};

// Delete user by id (patron only)
export const deleteUser = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    const userId = req.params.id;
    if (!userId) {
      res.status(400).json({ error: 'ID utilisateur manquant' });
      return;
    }
    // Prevent self-delete
    if (req.user?.id === userId) {
      res.status(403).json({ error: 'Impossible de supprimer votre propre compte.' });
      return;
    }
    const deleted = await User.findByIdAndDelete(userId);
    if (!deleted) {
      res.status(404).json({ error: 'Utilisateur non trouvé' });
      return;
    }
    res.json({ message: 'Utilisateur supprimé' });
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(400).json({ error: error.message });
  }
};

// Update user role (patron only)
export const updateUserRole = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    const userId = req.params.id;
    const { role } = req.body;
    if (!userId || !role) {
      res.status(400).json({ error: 'ID utilisateur ou rôle manquant' });
      return;
    }
    if (!['employé', 'patron'].includes(role)) {
      res.status(400).json({ error: 'Rôle invalide' });
      return;
    }
    const updated = await User.findByIdAndUpdate(userId, { role }, { new: true }).select('-password');
    if (!updated) {
      res.status(404).json({ error: 'Utilisateur non trouvé' });
      return;
    }
    res.json(updated);
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(400).json({ error: error.message });
  }
};

export const resetPassword = async (req: Request, res: Response): Promise<void> => {
  try {
    const { email } = req.body;
    if (!email) {
      res.status(400).json({ error: 'Email requis.' });
      return;
    }
    const user = await User.findOne({ email: email.trim().toLowerCase() });
    if (!user) {
      // Return success even if user not found (prevent email enumeration)
      res.json({ message: 'Si cet email existe, un code de réinitialisation a été généré.' });
      return;
    }
    // Delete any existing tokens for this user
    await PasswordResetToken.deleteMany({ userId: user._id });
    // Generate a 6-digit code
    const code = crypto.randomInt(100000, 999999).toString();
    const hashedToken = await bcrypt.hash(code, 10);
    await PasswordResetToken.create({
      userId: user._id,
      token: hashedToken,
      expiresAt: new Date(Date.now() + 15 * 60 * 1000), // 15 minutes
    });
    // In production, send code via email. For now, log it.
    console.log(`[RESET] Code de réinitialisation pour ${email}: ${code}`);
    res.json({ message: 'Si cet email existe, un code de réinitialisation a été généré.' });
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la demande de réinitialisation.' });
  }
};

export const confirmResetPassword = async (req: Request, res: Response): Promise<void> => {
  try {
    const { email, code, password } = req.body;
    if (!email || !code || !password) {
      res.status(400).json({ error: 'Email, code et nouveau mot de passe requis.' });
      return;
    }
    if (password.length < 8) {
      res.status(400).json({ error: 'Le mot de passe doit contenir au moins 8 caractères.' });
      return;
    }
    const user = await User.findOne({ email: email.trim().toLowerCase() });
    if (!user) {
      res.status(400).json({ error: 'Code invalide ou expiré.' });
      return;
    }
    const resetToken = await PasswordResetToken.findOne({ userId: user._id });
    if (!resetToken || resetToken.expiresAt < new Date()) {
      res.status(400).json({ error: 'Code invalide ou expiré.' });
      return;
    }
    const isValid = await bcrypt.compare(code, resetToken.token);
    if (!isValid) {
      res.status(400).json({ error: 'Code invalide ou expiré.' });
      return;
    }
    user.password = await bcrypt.hash(password, 10);
    await user.save();
    await PasswordResetToken.deleteMany({ userId: user._id });
    res.json({ message: 'Mot de passe réinitialisé avec succès.' });
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la réinitialisation du mot de passe.' });
  }
};
