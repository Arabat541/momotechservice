import { Request, Response } from 'express';
import prisma from '../lib/prisma';

// GET /api/storefront/all — All shops public info
export const getAllShopsPublic = async (_req: Request, res: Response): Promise<void> => {
  try {
    const shops = await prisma.shop.findMany({
      select: { id: true, nom: true, adresse: true, telephone: true },
      orderBy: { id: 'asc' },
    });
    const shopsWithSettings = await Promise.all(
      shops.map(async (shop) => {
        const settings = await prisma.settings.findUnique({ where: { shopId: shop.id } });
        const ci = (settings?.companyInfo as any) || {};
        const warranty = (settings?.warranty as any) || {};
        return {
          _id: shop.id,
          id: shop.id,
          nom: ci.nom || shop.nom,
          adresse: ci.adresse || shop.adresse,
          telephone: ci.telephone || shop.telephone,
          email: ci.email || '',
          slogan: ci.slogan || '',
          logoUrl: ci.logoUrl || '',
          warranty,
        };
      })
    );
    res.json(shopsWithSettings);
  } catch (err) {
    console.error('getAllShopsPublic error:', err);
    res.status(500).json({ error: 'Erreur lors de la récupération des boutiques.' });
  }
};

// GET /api/storefront/:shopId — Public shop info
export const getShopPublicInfo = async (req: Request, res: Response): Promise<void> => {
  try {
    const shop = await prisma.shop.findUnique({
      where: { id: req.params.shopId },
      select: { id: true, nom: true, adresse: true, telephone: true },
    });
    if (!shop) {
      res.status(404).json({ error: 'Boutique introuvable.' });
      return;
    }
    const settings = await prisma.settings.findUnique({ where: { shopId: shop.id } });
    const companyInfo = (settings?.companyInfo as any) || {};
    const warranty = (settings?.warranty as any) || {};

    res.json({
      nom: companyInfo.nom || shop.nom,
      adresse: companyInfo.adresse || shop.adresse,
      telephone: companyInfo.telephone || shop.telephone,
      email: companyInfo.email || '',
      slogan: companyInfo.slogan || '',
      logoUrl: companyInfo.logoUrl || '',
      warranty,
    });
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération des informations.' });
  }
};

// GET /api/storefront/track/:numero — Public repair tracking
export const trackRepair = async (req: Request, res: Response): Promise<void> => {
  try {
    const { numero } = req.params;
    if (!numero || numero.length < 3) {
      res.status(400).json({ error: 'Numéro de réparation invalide.' });
      return;
    }

    const repair = await prisma.repair.findUnique({ where: { numeroReparation: numero } });
    if (!repair) {
      res.status(404).json({ error: 'Réparation introuvable. Vérifiez le numéro.' });
      return;
    }

    // Return only public-safe fields (no client phone, no financial details)
    res.json({
      numeroReparation: repair.numeroReparation,
      appareil: repair.appareil_marque_modele,
      statut: repair.statut_reparation,
      type: repair.type_reparation,
      date_creation: repair.date_creation,
      date_rendez_vous: repair.date_rendez_vous || null,
      date_retrait: repair.date_retrait || null,
    });
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors du suivi de la réparation.' });
  }
};
