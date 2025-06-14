import Settings from '../models/Settings';
import { Request, Response } from 'express';

export const getSettings = async (req: Request, res: Response) => {
  try {
    let settings = await Settings.findOne();
    if (!settings) {
      settings = await Settings.create({});
    }
    res.json(settings);
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la récupération des paramètres.' });
  }
};

export const updateSettings = async (req: Request, res: Response) => {
  try {
    const { companyInfo, warranty } = req.body;
    let settings = await Settings.findOne();
    if (!settings) {
      settings = await Settings.create({ companyInfo, warranty });
    } else {
      settings.companyInfo = companyInfo;
      settings.warranty = warranty;
      await settings.save();
    }
    res.json(settings);
  } catch (err) {
    res.status(500).json({ error: 'Erreur lors de la mise à jour des paramètres.' });
  }
};
