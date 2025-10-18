const express = require('express');
const router = express.Router();
const whatsappController = require('../controllers/whatsappController');

// WhatsApp session management
router.post('/session/create', whatsappController.createSession);
router.post('/session/destroy', whatsappController.destroySession);
router.get('/session/status/:sessionId', whatsappController.getSessionStatus);
router.get('/session/qr/:sessionId', whatsappController.getQRCode);

// WhatsApp number validation
router.post('/validate-number', whatsappController.validateNumber);
router.post('/validate-numbers-bulk', whatsappController.validateNumbersBulk);

// Contact operations
router.get('/contacts/:sessionId', whatsappController.getContacts);
router.post('/contact/block', whatsappController.blockContact);
router.post('/contact/unblock', whatsappController.unblockContact);

module.exports = router;

