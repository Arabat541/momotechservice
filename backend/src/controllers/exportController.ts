import { Response, NextFunction } from 'express';
import type { AuthRequest } from '../middlewares/auth';
import { Parser as Json2csvParser } from 'json2csv';
import PDFDocument from 'pdfkit';

// Export users to CSV
export const exportUsersCSV = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    const users = await (await import('../models/User')).default.find().select('-password');
    const fields = ['_id', 'email', 'nom', 'prenom', 'role'];
    const json2csv = new Json2csvParser({ fields });
    const csv = json2csv.parse(users);
    res.header('Content-Type', 'text/csv');
    res.attachment('utilisateurs.csv');
    res.send(csv);
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(500).json({ error: error.message });
  }
};

// Export users to PDF
export const exportUsersPDF = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  try {
    const users = await (await import('../models/User')).default.find().select('-password');
    const doc = new PDFDocument();
    res.setHeader('Content-Type', 'application/pdf');
    res.setHeader('Content-Disposition', 'attachment; filename=utilisateurs.pdf');
    doc.pipe(res);
    doc.fontSize(18).text('Liste des utilisateurs', { align: 'center' });
    doc.moveDown();
    users.forEach((user: any) => {
      doc.fontSize(12).text(`Nom: ${user.nom} ${user.prenom} | Email: ${user.email} | Rôle: ${user.role}`);
    });
    doc.end();
  } catch (err) {
    const error = err instanceof Error ? err : new Error('Erreur inconnue');
    res.status(500).json({ error: error.message });
  }
};
