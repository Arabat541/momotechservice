import { Response } from 'express';
import Shop from '../models/Shop';
import User from '../models/User';
import Settings from '../models/Settings';
import Repair from '../models/Repair';
import Stock from '../models/Stock';
import { AuthRequest } from '../middlewares/auth';

// Get all shops the current user has access to
export const getMyShops = async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const user = await User.findById(req.user?.id).populate('shops');
    if (!user) {
      res.status(404).json({ error: 'Utilisateur non trouvé' });
      return;
    }
    res.json(user.shops);
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
    const shop = new Shop({
      nom,
      adresse: adresse || '',
      telephone: telephone || '',
      createdBy: req.user?.id,
    });
    await shop.save();

    // Add shop to creator's shops list
    await User.findByIdAndUpdate(req.user?.id, { $addToSet: { shops: shop._id } });

    // Create default settings for the new shop
    await Settings.create({ shopId: shop._id });

    res.status(201).json(shop);
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la création de la boutique.' });
  }
};

// Update a shop (patron only)
export const updateShop = async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const shop = await Shop.findByIdAndUpdate(req.params.id, req.body, { new: true });
    if (!shop) {
      res.status(404).json({ error: 'Boutique non trouvée.' });
      return;
    }
    res.json(shop);
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la mise à jour de la boutique.' });
  }
};

// Delete a shop (patron only)
export const deleteShop = async (req: AuthRequest, res: Response): Promise<void> => {
  try {
    const shop = await Shop.findByIdAndDelete(req.params.id);
    if (!shop) {
      res.status(404).json({ error: 'Boutique non trouvée.' });
      return;
    }
    // Remove shop from all users
    await User.updateMany({}, { $pull: { shops: shop._id } });
    // Delete settings, repairs and stocks for this shop
    await Settings.deleteMany({ shopId: shop._id });
    await Repair.deleteMany({ shopId: shop._id });
    await Stock.deleteMany({ shopId: shop._id });
    res.json({ message: 'Boutique supprimée.' });
  } catch (err) {
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
    const shop = await Shop.findById(shopId);
    if (!shop) {
      res.status(404).json({ error: 'Boutique non trouvée.' });
      return;
    }
    await User.findByIdAndUpdate(userId, { $addToSet: { shops: shopId } });
    res.json({ message: 'Utilisateur ajouté à la boutique.' });
  } catch (err) {
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
    await User.findByIdAndUpdate(userId, { $pull: { shops: shopId } });
    res.json({ message: 'Utilisateur retiré de la boutique.' });
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors du retrait de l\'utilisateur de la boutique.' });
  }
};
