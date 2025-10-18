const WhatsAppService = require('../services/whatsappService');

class WhatsAppController {
    async createSession(req, res) {
        try {
            const { sessionId, workspaceId } = req.body;
            
            if (!sessionId || !workspaceId) {
                return res.status(400).json({ 
                    success: false, 
                    message: 'Session ID and Workspace ID are required' 
                });
            }

            const session = await WhatsAppService.createSession(sessionId, workspaceId);
            const io = req.app.get('io');
            
            res.json({ 
                success: true, 
                message: 'Session created successfully',
                data: { sessionId }
            });
        } catch (error) {
            console.error('Create session error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async destroySession(req, res) {
        try {
            const { sessionId } = req.body;
            
            if (!sessionId) {
                return res.status(400).json({ 
                    success: false, 
                    message: 'Session ID is required' 
                });
            }

            await WhatsAppService.destroySession(sessionId);
            
            res.json({ 
                success: true, 
                message: 'Session destroyed successfully' 
            });
        } catch (error) {
            console.error('Destroy session error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async getSessionStatus(req, res) {
        try {
            const { sessionId } = req.params;
            
            const status = await WhatsAppService.getSessionStatus(sessionId);
            
            res.json({ 
                success: true, 
                data: status 
            });
        } catch (error) {
            console.error('Get session status error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async getQRCode(req, res) {
        try {
            const { sessionId } = req.params;
            
            const qrCode = await WhatsAppService.getQRCode(sessionId);
            
            if (!qrCode) {
                return res.status(404).json({ 
                    success: false, 
                    message: 'QR Code not available' 
                });
            }
            
            res.json({ 
                success: true, 
                data: { qrCode } 
            });
        } catch (error) {
            console.error('Get QR code error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async validateNumber(req, res) {
        try {
            const { sessionId, phone } = req.body;
            
            if (!sessionId || !phone) {
                return res.status(400).json({ 
                    success: false, 
                    message: 'Session ID and phone number are required' 
                });
            }

            const isValid = await WhatsAppService.validateNumber(sessionId, phone);
            
            res.json({ 
                success: true, 
                data: { 
                    phone, 
                    isValid,
                    isRegistered: isValid 
                } 
            });
        } catch (error) {
            console.error('Validate number error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async validateNumbersBulk(req, res) {
        try {
            const { sessionId, phones } = req.body;
            
            if (!sessionId || !phones || !Array.isArray(phones)) {
                return res.status(400).json({ 
                    success: false, 
                    message: 'Session ID and phones array are required' 
                });
            }

            const results = await WhatsAppService.validateNumbersBulk(sessionId, phones);
            
            res.json({ 
                success: true, 
                data: results 
            });
        } catch (error) {
            console.error('Validate numbers bulk error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async getContacts(req, res) {
        try {
            const { sessionId } = req.params;
            
            const contacts = await WhatsAppService.getContacts(sessionId);
            
            res.json({ 
                success: true, 
                data: contacts 
            });
        } catch (error) {
            console.error('Get contacts error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async blockContact(req, res) {
        try {
            const { sessionId, phone } = req.body;
            
            await WhatsAppService.blockContact(sessionId, phone);
            
            res.json({ 
                success: true, 
                message: 'Contact blocked successfully' 
            });
        } catch (error) {
            console.error('Block contact error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }

    async unblockContact(req, res) {
        try {
            const { sessionId, phone } = req.body;
            
            await WhatsAppService.unblockContact(sessionId, phone);
            
            res.json({ 
                success: true, 
                message: 'Contact unblocked successfully' 
            });
        } catch (error) {
            console.error('Unblock contact error:', error);
            res.status(500).json({ 
                success: false, 
                message: error.message 
            });
        }
    }
}

module.exports = new WhatsAppController();

