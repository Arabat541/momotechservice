import { Request, Response } from 'express';
import Shop from '../models/Shop';
import Settings from '../models/Settings';
import Repair from '../models/Repair';

// GET /api/storefront/:shopId — Public shop info
export const getShopPublicInfo = async (req: Request, res: Response): Promise<void> => {
  try {
    const shop = await Shop.findById(req.params.shopId).select('nom adresse telephone');
    if (!shop) {
      res.status(404).json({ error: 'Boutique introuvable.' });
      return;
    }
    const settings = await Settings.findOne({ shopId: shop._id });
    const companyInfo = settings?.companyInfo || {};

    res.json({
      nom: companyInfo.nom || shop.nom,
      adresse: companyInfo.adresse || shop.adresse,
      telephone: companyInfo.telephone || shop.telephone,
      email: companyInfo.email || '',
      slogan: companyInfo.slogan || '',
      logoUrl: companyInfo.logoUrl || '',
      warranty: settings?.warranty || {},
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

    const repair = await Repair.findOne({ numeroReparation: numero });
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
