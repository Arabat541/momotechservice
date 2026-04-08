import { z } from 'zod';

export const registerSchema = z.object({
  email: z.string().email('Email invalide.'),
  password: z.string().min(8, 'Le mot de passe doit contenir au moins 8 caractères.'),
  nom: z.string().min(1, 'Le nom est requis.'),
  prenom: z.string().min(1, 'Le prénom est requis.'),
  role: z.string().min(1, 'Le rôle est requis.'),
});

export const loginSchema = z.object({
  email: z.string().email('Email invalide.'),
  password: z.string().min(1, 'Le mot de passe est requis.'),
});

export const resetPasswordSchema = z.object({
  email: z.string().email('Email invalide.'),
});

export const confirmResetSchema = z.object({
  email: z.string().email('Email invalide.'),
  code: z.string().length(6, 'Le code doit contenir 6 chiffres.'),
  password: z.string().min(8, 'Le mot de passe doit contenir au moins 8 caractères.'),
});

export const createRepairSchema = z.object({
  type_reparation: z.enum(['place', 'rdv']),
  client_nom: z.string().min(1, 'Le nom du client est requis.'),
  client_telephone: z.string().min(1, 'Le téléphone est requis.'),
  appareil_marque_modele: z.string().min(1, "L'appareil est requis."),
  pannes_services: z.array(z.object({
    description: z.string().min(1),
    montant: z.number().min(0),
  })).min(1, 'Au moins une panne/service est requise.'),
  pieces_rechange_utilisees: z.array(z.object({
    stockId: z.string(),
    nom: z.string(),
    quantiteUtilisee: z.number().int().min(1),
  })).default([]),
  total_reparation: z.number().min(0),
  montant_paye: z.number().min(0),
  reste_a_payer: z.number().min(0),
  statut_reparation: z.string().min(1),
  etat_paiement: z.string().min(1),
  date_creation: z.coerce.date().optional(),
  date_mise_en_reparation: z.coerce.date().optional(),
  date_rendez_vous: z.coerce.date().optional(),
  date_retrait: z.coerce.date().nullable().optional(),
  numeroReparation: z.string().min(1, 'Le numéro de réparation est requis.'),
  userId: z.string().min(1),
}).passthrough();

export const createStockSchema = z.object({
  nom: z.string().min(1, "Le nom de l'article est requis."),
  quantite: z.number().int().min(0, 'La quantité doit être positive.'),
  prixAchat: z.number().min(0, "Le prix d'achat doit être positif."),
  prixVente: z.number().min(0, 'Le prix de vente doit être positif.'),
  beneficeNetAttendu: z.number().optional(),
}).passthrough();
