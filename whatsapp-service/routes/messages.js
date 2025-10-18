const express = require('express');
const router = express.Router();
const messageController = require('../controllers/messageController');
const multer = require('multer');

// Configure multer for file uploads
const storage = multer.memoryStorage();
const upload = multer({ storage: storage });

// Send messages
router.post('/send', messageController.sendMessage);
router.post('/send-bulk', messageController.sendBulkMessages);
router.post('/send-media', upload.single('file'), messageController.sendMediaMessage);

// Message management
router.get('/conversation/:phone', messageController.getConversation);
router.post('/mark-read', messageController.markAsRead);
router.delete('/delete/:messageId', messageController.deleteMessage);

module.exports = router;

