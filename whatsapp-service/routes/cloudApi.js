const express = require('express');
const router = express.Router();
const cloudApiController = require('../controllers/cloudApiController');

// Session management
router.post('/initialize', cloudApiController.initializeSession.bind(cloudApiController));
router.get('/status', cloudApiController.getStatus.bind(cloudApiController));
router.post('/disconnect', cloudApiController.disconnect.bind(cloudApiController));

// Messaging
router.post('/send-message', cloudApiController.sendMessage.bind(cloudApiController));
router.post('/send-media', cloudApiController.sendMedia.bind(cloudApiController));
router.post('/send-template', cloudApiController.sendTemplate.bind(cloudApiController));
router.post('/mark-as-read', cloudApiController.markAsRead.bind(cloudApiController));

// Templates
router.get('/templates', cloudApiController.getTemplates.bind(cloudApiController));
router.post('/templates', cloudApiController.createTemplate.bind(cloudApiController));

// Business Profile
router.get('/business-profile', cloudApiController.getBusinessProfile.bind(cloudApiController));
router.post('/business-profile', cloudApiController.updateBusinessProfile.bind(cloudApiController));

// Webhook
router.get('/webhook', cloudApiController.handleWebhook.bind(cloudApiController));
router.post('/webhook', cloudApiController.handleWebhook.bind(cloudApiController));

module.exports = router;


