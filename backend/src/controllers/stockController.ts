import { Request, Response } from 'express';
import prisma from '../lib/prisma';
import { withId, withIds } from '../lib/transform';
import { AuthRequest } from '../middlewares/auth';

export const getAllStocks = async (req: Request, res: Response): Promise<void> => {
  try {
    const shopId = (req as AuthRequest).shopId;
    const where = shopId ? { shopId } : {};
    const page = parseInt(req.query.page as string);
    const limit = parseInt(req.query.limit as string);

    if (page > 0 && limit > 0) {
      const skip = (page - 1) * limit;
      const [stocks, total] = await Promise.all([
        prisma.stock.findMany({ where, skip, take: limit }),
        prisma.stock.count({ where }),
      ]);
      res.json({ data: withIds(stocks), total, page, totalPages: Math.ceil(total / limit) });
    } else {
      const stocks = await prisma.stock.findMany({ where });
      res.json(withIds(stocks));
    }
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération des stocks.' });
  }
};

export const getStockById = async (req: Request, res: Response): Promise<void> => {
  try {
    const stock = await prisma.stock.findUnique({ where: { id: req.params.id } });
    if (!stock) {
      res.status(404).json({ error: 'Article non trouvé.' });
      return;
    }
    res.json(withId(stock));
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
    const stock = await prisma.stock.create({ data: stockData });
    res.status(201).json(withId(stock));
  } catch (err) {
    res.status(400).json({ error: 'Erreur lors de la création de l\'article.' });
  }
};

export const updateStock = async (req: Request, res: Response): Promise<void> => {
  try {
    const stock = await prisma.stock.update({
      where: { id: req.params.id },
      data: req.body,
    });
    res.json(withId(stock));
  } catch (err: any) {
    if (err.code === 'P2025') {
      res.status(404).json({ error: 'Article non trouvé.' });
      return;
    }
    res.status(400).json({ error: 'Erreur lors de la mise à jour de l\'article.' });
  }
};

export const deleteStock = async (req: Request, res: Response): Promise<void> => {
  try {
    await prisma.stock.delete({ where: { id: req.params.id } });
    res.json({ message: 'Article supprimé.' });
  } catch (err: any) {
    if (err.code === 'P2025') {
      res.status(404).json({ error: 'Article non trouvé.' });
      return;
    }
    res.status(500).json({ error: 'Erreur lors de la suppression de l\'article.' });
  }
};
