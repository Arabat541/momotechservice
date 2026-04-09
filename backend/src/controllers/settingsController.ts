import { Request, Response } from 'express';
import prisma from '../lib/prisma';
import { withId } from '../lib/transform';
import { AuthRequest } from '../middlewares/auth';

const defaultCompanyInfo = { nom: '', adresse: '', telephone: '', slogan: '', email: '', siret: '', tva: '', logoUrl: '' };
const defaultWarranty = { duree: '', conditions: '' };

export const getSettings = async (req: Request, res: Response) => {
  try {
    const shopId = (req as AuthRequest).shopId;
    if (!shopId) {
      res.status(400).json({ error: 'Boutique non sélectionnée (shopId manquant).' });
      return;
    }
    let settings = await prisma.settings.findUnique({ where: { shopId } });
    if (!settings) {
      settings = await prisma.settings.create({
        data: { shopId, companyInfo: defaultCompanyInfo, warranty: defaultWarranty },
      });
    }
    res.json(withId(settings));
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération des paramètres.' });
  }
};

export const updateSettings = async (req: Request, res: Response) => {
  try {
    const shopId = (req as AuthRequest).shopId;
    if (!shopId) {
      res.status(400).json({ error: 'Boutique non sélectionnée (shopId manquant).' });
      return;
    }
    const { companyInfo, warranty } = req.body;
    const settings = await prisma.settings.upsert({
      where: { shopId },
      update: { companyInfo, warranty },
      create: { shopId, companyInfo: companyInfo || defaultCompanyInfo, warranty: warranty || defaultWarranty },
    });
    res.json(withId(settings));
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la mise à jour des paramètres.' });
  }
};
