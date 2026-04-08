import express from 'express';
import { getAllShopsPublic, getShopPublicInfo, trackRepair } from '../controllers/storefrontController';

const router = express.Router();

// Public endpoints — no authentication required
router.get('/all', getAllShopsPublic);
router.get('/track/:numero', trackRepair);
router.get('/:shopId', getShopPublicInfo);

export default router;
