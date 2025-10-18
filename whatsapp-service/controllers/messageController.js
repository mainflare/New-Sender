const WhatsAppService = require('../services/whatsappService');
const MessageService = require('../services/messageService');

class MessageController {
    async sendMessage(req, res) {
        try {
            const { sessionId, to, message } = req.body;
            
            if (!sessionId || !to || !message) {
                return res.status(400).json({ 
                    success: false, 
                    message: 'Session ID, recipient, and message are required' 
                });
            }

            const result = await MessageService.sendMessage(sessionId, to, message);
            
            res.json({ 
                success: true, 
                data: result 
            });
        } catch (error) {
            console.error('Send message error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async sendBulkMessages(req, res) {
        try {
            const { sessionId, messages, delay } = req.body;
            
            if (!sessionId || !messages || !Array.isArray(messages)) {
                return res.status(400).json({ 
                    success: false, 
                    message: 'Session ID and messages array are required' 
                });
            }

            // Start bulk sending in background
            MessageService.sendBulkMessages(sessionId, messages, delay || 5000)
                .then(results => {
                    console.log('Bulk messages sent:', results);
                })
                .catch(error => {
                    console.error('Bulk send error:', error);
                });
            
            res.json({ 
                success: true, 
                message: 'Bulk messaging started',
                data: { 
                    totalMessages: messages.length,
                    estimatedTime: messages.length * (delay || 5000) / 1000 + ' seconds'
                }
            });
        } catch (error) {
            console.error('Send bulk messages error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async sendMediaMessage(req, res) {
        try {
            const { sessionId, to, caption } = req.body;
            const file = req.file;
            
            if (!sessionId || !to || !file) {
                return res.status(400).json({ 
                    success: false, 
                    message: 'Session ID, recipient, and file are required' 
                });
            }

            const result = await MessageService.sendMediaMessage(
                sessionId, 
                to, 
                file, 
                caption
            );
            
            res.json({ 
                success: true, 
                data: result 
            });
        } catch (error) {
            console.error('Send media message error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async getConversation(req, res) {
        try {
            const { phone } = req.params;
            const { sessionId, limit } = req.query;
            
            if (!sessionId || !phone) {
                return res.status(400).json({ 
                    success: false, 
                    message: 'Session ID and phone number are required' 
                });
            }

            const messages = await MessageService.getConversation(
                sessionId, 
                phone, 
                parseInt(limit) || 50
            );
            
            res.json({ 
                success: true, 
                data: messages 
            });
        } catch (error) {
            console.error('Get conversation error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async markAsRead(req, res) {
        try {
            const { sessionId, messageId } = req.body;
            
            if (!sessionId || !messageId) {
                return res.status(400).json({ 
                    success: false, 
                    message: 'Session ID and message ID are required' 
                });
            }

            await MessageService.markAsRead(sessionId, messageId);
            
            res.json({ 
                success: true, 
                message: 'Message marked as read' 
            });
        } catch (error) {
            console.error('Mark as read error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async deleteMessage(req, res) {
        try {
            const { messageId } = req.params;
            const { sessionId, forEveryone } = req.body;
            
            if (!sessionId || !messageId) {
                return res.status(400).json({ 
                    success: false, 
                    message: 'Session ID and message ID are required' 
                });
            }

            await MessageService.deleteMessage(sessionId, messageId, forEveryone);
            
            res.json({ 
                success: true, 
                message: 'Message deleted successfully' 
            });
        } catch (error) {
            console.error('Delete message error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }
}

module.exports = new MessageController();

