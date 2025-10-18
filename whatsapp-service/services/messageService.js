const WhatsAppService = require('./whatsappService');
const { MessageMedia } = require('whatsapp-web.js');

class MessageService {
    async sendMessage(sessionId, to, message) {
        try {
            const session = WhatsAppService.getSession(sessionId);
            
            if (!session) {
                throw new Error('Session not found');
            }

            const formattedPhone = WhatsAppService.formatPhoneNumber(to);
            const sentMessage = await session.client.sendMessage(formattedPhone, message);
            
            return {
                id: sentMessage.id._serialized,
                to: formattedPhone,
                message,
                timestamp: sentMessage.timestamp,
                ack: sentMessage.ack
            };
        } catch (error) {
            console.error('Send message error:', error);
            throw error;
        }
    }

    async sendBulkMessages(sessionId, messages, delay = 5000) {
        try {
            const results = [];
            
            for (const msg of messages) {
                try {
                    const result = await this.sendMessage(sessionId, msg.to, msg.message);
                    results.push({ success: true, ...result });
                    
                    // Add delay between messages to prevent spam detection
                    await WhatsAppService.delay(delay);
                } catch (error) {
                    results.push({ 
                        success: false, 
                        to: msg.to, 
                        error: error.message 
                    });
                }
            }
            
            return results;
        } catch (error) {
            console.error('Send bulk messages error:', error);
            throw error;
        }
    }

    async sendMediaMessage(sessionId, to, file, caption = '') {
        try {
            const session = WhatsAppService.getSession(sessionId);
            
            if (!session) {
                throw new Error('Session not found');
            }

            const formattedPhone = WhatsAppService.formatPhoneNumber(to);
            
            // Create MessageMedia from buffer
            const media = new MessageMedia(
                file.mimetype,
                file.buffer.toString('base64'),
                file.originalname
            );
            
            const sentMessage = await session.client.sendMessage(formattedPhone, media, {
                caption: caption
            });
            
            return {
                id: sentMessage.id._serialized,
                to: formattedPhone,
                type: 'media',
                caption,
                timestamp: sentMessage.timestamp
            };
        } catch (error) {
            console.error('Send media message error:', error);
            throw error;
        }
    }

    async getConversation(sessionId, phone, limit = 50) {
        try {
            const session = WhatsAppService.getSession(sessionId);
            
            if (!session) {
                throw new Error('Session not found');
            }

            const formattedPhone = WhatsAppService.formatPhoneNumber(phone);
            const chat = await session.client.getChatById(formattedPhone);
            const messages = await chat.fetchMessages({ limit });
            
            return messages.map(msg => ({
                id: msg.id._serialized,
                body: msg.body,
                from: msg.from,
                to: msg.to,
                timestamp: msg.timestamp,
                type: msg.type,
                ack: msg.ack,
                hasMedia: msg.hasMedia,
                fromMe: msg.fromMe
            }));
        } catch (error) {
            console.error('Get conversation error:', error);
            throw error;
        }
    }

    async markAsRead(sessionId, messageId) {
        try {
            const session = WhatsAppService.getSession(sessionId);
            
            if (!session) {
                throw new Error('Session not found');
            }

            // Get message and mark as seen
            const chats = await session.client.getChats();
            
            for (const chat of chats) {
                const messages = await chat.fetchMessages({ limit: 100 });
                const message = messages.find(m => m.id._serialized === messageId);
                
                if (message) {
                    await chat.sendSeen();
                    return { message: 'Message marked as read' };
                }
            }
            
            throw new Error('Message not found');
        } catch (error) {
            console.error('Mark as read error:', error);
            throw error;
        }
    }

    async deleteMessage(sessionId, messageId, forEveryone = false) {
        try {
            const session = WhatsAppService.getSession(sessionId);
            
            if (!session) {
                throw new Error('Session not found');
            }

            const chats = await session.client.getChats();
            
            for (const chat of chats) {
                const messages = await chat.fetchMessages({ limit: 100 });
                const message = messages.find(m => m.id._serialized === messageId);
                
                if (message) {
                    await message.delete(forEveryone);
                    return { message: 'Message deleted successfully' };
                }
            }
            
            throw new Error('Message not found');
        } catch (error) {
            console.error('Delete message error:', error);
            throw error;
        }
    }
}

module.exports = new MessageService();

