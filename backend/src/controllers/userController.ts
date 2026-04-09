import { Response, NextFunction, Request } from 'express';
import crypto from 'crypto';
import prisma from '../lib/prisma';
import { withId, withIds } from '../lib/transform';
import jwt from 'jsonwebtoken';
import bcrypt from 'bcryptjs';
import { AuthRequest } from '../middlewares/auth';

export const register = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    // Only authenticated patron can create users
    if (!req.user || req.user.role !== 'patron') {
      res.status(403).json({ error: 'Seul le patron peut créer des comptes.' });
      return;
    }
    let { email, password, nom, prenom, role } = req.body as { email: string; password: string; nom: string; prenom: string; role: string };
    email = email.trim().toLowerCase();
    if (password.length < 8) {
      res.status(400).json({ error: 'Le mot de passe doit contenir au moins 8 caractères.' });
      return;
    }
    if (role === 'employe') role = 'employé';
    // Only employees can be created (patron is unique)
    if (role !== 'employé') {
      res.status(400).json({ error: 'Seuls des comptes employés peuvent être créés.' });
      return;
    }
    const existingUser = await prisma.user.findUnique({ where: { email } });
    if (existingUser) {
      res.status(400).json({ error: "L'email existe déjà. Veuillez en choisir un autre." });
      return;
    }
    const hashedPassword = await bcrypt.hash(password, 10);

    // Patron creates employee — optionally assign to their current shop
    const shopId = req.shopId || req.headers['x-shop-id'] as string;
    const user = await prisma.user.create({
      data: {
        email, password: hashedPassword, nom, prenom, role,
        ...(shopId ? { shops: { connect: { id: shopId } } } : {}),
      },
      include: { shops: true },
    });
    const { password: _pw, ...userSafe } = user;
    res.status(201).json({ message: 'Utilisateur créé', user: withId(userSafe as any) });
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(400).json({ error: error.message });
  }
};

export const login = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    let { email, password } = req.body as { email: string; password: string };
    email = email.trim().toLowerCase();
    const user = await prisma.user.findUnique({ where: { email } });
    if (!user) {
      res.status(401).json({ error: 'Utilisateur non trouvé' });
      return;
    }
    const isMatch = await bcrypt.compare(password, user.password);
    if (!isMatch) {
      res.status(401).json({ error: 'Mot de passe incorrect' });
      return;
    }
    const { password: _pw, ...userSafe } = user;
    const token = jwt.sign({ id: userSafe.id, role: userSafe.role }, process.env.JWT_SECRET as string, { expiresIn: '1d' });
    res.json({ token, user: withId(userSafe as any) });
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(400).json({ error: error.message });
  }
};

export const getProfile = async (req: Request, res: Response, next: NextFunction): Promise<void> => {
  try {
    const userId = (req as AuthRequest).user?.id;
    const found = await prisma.user.findUnique({
      where: { id: userId },
      include: { shops: true },
    });
    if (!found) {
      res.status(404).json({ error: 'Utilisateur non trouvé' });
      return;
    }
    const { password: _pw, ...user } = found;
    res.json(withId(user as any));
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
    const updated = await prisma.user.update({
      where: { id: userId },
      data: updates,
    });
    const { password: _pw, ...user } = updated;
    res.json(withId(user as any));
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(400).json({ error: error.message });
  }
};

// Get all users (patron only) — patron sees ALL users across all shops
export const getAllUsers = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    const allUsers = await prisma.user.findMany({ include: { shops: true } });
    const users = allUsers.map(({ password: _pw, ...u }) => u);
    res.json(withIds(users as any[]));
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
    if (req.user?.id === userId) {
      res.status(403).json({ error: 'Impossible de supprimer votre propre compte.' });
      return;
    }
    await prisma.user.delete({ where: { id: userId } });
    res.json({ message: 'Utilisateur supprimé' });
  } catch (err: any) {
    if (err.code === 'P2025') {
      res.status(404).json({ error: 'Utilisateur non trouvé' });
      return;
    }
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
    const result = await prisma.user.update({
      where: { id: userId },
      data: { role },
    });
    const { password: _pw, ...updated } = result;
    res.json(withId(updated as any));
  } catch (err: any) {
    if (err.code === 'P2025') {
      res.status(404).json({ error: 'Utilisateur non trouvé' });
      return;
    }
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
    const user = await prisma.user.findUnique({ where: { email: email.trim().toLowerCase() } });
    if (!user) {
      res.json({ message: 'Si cet email existe, un code de réinitialisation a été généré.' });
      return;
    }
    // Delete any existing tokens for this user
    await prisma.passwordResetToken.deleteMany({ where: { userId: user.id } });
    // Generate a 6-digit code
    const code = crypto.randomInt(100000, 999999).toString();
    const hashedToken = await bcrypt.hash(code, 10);
    await prisma.passwordResetToken.create({
      data: {
        userId: user.id,
        token: hashedToken,
        expiresAt: new Date(Date.now() + 15 * 60 * 1000),
      },
    });
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
    const user = await prisma.user.findUnique({ where: { email: email.trim().toLowerCase() } });
    if (!user) {
      res.status(400).json({ error: 'Code invalide ou expiré.' });
      return;
    }
    const resetToken = await prisma.passwordResetToken.findFirst({ where: { userId: user.id } });
    if (!resetToken || resetToken.expiresAt < new Date()) {
      res.status(400).json({ error: 'Code invalide ou expiré.' });
      return;
    }
    const isValid = await bcrypt.compare(code, resetToken.token);
    if (!isValid) {
      res.status(400).json({ error: 'Code invalide ou expiré.' });
      return;
    }
    const hashedPassword = await bcrypt.hash(password, 10);
    await prisma.user.update({ where: { id: user.id }, data: { password: hashedPassword } });
    await prisma.passwordResetToken.deleteMany({ where: { userId: user.id } });
    res.json({ message: 'Mot de passe réinitialisé avec succès.' });
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la réinitialisation du mot de passe.' });
  }
};
