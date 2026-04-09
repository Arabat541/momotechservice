import { Request, Response } from 'express';
import prisma from '../lib/prisma';
import { withId, withIds } from '../lib/transform';
import { AuthRequest } from '../middlewares/auth';

export const getAllRepairs = async (req: Request, res: Response): Promise<void> => {
  try {
    const shopId = (req as AuthRequest).shopId;
    const where = shopId ? { shopId } : {};
    const page = parseInt(req.query.page as string);
    const limit = parseInt(req.query.limit as string);

    if (page > 0 && limit > 0) {
      const skip = (page - 1) * limit;
      const [repairs, total] = await Promise.all([
        prisma.repair.findMany({ where, orderBy: { date_creation: 'desc' }, skip, take: limit }),
        prisma.repair.count({ where }),
      ]);
      res.json({ data: withIds(repairs), total, page, totalPages: Math.ceil(total / limit) });
    } else {
      const repairs = await prisma.repair.findMany({ where, orderBy: { date_creation: 'desc' } });
      res.json(withIds(repairs));
    }
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération des réparations.' });
  }
};

export const getRepairById = async (req: Request, res: Response): Promise<void> => {
  try {
    const repair = await prisma.repair.findUnique({ where: { id: req.params.id } });
    if (!repair) {
      res.status(404).json({ error: 'Réparation non trouvée.' });
      return;
    }
    res.json(withId(repair));
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération de la réparation.' });
  }
};

export const createRepair = async (req: Request, res: Response): Promise<void> => {
  try {
    const shopId = (req as AuthRequest).shopId;
    const repairData = { ...req.body };
    if (shopId) {
      repairData.shopId = shopId;
    }
    const repair = await prisma.repair.create({ data: repairData });
    res.status(201).json(withId(repair));
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la création de la réparation.' });
  }
};

export const updateRepair = async (req: Request, res: Response): Promise<void> => {
  try {
    const update = { ...req.body };
    // In Prisma/MySQL, setting date_retrait to null is straightforward
    const repair = await prisma.repair.update({
      where: { id: req.params.id },
      data: update,
    });
    res.json(withId(repair));
  } catch (err: any) {
    if (err.code === 'P2025') {
      res.status(404).json({ error: 'Réparation non trouvée.' });
      return;
    }
    res.status(400).json({ error: 'Erreur lors de la mise à jour de la réparation.' });
  }
};

export const deleteRepair = async (req: Request, res: Response): Promise<void> => {
  try {
    await prisma.repair.delete({ where: { id: req.params.id } });
    res.json({ message: 'Réparation supprimée.' });
  } catch (err: any) {
    if (err.code === 'P2025') {
      res.status(404).json({ error: 'Réparation non trouvée.' });
      return;
    }
    res.status(500).json({ error: 'Erreur lors de la suppression de la réparation.' });
  }
};
