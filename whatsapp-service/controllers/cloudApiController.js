const cloudApiService = require('../services/cloudApiService');

class CloudApiController {
    /**
     * Initialize Cloud API session
     */
    async initializeSession(req, res) {
        try {
            const { sessionName, accessToken, phoneNumberId, businessAccountId } = req.body;

            if (!sessionName || !accessToken || !phoneNumberId || !businessAccountId) {
                return res.status(400).json({
                    success: false,
                    message: 'Missing required parameters'
                });
            }

            const result = await cloudApiService.initializeSession({
                sessionName,
                accessToken,
                phoneNumberId,
                businessAccountId
            });

            if (result.success) {
                res.json(result);
            } else {
                res.status(500).json(result);
            }
        } catch (error) {
            console.error('Initialize session error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to initialize session',
                error: error.message
            });
        }
    }

    /**
     * Send text message
     */
    async sendMessage(req, res) {
        try {
            const { sessionName, to, message } = req.body;

            if (!sessionName || !to || !message) {
                return res.status(400).json({
                    success: false,
                    message: 'Missing required parameters'
                });
            }

            const result = await cloudApiService.sendTextMessage(sessionName, to, message);
            
            if (result.success) {
                res.json(result);
            } else {
                res.status(500).json(result);
            }
        } catch (error) {
            console.error('Send message error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to send message',
                error: error.message
            });
        }
    }

    /**
     * Send media message
     */
    async sendMedia(req, res) {
        try {
            const { sessionName, to, mediaType, mediaUrl, caption } = req.body;

            if (!sessionName || !to || !mediaType || !mediaUrl) {
                return res.status(400).json({
                    success: false,
                    message: 'Missing required parameters'
                });
            }

            const result = await cloudApiService.sendMediaMessage(
                sessionName,
                to,
                mediaType,
                mediaUrl,
                caption
            );
            
            if (result.success) {
                res.json(result);
            } else {
                res.status(500).json(result);
            }
        } catch (error) {
            console.error('Send media error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to send media',
                error: error.message
            });
        }
    }

    /**
     * Send template message
     */
    async sendTemplate(req, res) {
        try {
            const { sessionName, to, templateName, languageCode, components } = req.body;

            if (!sessionName || !to || !templateName) {
                return res.status(400).json({
                    success: false,
                    message: 'Missing required parameters'
                });
            }

            const result = await cloudApiService.sendTemplateMessage(
                sessionName,
                to,
                templateName,
                languageCode || 'en_US',
                components || []
            );
            
            if (result.success) {
                res.json(result);
            } else {
                res.status(500).json(result);
            }
        } catch (error) {
            console.error('Send template error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to send template',
                error: error.message
            });
        }
    }

    /**
     * Get templates
     */
    async getTemplates(req, res) {
        try {
            const { sessionName } = req.query;

            if (!sessionName) {
                return res.status(400).json({
                    success: false,
                    message: 'Session name is required'
                });
            }

            const result = await cloudApiService.getTemplates(sessionName);
            
            if (result.success) {
                res.json(result);
            } else {
                res.status(500).json(result);
            }
        } catch (error) {
            console.error('Get templates error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to get templates',
                error: error.message
            });
        }
    }

    /**
     * Create template
     */
    async createTemplate(req, res) {
        try {
            const { sessionName, templateData } = req.body;

            if (!sessionName || !templateData) {
                return res.status(400).json({
                    success: false,
                    message: 'Missing required parameters'
                });
            }

            const result = await cloudApiService.createTemplate(sessionName, templateData);
            
            if (result.success) {
                res.json(result);
            } else {
                res.status(500).json(result);
            }
        } catch (error) {
            console.error('Create template error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to create template',
                error: error.message
            });
        }
    }

    /**
     * Mark message as read
     */
    async markAsRead(req, res) {
        try {
            const { sessionName, messageId } = req.body;

            if (!sessionName || !messageId) {
                return res.status(400).json({
                    success: false,
                    message: 'Missing required parameters'
                });
            }

            const result = await cloudApiService.markAsRead(sessionName, messageId);
            
            if (result.success) {
                res.json(result);
            } else {
                res.status(500).json(result);
            }
        } catch (error) {
            console.error('Mark as read error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to mark message as read',
                error: error.message
            });
        }
    }

    /**
     * Get business profile
     */
    async getBusinessProfile(req, res) {
        try {
            const { sessionName } = req.query;

            if (!sessionName) {
                return res.status(400).json({
                    success: false,
                    message: 'Session name is required'
                });
            }

            const result = await cloudApiService.getBusinessProfile(sessionName);
            
            if (result.success) {
                res.json(result);
            } else {
                res.status(500).json(result);
            }
        } catch (error) {
            console.error('Get business profile error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to get business profile',
                error: error.message
            });
        }
    }

    /**
     * Update business profile
     */
    async updateBusinessProfile(req, res) {
        try {
            const { sessionName, profileData } = req.body;

            if (!sessionName || !profileData) {
                return res.status(400).json({
                    success: false,
                    message: 'Missing required parameters'
                });
            }

            const result = await cloudApiService.updateBusinessProfile(sessionName, profileData);
            
            if (result.success) {
                res.json(result);
            } else {
                res.status(500).json(result);
            }
        } catch (error) {
            console.error('Update business profile error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to update business profile',
                error: error.message
            });
        }
    }

    /**
     * Get session status
     */
    async getStatus(req, res) {
        try {
            const { sessionName } = req.query;

            if (!sessionName) {
                return res.status(400).json({
                    success: false,
                    message: 'Session name is required'
                });
            }

            const result = cloudApiService.getSessionStatus(sessionName);
            res.json(result);
        } catch (error) {
            console.error('Get status error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to get session status',
                error: error.message
            });
        }
    }

    /**
     * Disconnect session
     */
    async disconnect(req, res) {
        try {
            const { sessionName } = req.body;

            if (!sessionName) {
                return res.status(400).json({
                    success: false,
                    message: 'Session name is required'
                });
            }

            const result = cloudApiService.disconnectSession(sessionName);
            res.json(result);
        } catch (error) {
            console.error('Disconnect error:', error);
            res.status(500).json({
                success: false,
                message: 'Failed to disconnect session',
                error: error.message
            });
        }
    }

    /**
     * Handle webhook
     */
    async handleWebhook(req, res) {
        try {
            // Verify webhook (GET request for initial verification)
            if (req.method === 'GET') {
                const mode = req.query['hub.mode'];
                const token = req.query['hub.verify_token'];
                const challenge = req.query['hub.challenge'];

                // Verify token (should match your configured verify token)
                if (mode === 'subscribe' && token === process.env.WEBHOOK_VERIFY_TOKEN) {
                    res.status(200).send(challenge);
                } else {
                    res.status(403).send('Forbidden');
                }
                return;
            }

            // Handle webhook event (POST request)
            const webhookData = req.body;
            const processedData = cloudApiService.handleWebhook(webhookData);

            if (processedData) {
                // Emit event via Socket.IO for real-time updates
                if (req.io) {
                    req.io.emit('webhook-event', processedData);
                }

                // You can also forward this to Laravel backend
                // await axios.post('http://localhost:8000/api/webhooks/whatsapp-cloud', processedData);
            }

            res.status(200).send('OK');
        } catch (error) {
            console.error('Webhook error:', error);
            res.status(500).json({
                success: false,
                message: 'Webhook processing failed',
                error: error.message
            });
        }
    }
}

module.exports = new CloudApiController();


