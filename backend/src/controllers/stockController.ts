import { Request, Response } from 'express';
import Stock from '../models/Stock';

export const getAllStocks = async (req: Request, res: Response): Promise<void> => {
  try {
    const stocks = await Stock.find();
    res.json(stocks);
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération des stocks.' });
  }
};

export const getStockById = async (req: Request, res: Response): Promise<void> => {
  try {
    const stock = await Stock.findById(req.params.id);
    if (!stock) {
      res.status(404).json({ error: 'Article non trouvé.' });
      return;
    }
    res.json(stock);
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération de l\'article.' });
  }
};

export const createStock = async (req: Request, res: Response): Promise<void> => {
  try {
    const stock = new Stock(req.body);
    await stock.save();
    res.status(201).json(stock);
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la création de l\'article.' });
  }
};

export const updateStock = async (req: Request, res: Response): Promise<void> => {
  try {
    const stock = await Stock.findByIdAndUpdate(req.params.id, req.body, { new: true });
    if (!stock) {
      res.status(404).json({ error: 'Article non trouvé.' });
      return;
    }
    res.json(stock);
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la mise à jour de l\'article.' });
  }
};

export const deleteStock = async (req: Request, res: Response): Promise<void> => {
  try {
    const stock = await Stock.findByIdAndDelete(req.params.id);
    if (!stock) {
      res.status(404).json({ error: 'Article non trouvé.' });
      return;
    }
    res.json({ message: 'Article supprimé.' });
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la suppression de l\'article.' });
  }
};
