import express from 'express';
import { getShopPublicInfo, trackRepair } from '../controllers/storefrontController';

const router = express.Router();

// Public endpoints — no authentication required
router.get('/track/:numero', trackRepair);
router.get('/:shopId', getShopPublicInfo);

export default router;
