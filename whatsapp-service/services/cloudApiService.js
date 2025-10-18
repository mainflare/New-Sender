const axios = require('axios');

class CloudApiService {
    constructor() {
        this.baseUrl = 'https://graph.facebook.com/v18.0';
        this.sessions = new Map();
    }

    /**
     * Initialize Cloud API session
     */
    async initializeSession(sessionData) {
        const { sessionName, accessToken, phoneNumberId, businessAccountId } = sessionData;

        try {
            // Verify the access token and phone number
            const response = await axios.get(
                `${this.baseUrl}/${phoneNumberId}`,
                {
                    headers: {
                        'Authorization': `Bearer ${accessToken}`
                    }
                }
            );

            if (response.data) {
                this.sessions.set(sessionName, {
                    accessToken,
                    phoneNumberId,
                    businessAccountId,
                    phoneNumber: response.data.display_phone_number,
                    status: 'connected',
                    verifiedName: response.data.verified_name,
                    qualityRating: response.data.quality_rating,
                });

                return {
                    success: true,
                    message: 'Cloud API session initialized',
                    data: this.sessions.get(sessionName)
                };
            }
        } catch (error) {
            console.error('Cloud API initialization error:', error.response?.data || error.message);
            return {
                success: false,
                message: 'Failed to initialize Cloud API session',
                error: error.response?.data || error.message
            };
        }
    }

    /**
     * Send text message
     */
    async sendTextMessage(sessionName, to, message) {
        const session = this.sessions.get(sessionName);

        if (!session) {
            return {
                success: false,
                message: 'Session not found'
            };
        }

        try {
            const response = await axios.post(
                `${this.baseUrl}/${session.phoneNumberId}/messages`,
                {
                    messaging_product: 'whatsapp',
                    recipient_type: 'individual',
                    to: to,
                    type: 'text',
                    text: {
                        preview_url: true,
                        body: message
                    }
                },
                {
                    headers: {
                        'Authorization': `Bearer ${session.accessToken}`,
                        'Content-Type': 'application/json'
                    }
                }
            );

            return {
                success: true,
                messageId: response.data.messages[0].id,
                data: response.data
            };
        } catch (error) {
            console.error('Send message error:', error.response?.data || error.message);
            return {
                success: false,
                message: 'Failed to send message',
                error: error.response?.data || error.message
            };
        }
    }

    /**
     * Send media message (image, video, document)
     */
    async sendMediaMessage(sessionName, to, mediaType, mediaUrl, caption = '') {
        const session = this.sessions.get(sessionName);

        if (!session) {
            return {
                success: false,
                message: 'Session not found'
            };
        }

        try {
            const payload = {
                messaging_product: 'whatsapp',
                recipient_type: 'individual',
                to: to,
                type: mediaType,
                [mediaType]: {
                    link: mediaUrl
                }
            };

            // Add caption for image and video
            if ((mediaType === 'image' || mediaType === 'video') && caption) {
                payload[mediaType].caption = caption;
            }

            const response = await axios.post(
                `${this.baseUrl}/${session.phoneNumberId}/messages`,
                payload,
                {
                    headers: {
                        'Authorization': `Bearer ${session.accessToken}`,
                        'Content-Type': 'application/json'
                    }
                }
            );

            return {
                success: true,
                messageId: response.data.messages[0].id,
                data: response.data
            };
        } catch (error) {
            console.error('Send media error:', error.response?.data || error.message);
            return {
                success: false,
                message: 'Failed to send media',
                error: error.response?.data || error.message
            };
        }
    }

    /**
     * Send template message
     */
    async sendTemplateMessage(sessionName, to, templateName, languageCode = 'en_US', components = []) {
        const session = this.sessions.get(sessionName);

        if (!session) {
            return {
                success: false,
                message: 'Session not found'
            };
        }

        try {
            const response = await axios.post(
                `${this.baseUrl}/${session.phoneNumberId}/messages`,
                {
                    messaging_product: 'whatsapp',
                    to: to,
                    type: 'template',
                    template: {
                        name: templateName,
                        language: {
                            code: languageCode
                        },
                        components: components
                    }
                },
                {
                    headers: {
                        'Authorization': `Bearer ${session.accessToken}`,
                        'Content-Type': 'application/json'
                    }
                }
            );

            return {
                success: true,
                messageId: response.data.messages[0].id,
                data: response.data
            };
        } catch (error) {
            console.error('Send template error:', error.response?.data || error.message);
            return {
                success: false,
                message: 'Failed to send template',
                error: error.response?.data || error.message
            };
        }
    }

    /**
     * Get message templates
     */
    async getTemplates(sessionName) {
        const session = this.sessions.get(sessionName);

        if (!session) {
            return {
                success: false,
                message: 'Session not found'
            };
        }

        try {
            const response = await axios.get(
                `${this.baseUrl}/${session.businessAccountId}/message_templates`,
                {
                    headers: {
                        'Authorization': `Bearer ${session.accessToken}`
                    }
                }
            );

            return {
                success: true,
                templates: response.data.data
            };
        } catch (error) {
            console.error('Get templates error:', error.response?.data || error.message);
            return {
                success: false,
                message: 'Failed to get templates',
                error: error.response?.data || error.message
            };
        }
    }

