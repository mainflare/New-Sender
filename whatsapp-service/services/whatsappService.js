const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const qrcode = require('qrcode-terminal');
const fs = require('fs');
const path = require('path');

class WhatsAppService {
    constructor() {
        this.sessions = new Map();
        this.qrCodes = new Map();
    }

    async createSession(sessionId, workspaceId) {
        try {
            if (this.sessions.has(sessionId)) {
                throw new Error('Session already exists');
            }

            const sessionPath = path.join(__dirname, '../.wwebjs_auth', `session-${sessionId}`);
            
            const client = new Client({
                authStrategy: new LocalAuth({
                    clientId: sessionId,
                    dataPath: sessionPath
                }),
                puppeteer: {
                    headless: true,
                    args: [
                        '--no-sandbox',
                        '--disable-setuid-sandbox',
                        '--disable-dev-shm-usage',
                        '--disable-accelerated-2d-canvas',
                        '--no-first-run',
                        '--no-zygote',
                        '--disable-gpu'
                    ]
                }
            });

            // QR Code event
            client.on('qr', (qr) => {
                console.log(`QR Code generated for session ${sessionId}`);
                this.qrCodes.set(sessionId, qr);
                qrcode.generate(qr, { small: true });
            });

            // Ready event
            client.on('ready', () => {
                console.log(`WhatsApp client ready for session ${sessionId}`);
                this.qrCodes.delete(sessionId);
            });

            // Authenticated event
            client.on('authenticated', () => {
                console.log(`Session ${sessionId} authenticated`);
            });

            // Auth failure event
            client.on('auth_failure', (msg) => {
                console.error(`Auth failure for session ${sessionId}:`, msg);
            });

            // Disconnected event
            client.on('disconnected', (reason) => {
                console.log(`Session ${sessionId} disconnected:`, reason);
                this.sessions.delete(sessionId);
                this.qrCodes.delete(sessionId);
            });

            // Message event
            client.on('message', async (message) => {
                console.log(`New message in session ${sessionId}:`, message.body);
                // Here you can forward messages to Laravel backend
            });

            // Initialize client
            await client.initialize();

            // Store session
            this.sessions.set(sessionId, {
                client,
                workspaceId,
                createdAt: new Date()
            });

            return { sessionId, status: 'initializing' };
        } catch (error) {
            console.error('Create session error:', error);
            throw error;
        }
    }

    async destroySession(sessionId) {
        try {
            const session = this.sessions.get(sessionId);
            
            if (!session) {
                throw new Error('Session not found');
            }

            await session.client.destroy();
            this.sessions.delete(sessionId);
            this.qrCodes.delete(sessionId);

            // Clean up session files
            const sessionPath = path.join(__dirname, '../.wwebjs_auth', `session-${sessionId}`);
            if (fs.existsSync(sessionPath)) {
                fs.rmSync(sessionPath, { recursive: true, force: true });
            }

            return { message: 'Session destroyed successfully' };
        } catch (error) {
            console.error('Destroy session error:', error);
            throw error;
        }
    }

    async getSessionStatus(sessionId) {
        try {
            const session = this.sessions.get(sessionId);
            
            if (!session) {
                return { status: 'not_found', isReady: false };
            }

            const state = await session.client.getState();
            
            return {
                status: state,
                isReady: state === 'CONNECTED',
                sessionId,
                workspaceId: session.workspaceId
            };
        } catch (error) {
            console.error('Get session status error:', error);
            return { status: 'error', isReady: false, error: error.message };
        }
    }

    async getQRCode(sessionId) {
        return this.qrCodes.get(sessionId) || null;
    }

    async validateNumber(sessionId, phone) {
        try {
            const session = this.sessions.get(sessionId);
            
            if (!session) {
                throw new Error('Session not found');
            }

            const formattedPhone = this.formatPhoneNumber(phone);
            const numberId = await session.client.getNumberId(formattedPhone);
            
            return !!numberId;
        } catch (error) {
            console.error('Validate number error:', error);
            return false;
        }
    }

    async validateNumbersBulk(sessionId, phones) {
        try {
            const results = [];
            
            for (const phone of phones) {
                const isValid = await this.validateNumber(sessionId, phone);
                results.push({
                    phone,
                    isValid,
                    isRegistered: isValid
                });
                
                // Add delay to prevent rate limiting
                await this.delay(1000);
            }
            
            return results;
        } catch (error) {
            console.error('Validate numbers bulk error:', error);
            throw error;
        }
    }

    async getContacts(sessionId) {
        try {
            const session = this.sessions.get(sessionId);
            
            if (!session) {
                throw new Error('Session not found');
            }

            const contacts = await session.client.getContacts();
            
            return contacts.map(contact => ({
                id: contact.id._serialized,
                name: contact.name || contact.pushname,
                number: contact.number,
                isGroup: contact.isGroup,
                isMyContact: contact.isMyContact
            }));
        } catch (error) {
            console.error('Get contacts error:', error);
            throw error;
        }
    }

    async blockContact(sessionId, phone) {
        try {
            const session = this.sessions.get(sessionId);
            
            if (!session) {
                throw new Error('Session not found');
            }

            const formattedPhone = this.formatPhoneNumber(phone);
            const contact = await session.client.getContactById(formattedPhone);
            await contact.block();
            
            return { message: 'Contact blocked successfully' };
        } catch (error) {
            console.error('Block contact error:', error);
            throw error;
        }
    }

    async unblockContact(sessionId, phone) {
        try {
            const session = this.sessions.get(sessionId);
            
            if (!session) {
                throw new Error('Session not found');
            }

            const formattedPhone = this.formatPhoneNumber(phone);
            const contact = await session.client.getContactById(formattedPhone);
            await contact.unblock();
            
            return { message: 'Contact unblocked successfully' };
        } catch (error) {
            console.error('Unblock contact error:', error);
            throw error;
        }
    }

    getSession(sessionId) {
        return this.sessions.get(sessionId);
    }

    formatPhoneNumber(phone) {
        // Remove all non-numeric characters
        let cleaned = phone.replace(/\D/g, '');
        
        // Add @c.us suffix if not present
        if (!cleaned.includes('@')) {
            cleaned = cleaned + '@c.us';
        }
        
        return cleaned;
    }

    delay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

module.exports = new WhatsAppService();

