import { Request, Response, NextFunction } from 'express';
import { ZodSchema, ZodError } from 'zod';

export const validate = (schema: ZodSchema) => (req: Request, res: Response, next: NextFunction) => {
  try {
    req.body = schema.parse(req.body);
    next();
  } catch (err) {
    if (err instanceof ZodError) {
      res.status(400).json({
        error: 'Données invalides.',
        details: err.issues.map((e) => ({ field: (e.path as (string | number)[]).join('.'), message: e.message })),
      });
      return;
    }
    next(err);
  }
};
