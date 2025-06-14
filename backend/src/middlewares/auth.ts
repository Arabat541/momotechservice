import { Request, Response, NextFunction } from 'express';
import jwt, { JwtPayload } from 'jsonwebtoken';

export interface AuthRequest extends Request {
  user?: { id: string; role: string };
}

export const authenticateJWT = (req: AuthRequest, res: Response, next: NextFunction): void => {
  const authHeader = req.headers.authorization;
  console.log('AUTH_MIDDLEWARE: Authorization header reçu =', authHeader);
  if (!authHeader) {
    res.status(401).json({ error: 'Token manquant' });
    return;
  }
  const token = authHeader.split(' ')[1];
  console.log('AUTH_MIDDLEWARE: Token extrait =', token);
  jwt.verify(token, process.env.JWT_SECRET as string, (err, user) => {
    if (err) {
      console.log('AUTH_MIDDLEWARE: Erreur de vérification JWT =', err);
      res.status(403).json({ error: 'Token invalide' });
      return;
    }
    if (typeof user === 'object' && user && 'id' in user && 'role' in user) {
      req.user = { id: (user as JwtPayload).id, role: (user as JwtPayload).role };
      next();
    } else {
      res.status(403).json({ error: 'Token invalide' });
      return;
    }
  });
};

export const authorizeRole = (roles: string[]) => (req: AuthRequest, res: Response, next: NextFunction): void => {
  if (!req.user || !roles.includes(req.user.role)) {
    res.status(403).json({ error: 'Accès refusé' });
    return;
  }
  next();
};