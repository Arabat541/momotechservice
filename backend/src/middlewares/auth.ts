import { Request, Response, NextFunction } from 'express';
import jwt, { JwtPayload } from 'jsonwebtoken';
import prisma from '../lib/prisma';

export interface AuthRequest extends Request {
  user?: { id: string; role: string };
  shopId?: string;
}

export const authenticateJWT = (req: AuthRequest, res: Response, next: NextFunction): void => {
  const authHeader = req.headers.authorization;
  if (!authHeader) {
    res.status(401).json({ error: 'Token manquant' });
    return;
  }
  const token = authHeader.split(' ')[1];
  jwt.verify(token, process.env.JWT_SECRET as string, (err, user) => {
    if (err) {
      res.status(403).json({ error: 'Token invalide' });
      return;
    }
    if (typeof user === 'object' && user && 'id' in user && 'role' in user) {
      req.user = { id: (user as JwtPayload).id, role: (user as JwtPayload).role };
      // Extract shopId from header
      const shopId = req.headers['x-shop-id'] as string | undefined;
      if (shopId) {
        req.shopId = shopId;
      }
      next();
    } else {
      res.status(403).json({ error: 'Token invalide' });
      return;
    }
  });
};

// Middleware to require a shopId and validate user has access to it
export const requireShop = async (req: AuthRequest, res: Response, next: NextFunction): Promise<void> => {
  if (!req.shopId) {
    res.status(400).json({ error: 'Boutique non sélectionnée (header X-Shop-Id manquant)' });
    return;
  }
  if (!req.user) {
    res.status(401).json({ error: 'Non authentifié' });
    return;
  }
  try {
    const user = await prisma.user.findUnique({
      where: { id: req.user.id },
      include: { shops: { select: { id: true } } },
    });
    if (!user) {
      res.status(401).json({ error: 'Utilisateur non trouvé' });
      return;
    }
    const hasAccess = user.shops.some((s) => s.id === req.shopId);
    if (!hasAccess) {
      res.status(403).json({ error: 'Accès refusé à cette boutique' });
      return;
    }
    next();
  } catch (err) {
    res.status(500).json({ error: 'Erreur de vérification boutique' });
  }
};

export const authorizeRole = (roles: string[]) => (req: AuthRequest, res: Response, next: NextFunction): void => {
  if (!req.user || !roles.includes(req.user.role)) {
    res.status(403).json({ error: 'Accès refusé' });
    return;
  }
  next();
};