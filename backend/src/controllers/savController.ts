import { Request, Response } from 'express';
import prisma from '../lib/prisma';
import { withId, withIds } from '../lib/transform';
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
    const where = shopId ? { shopId } : {};
    const page = parseInt(req.query.page as string);
    const limit = parseInt(req.query.limit as string);

    if (page > 0 && limit > 0) {
      const skip = (page - 1) * limit;
      const [items, total] = await Promise.all([
        prisma.sAV.findMany({ where, orderBy: { date_creation: 'desc' }, skip, take: limit }),
        prisma.sAV.count({ where }),
      ]);
      res.json({ data: withIds(items), total, page, totalPages: Math.ceil(total / limit) });
    } else {
      const items = await prisma.sAV.findMany({ where, orderBy: { date_creation: 'desc' } });
      res.json(withIds(items));
    }
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération des SAV.' });
  }
};

export const getSAVById = async (req: Request, res: Response): Promise<void> => {
  try {
    const sav = await prisma.sAV.findUnique({ where: { id: req.params.id } });
    if (!sav) {
      res.status(404).json({ error: 'SAV non trouvé.' });
      return;
    }
    res.json(withId(sav));
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

    if (savData.numeroReparationOrigine) {
      const repair = await prisma.repair.findUnique({ where: { numeroReparation: savData.numeroReparationOrigine } });
      if (repair) {
        savData.repairId = repair.id;
        if (!savData.client_nom) savData.client_nom = repair.client_nom;
        if (!savData.client_telephone) savData.client_telephone = repair.client_telephone;
        if (!savData.appareil_marque_modele) savData.appareil_marque_modele = repair.appareil_marque_modele;

        const settings = await prisma.settings.findUnique({ where: { shopId } });
        const warrantyDuree = (settings?.warranty as any)?.duree || '';
        const warrantyMs = parseWarrantyDuration(warrantyDuree);
        const { sous_garantie, date_fin_garantie } = computeWarrantyStatus(repair.date_retrait, warrantyMs);
        savData.sous_garantie = sous_garantie;
        if (date_fin_garantie) savData.date_fin_garantie = date_fin_garantie;
      }
    }

    const sav = await prisma.sAV.create({ data: savData });
    res.status(201).json(withId(sav));
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la création du SAV.' });
  }
};

export const updateSAV = async (req: Request, res: Response): Promise<void> => {
  try {
    const update = { ...req.body };
    if ((update.statut === 'Résolu' || update.statut === 'Refusé') && !update.date_resolution) {
      update.date_resolution = new Date();
    }
    const sav = await prisma.sAV.update({
      where: { id: req.params.id },
      data: update,
    });
    res.json(withId(sav));
  } catch (err: any) {
    if (err.code === 'P2025') {
      res.status(404).json({ error: 'SAV non trouvé.' });
      return;
    }
    res.status(400).json({ error: 'Erreur lors de la mise à jour du SAV.' });
  }
};

export const deleteSAV = async (req: Request, res: Response): Promise<void> => {
  try {
    await prisma.sAV.delete({ where: { id: req.params.id } });
    res.json({ message: 'SAV supprimé.' });
  } catch (err: any) {
    if (err.code === 'P2025') {
      res.status(404).json({ error: 'SAV non trouvé.' });
      return;
    }
    res.status(500).json({ error: 'Erreur lors de la suppression du SAV.' });
  }
};

// Lookup repair info by numero for SAV creation
export const lookupRepair = async (req: Request, res: Response): Promise<void> => {
  try {
    const shopId = (req as AuthRequest).shopId;
    const { numero } = req.params;
    const repair = await prisma.repair.findUnique({ where: { numeroReparation: numero } });
    if (!repair) {
      res.status(404).json({ error: 'Réparation introuvable.' });
      return;
    }

    const settings = await prisma.settings.findUnique({ where: { shopId } });
    const warrantyDuree = (settings?.warranty as any)?.duree || '';
    const warrantyMs = parseWarrantyDuration(warrantyDuree);
    const { sous_garantie, date_fin_garantie } = computeWarrantyStatus(repair.date_retrait, warrantyMs);

    res.json({
      _id: repair.id,
      id: repair.id,
      numeroReparation: repair.numeroReparation,
      client_nom: repair.client_nom,
      client_telephone: repair.client_telephone,
      appareil_marque_modele: repair.appareil_marque_modele,
      statut_reparation: repair.statut_reparation,
      date_creation: repair.date_creation,
      date_retrait: repair.date_retrait || null,
      sous_garantie,
      date_fin_garantie,
      duree_garantie: warrantyDuree,
    });
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la recherche de la réparation.' });
  }
};