    /**
     * Create message template
     */
    async createTemplate(sessionName, templateData) {
        const session = this.sessions.get(sessionName);

        if (!session) {
            return {
                success: false,
                message: 'Session not found'
            };
        }

        try {
            const response = await axios.post(
                `${this.baseUrl}/${session.businessAccountId}/message_templates`,
                templateData,
                {
                    headers: {
                        'Authorization': `Bearer ${session.accessToken}`,
                        'Content-Type': 'application/json'
                    }
                }
            );

            return {
                success: true,
                template: response.data
            };
        } catch (error) {
            console.error('Create template error:', error.response?.data || error.message);
            return {
                success: false,
                message: 'Failed to create template',
                error: error.response?.data || error.message
            };
        }
    }

    /**
     * Mark message as read
     */
    async markAsRead(sessionName, messageId) {
        const session = this.sessions.get(sessionName);

        if (!session) {
            return {
                success: false,
                message: 'Session not found'
            };
        }

        try {
            const response = await axios.post(
                `${this.baseUrl}/${session.phoneNumberId}/messages`,
                {
                    messaging_product: 'whatsapp',
                    status: 'read',
                    message_id: messageId
                },
                {
                    headers: {
                        'Authorization': `Bearer ${session.accessToken}`,
                        'Content-Type': 'application/json'
                    }
                }
            );

            return {
                success: true,
                data: response.data
            };
        } catch (error) {
            console.error('Mark as read error:', error.response?.data || error.message);
            return {
                success: false,
                error: error.response?.data || error.message
            };
        }
    }

    /**
     * Get business profile
     */
    async getBusinessProfile(sessionName) {
        const session = this.sessions.get(sessionName);

        if (!session) {
            return {
                success: false,
                message: 'Session not found'
            };
        }

        try {
            const response = await axios.get(
                `${this.baseUrl}/${session.phoneNumberId}/whatsapp_business_profile`,
                {
                    headers: {
                        'Authorization': `Bearer ${session.accessToken}`
                    }
                }
            );

            return {
                success: true,
                profile: response.data.data[0]
            };
        } catch (error) {
            console.error('Get business profile error:', error.response?.data || error.message);
            return {
                success: false,
                error: error.response?.data || error.message
            };
        }
    }

    /**
     * Update business profile
     */
    async updateBusinessProfile(sessionName, profileData) {
        const session = this.sessions.get(sessionName);

        if (!session) {
            return {
                success: false,
                message: 'Session not found'
            };
        }

        try {
            const response = await axios.post(
                `${this.baseUrl}/${session.phoneNumberId}/whatsapp_business_profile`,
                profileData,
                {
                    headers: {
                        'Authorization': `Bearer ${session.accessToken}`,
                        'Content-Type': 'application/json'
                    }
                }
            );

            return {
                success: true,
                data: response.data
            };
        } catch (error) {
            console.error('Update business profile error:', error.response?.data || error.message);
            return {
                success: false,
                error: error.response?.data || error.message
            };
        }
    }

    /**
     * Get session status
     */
    getSessionStatus(sessionName) {
        const session = this.sessions.get(sessionName);
        
        if (!session) {
            return {
                success: false,
                status: 'disconnected'
            };
        }

        return {
            success: true,
            status: session.status,
            data: {
                phoneNumber: session.phoneNumber,
                verifiedName: session.verifiedName,
                qualityRating: session.qualityRating
            }
        };
    }

    /**
     * Disconnect session
     */
    disconnectSession(sessionName) {
        const session = this.sessions.get(sessionName);
        
        if (session) {
            this.sessions.delete(sessionName);
            return {
                success: true,
                message: 'Session disconnected'
            };
        }

        return {
            success: false,
            message: 'Session not found'
        };
    }

    /**
     * Handle webhook events
     */
    handleWebhook(webhookData) {
        try {
            const entry = webhookData.entry[0];
            const changes = entry.changes[0];
            const value = changes.value;

            if (value.messages) {
                // Incoming message
                const message = value.messages[0];
                return {
                    type: 'message',
                    from: message.from,
                    messageId: message.id,
                    timestamp: message.timestamp,
                    messageType: message.type,
                    content: this.extractMessageContent(message)
                };
            }

            if (value.statuses) {
                // Message status update
                const status = value.statuses[0];
                return {
                    type: 'status',
                    messageId: status.id,
                    status: status.status,
                    timestamp: status.timestamp,
                    recipient: status.recipient_id
                };
            }

            return null;
        } catch (error) {
            console.error('Webhook processing error:', error);
            return null;
        }
    }

    /**
     * Extract message content based on type
     */
    extractMessageContent(message) {
        switch (message.type) {
            case 'text':
                return message.text.body;
            case 'image':
                return { id: message.image.id, caption: message.image.caption };
            case 'video':
                return { id: message.video.id, caption: message.video.caption };
            case 'audio':
                return { id: message.audio.id };
            case 'document':
                return { id: message.document.id, filename: message.document.filename };
            case 'location':
                return { latitude: message.location.latitude, longitude: message.location.longitude };
            default:
                return message;
        }
    }
}

module.exports = new CloudApiService();


