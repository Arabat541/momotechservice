import { Request, Response } from 'express';
import Repair from '../models/Repair';
import { AuthRequest } from '../middlewares/auth';

export const getAllRepairs = async (req: Request, res: Response): Promise<void> => {
  try {
    const shopId = (req as AuthRequest).shopId;
    const filter = shopId ? { shopId } : {};
    const page = parseInt(req.query.page as string);
    const limit = parseInt(req.query.limit as string);

    if (page > 0 && limit > 0) {
      const skip = (page - 1) * limit;
      const [repairs, total] = await Promise.all([
        Repair.find(filter).sort({ date_creation: -1 }).skip(skip).limit(limit),
        Repair.countDocuments(filter),
      ]);
      res.json({ data: repairs, total, page, totalPages: Math.ceil(total / limit) });
    } else {
      const repairs = await Repair.find(filter).sort({ date_creation: -1 });
      res.json(repairs);
    }
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération des réparations.' });
  }
};

export const getRepairById = async (req: Request, res: Response): Promise<void> => {
  try {
    const repair = await Repair.findById(req.params.id);
    if (!repair) {
      res.status(404).json({ error: 'Réparation non trouvée.' });
      return;
    }
    res.json(repair);
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
    const repair = new Repair(repairData);
    await repair.save();

    res.status(201).json(repair);
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la création de la réparation.' });
  }
};

export const updateRepair = async (req: Request, res: Response): Promise<void> => {
  try {
    let update: any = { ...req.body };
    let updateQuery;
    // Si date_retrait est explicitement null, on unset le champ
    if (Object.prototype.hasOwnProperty.call(req.body, 'date_retrait') && req.body.date_retrait === null) {
      updateQuery = { $set: { ...update }, $unset: { date_retrait: '' } };
      delete updateQuery.$set.date_retrait; // On retire date_retrait du $set
    } else {
      updateQuery = { $set: update };
    }
    const repair = await Repair.findByIdAndUpdate(req.params.id, updateQuery, { new: true });
    if (!repair) {
      res.status(404).json({ error: 'Réparation non trouvée.' });
      return;
    }
    res.json(repair);
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la mise à jour de la réparation.' });
  }
};

export const deleteRepair = async (req: Request, res: Response): Promise<void> => {
  try {
    const repair = await Repair.findByIdAndDelete(req.params.id);
    if (!repair) {
      res.status(404).json({ error: 'Réparation non trouvée.' });
      return;
    }
    res.json({ message: 'Réparation supprimée.' });
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la suppression de la réparation.' });
  }
};
