import { Request, Response } from 'express';
import Repair from '../models/Repair';
import Stock from '../models/Stock';

export const getAllRepairs = async (req: Request, res: Response): Promise<void> => {
  try {
    const repairs = await Repair.find().sort({ date_creation: -1 });
    res.json(repairs);
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
    const repair = new Repair(req.body);
    await repair.save();

    // Décrémentation du stock pour chaque pièce utilisée
    if (Array.isArray(req.body.pieces_rechange_utilisees)) {
      for (const piece of req.body.pieces_rechange_utilisees) {
        if (piece.stockId && piece.quantiteUtilisee > 0) {
          await Stock.findByIdAndUpdate(
            piece.stockId,
            { $inc: { quantite: -Math.abs(piece.quantiteUtilisee) } },
            { new: true }
          );
        }
      }
    }

    res.status(201).json(repair);
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la création de la réparation.' });
  }
};

export const updateRepair = async (req: Request, res: Response): Promise<void> => {
  try {
    const repair = await Repair.findByIdAndUpdate(req.params.id, req.body, { new: true });
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
