import { Request, Response } from 'express';
import SAV from '../models/SAV';
import Repair from '../models/Repair';
import Settings from '../models/Settings';
import { AuthRequest } from '../middlewares/auth';

/**
 * Parse warranty duration string (e.g. "3 mois", "1 an", "90 jours") into milliseconds.
 * Returns 0 if the format is unrecognized.
 */
function parseWarrantyDuration(duree: string): number {
  if (!duree || !duree.trim()) return 0;
  const cleaned = duree.trim().toLowerCase();
  const match = cleaned.match(/^(\d+)\s*(mois|an|ans|jour|jours|semaine|semaines)$/);
  if (!match) {
    // Try pure number → default to months
    const num = parseInt(cleaned, 10);
    if (!isNaN(num) && num > 0) return num * 30 * 24 * 60 * 60 * 1000;
    return 0;
  }
  const value = parseInt(match[1], 10);
  const unit = match[2];
  switch (unit) {
    case 'mois': return value * 30 * 24 * 60 * 60 * 1000;
    case 'an': case 'ans': return value * 365 * 24 * 60 * 60 * 1000;
    case 'jour': case 'jours': return value * 24 * 60 * 60 * 1000;
    case 'semaine': case 'semaines': return value * 7 * 24 * 60 * 60 * 1000;
    default: return 0;
  }
}

/**
 * Compute warranty expiration date and whether it's still valid.
 */
function computeWarrantyStatus(dateRetrait: Date | undefined | null, warrantyDurationMs: number): { sous_garantie: boolean; date_fin_garantie: Date | null } {
  if (!dateRetrait || warrantyDurationMs <= 0) {
    return { sous_garantie: false, date_fin_garantie: null };
  }
  const expirationDate = new Date(new Date(dateRetrait).getTime() + warrantyDurationMs);
  return {
    sous_garantie: expirationDate > new Date(),
    date_fin_garantie: expirationDate,
  };
}

export const getAllSAV = async (req: Request, res: Response): Promise<void> => {
  try {
    const shopId = (req as AuthRequest).shopId;
    const filter = shopId ? { shopId } : {};
    const page = parseInt(req.query.page as string);
    const limit = parseInt(req.query.limit as string);

    if (page > 0 && limit > 0) {
      const skip = (page - 1) * limit;
      const [items, total] = await Promise.all([
        SAV.find(filter).sort({ date_creation: -1 }).skip(skip).limit(limit),
        SAV.countDocuments(filter),
      ]);
      res.json({ data: items, total, page, totalPages: Math.ceil(total / limit) });
    } else {
      const items = await SAV.find(filter).sort({ date_creation: -1 });
      res.json(items);
    }
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération des SAV.' });
  }
};

export const getSAVById = async (req: Request, res: Response): Promise<void> => {
  try {
    const sav = await SAV.findById(req.params.id);
    if (!sav) {
      res.status(404).json({ error: 'SAV non trouvé.' });
      return;
    }
    res.json(sav);
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération du SAV.' });
  }
};

export const createSAV = async (req: Request, res: Response): Promise<void> => {
  try {
    const shopId = (req as AuthRequest).shopId;
    const savData = { ...req.body };
    if (shopId) {
      savData.shopId = shopId;
    }

    // Si un numéro de réparation d'origine est fourni, on lie la réparation
    if (savData.numeroReparationOrigine) {
      const repair = await Repair.findOne({ numeroReparation: savData.numeroReparationOrigine });
      if (repair) {
        savData.repairId = repair._id;
        // Pré-remplir depuis la réparation si pas déjà fourni
        if (!savData.client_nom) savData.client_nom = repair.client_nom;
        if (!savData.client_telephone) savData.client_telephone = repair.client_telephone;
        if (!savData.appareil_marque_modele) savData.appareil_marque_modele = repair.appareil_marque_modele;

        // Calcul du statut de garantie
        const settings = await Settings.findOne({ shopId });
        const warrantyMs = parseWarrantyDuration(settings?.warranty?.duree || '');
        const { sous_garantie, date_fin_garantie } = computeWarrantyStatus(repair.date_retrait, warrantyMs);
        savData.sous_garantie = sous_garantie;
        if (date_fin_garantie) savData.date_fin_garantie = date_fin_garantie;
      }
    }

    const sav = new SAV(savData);
    await sav.save();
    res.status(201).json(sav);
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la création du SAV.' });
  }
};

export const updateSAV = async (req: Request, res: Response): Promise<void> => {
  try {
    const update = { ...req.body };
    // Si le statut passe à Résolu ou Refusé, on met la date de résolution
    if ((update.statut === 'Résolu' || update.statut === 'Refusé') && !update.date_resolution) {
      update.date_resolution = new Date();
    }
    const sav = await SAV.findByIdAndUpdate(req.params.id, { $set: update }, { new: true });
    if (!sav) {
      res.status(404).json({ error: 'SAV non trouvé.' });
      return;
    }
    res.json(sav);
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la mise à jour du SAV.' });
  }
};

export const deleteSAV = async (req: Request, res: Response): Promise<void> => {
  try {
    const sav = await SAV.findByIdAndDelete(req.params.id);
    if (!sav) {
      res.status(404).json({ error: 'SAV non trouvé.' });
      return;
    }
    res.json({ message: 'SAV supprimé.' });
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la suppression du SAV.' });
  }
};

// Lookup repair info by numero for SAV creation
export const lookupRepair = async (req: Request, res: Response): Promise<void> => {
  try {
    const shopId = (req as AuthRequest).shopId;
    const { numero } = req.params;
    const repair = await Repair.findOne({ numeroReparation: numero });
    if (!repair) {
      res.status(404).json({ error: 'Réparation introuvable.' });
      return;
    }

    // Calcul garantie
    const settings = await Settings.findOne({ shopId });
    const warrantyMs = parseWarrantyDuration(settings?.warranty?.duree || '');
    const { sous_garantie, date_fin_garantie } = computeWarrantyStatus(repair.date_retrait, warrantyMs);

    res.json({
      _id: repair._id,
      numeroReparation: repair.numeroReparation,
      client_nom: repair.client_nom,
      client_telephone: repair.client_telephone,
      appareil_marque_modele: repair.appareil_marque_modele,
      statut_reparation: repair.statut_reparation,
      date_creation: repair.date_creation,
      date_retrait: repair.date_retrait || null,
      sous_garantie,
      date_fin_garantie,
      duree_garantie: settings?.warranty?.duree || '',
    });
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la recherche de la réparation.' });
  }
};
