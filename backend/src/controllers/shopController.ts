import { Response } from 'express';
import prisma from '../lib/prisma';
import { withId, withIds } from '../lib/transform';
import { AuthRequest } from '../middlewares/auth';

// Get all shops the current user has access to
export const getMyShops = async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const user = await prisma.user.findUnique({
      where: { id: req.user?.id },
      include: { shops: true },
    });
    if (!user) {
      res.status(404).json({ error: 'Utilisateur non trouvé' });
      return;
    }
    res.json(withIds(user.shops));
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération des boutiques.' });
  }
};

// Create a new shop (patron only)
export const createShop = async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const { nom, adresse, telephone } = req.body;
    if (!nom) {
      res.status(400).json({ error: 'Le nom de la boutique est requis.' });
      return;
    }
    const shop = await prisma.$transaction(async (tx) => {
      const newShop = await tx.shop.create({
        data: {
          nom,
          adresse: adresse || '',
          telephone: telephone || '',
          createdBy: req.user?.id || '',
          users: { connect: { id: req.user?.id } },
        },
      });
      // Create default settings for the new shop
      await tx.settings.create({
        data: {
          shopId: newShop.id,
          companyInfo: { nom: '', adresse: '', telephone: '', slogan: '', email: '', siret: '', tva: '', logoUrl: '' },
          warranty: { duree: '', conditions: '' },
        },
      });
      return newShop;
    });
    res.status(201).json(withId(shop));
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la création de la boutique.' });
  }
};

// Update a shop (patron only)
export const updateShop = async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const shop = await prisma.shop.update({
      where: { id: req.params.id },
      data: req.body,
    });
    res.json(withId(shop));
  } catch (err: any) {
    if (err.code === 'P2025') {
      res.status(404).json({ error: 'Boutique non trouvée.' });
      return;
    }
    res.status(400).json({ error: 'Erreur lors de la mise à jour de la boutique.' });
  }
};

// Delete a shop (patron only)
export const deleteShop = async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const shopId = req.params.id;
    await prisma.$transaction(async (tx) => {
      // Delete related data first
      await tx.sAV.deleteMany({ where: { shopId } });
      await tx.repair.deleteMany({ where: { shopId } });
      await tx.stock.deleteMany({ where: { shopId } });
      await tx.settings.deleteMany({ where: { shopId } });
      // Delete the shop (this also removes the many-to-many links)
      await tx.shop.delete({ where: { id: shopId } });
    });
    res.json({ message: 'Boutique supprimée.' });
  } catch (err: any) {
    if (err.code === 'P2025') {
      res.status(404).json({ error: 'Boutique non trouvée.' });
      return;
    }
    res.status(500).json({ error: 'Erreur lors de la suppression de la boutique.' });
  }
};

// Add a user to a shop (patron only)
export const addUserToShop = async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const { userId } = req.body;
    const { id: shopId } = req.params;
    if (!userId) {
      res.status(400).json({ error: 'userId requis.' });
      return;
    }
    await prisma.shop.update({
      where: { id: shopId },
      data: { users: { connect: { id: userId } } },
    });
    res.json({ message: 'Utilisateur ajouté à la boutique.' });
  } catch (err: any) {
    if (err.code === 'P2025') {
      res.status(404).json({ error: 'Boutique non trouvée.' });
      return;
    }
    res.status(400).json({ error: 'Erreur lors de l\'ajout de l\'utilisateur à la boutique.' });
  }
};

// Remove a user from a shop (patron only)
export const removeUserFromShop = async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const { userId } = req.body;
    const { id: shopId } = req.params;
    if (!userId) {
      res.status(400).json({ error: 'userId requis.' });
      return;
    }
    await prisma.shop.update({
      where: { id: shopId },
      data: { users: { disconnect: { id: userId } } },
    });
    res.json({ message: 'Utilisateur retiré de la boutique.' });
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors du retrait de l\'utilisateur de la boutique.' });
  }
};
