import { Request, Response } from 'express';
import Stock from '../models/Stock';
import { AuthRequest } from '../middlewares/auth';

export const getAllStocks = async (req: Request, res: Response): Promise<void> => {
  try {
    const shopId = (req as AuthRequest).shopId;
    const filter = shopId ? { shopId } : {};
    const page = parseInt(req.query.page as string);
    const limit = parseInt(req.query.limit as string);

    if (page > 0 && limit > 0) {
      const skip = (page - 1) * limit;
      const [stocks, total] = await Promise.all([
        Stock.find(filter).skip(skip).limit(limit),
        Stock.countDocuments(filter),
      ]);
      res.json({ data: stocks, total, page, totalPages: Math.ceil(total / limit) });
    } else {
      const stocks = await Stock.find(filter);
      res.json(stocks);
    }
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
    const shopId = (req as AuthRequest).shopId;
    const stockData = { ...req.body };
    if (shopId) {
      stockData.shopId = shopId;
    }
    const stock = new Stock(stockData);
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
