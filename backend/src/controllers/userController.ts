import { Response, NextFunction, Request } from 'express';
import User from '../models/User';
import jwt from 'jsonwebtoken';
import bcrypt from 'bcryptjs';
import { AuthRequest } from '../middlewares/auth';

export const register = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    let { email, password, nom, prenom, role } = req.body as { email: string; password: string; nom: string; prenom: string; role: string };
    // Normalisation de l'email
    email = email.trim().toLowerCase();
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
    const user = new User({ email, password: hashedPassword, nom, prenom, role });
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
    // On récupère l'utilisateur AVEC le password pour le check
    const userWithPassword = await User.findOne({ email });
    if (!userWithPassword) {
      res.status(401).json({ error: 'Utilisateur non trouvé' });
      return;
    }
    const isMatch = await bcrypt.compare(password, userWithPassword.password);
    if (!isMatch) {
      res.status(401).json({ error: 'Mot de passe incorrect' });
      return;
    }
    // On récupère l'utilisateur SANS le password pour la réponse
    const userDoc = await User.findOne({ email }).select('-password');
    const userObj = userDoc ? userDoc.toObject() : null;
    if (!userObj) {
      res.status(401).json({ error: 'Utilisateur non trouvé' });
      return;
    }
    const { password: _pw, ...userSafe } = userObj;
    console.log('LOGIN: userSafe renvoyé =', userSafe);
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
    const user = await User.findById(userId).select('-password');
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

// Get all users (patron only)
export const getAllUsers = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    const users = await User.find().select('-password');
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
    const { email, password } = req.body;
    if (!email || !password) {
      res.status(400).json({ error: 'Email et nouveau mot de passe requis.' });
      return;
    }
    const user = await User.findOne({ email });
    if (!user) {
      res.status(404).json({ error: 'Utilisateur non trouvé.' });
      return;
    }
    user.password = await bcrypt.hash(password, 10);
    await user.save();
    res.json({ message: 'Mot de passe réinitialisé avec succès.' });
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la réinitialisation du mot de passe.' });
  }
};
