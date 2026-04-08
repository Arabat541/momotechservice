import Settings from '../models/Settings';
import { Request, Response } from 'express';
import { AuthRequest } from '../middlewares/auth';

export const getSettings = async (req: Request, res: Response) => {
  try {
    const shopId = (req as AuthRequest).shopId;
    const filter = shopId ? { shopId } : {};
    let settings = await Settings.findOne(filter);
    if (!settings && shopId) {
      settings = await Settings.create({ shopId });
    } else if (!settings) {
      res.status(400).json({ error: 'Boutique non sélectionnée (shopId manquant).' });
      return;
    }
    res.json(settings);
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération des paramètres.' });
  }
};

export const updateSettings = async (req: Request, res: Response) => {
  try {
    const shopId = (req as AuthRequest).shopId;
    const { companyInfo, warranty } = req.body;
    const filter = shopId ? { shopId } : {};
    let settings = await Settings.findOne(filter);
    if (!settings) {
      settings = await Settings.create({ shopId, companyInfo, warranty });
    } else {
      settings.companyInfo = companyInfo;
      settings.warranty = warranty;
      await settings.save();
    }
    res.json(settings);
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la mise à jour des paramètres.' });
  }
};
