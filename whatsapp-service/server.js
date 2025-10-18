const express = require('express');
const http = require('http');
const socketIo = require('socket.io');
const cors = require('cors');
const bodyParser = require('body-parser');
require('dotenv').config();

const app = express();
const server = http.createServer(app);
const io = socketIo(server, {
    cors: {
        origin: process.env.ALLOWED_ORIGINS || '*',
        methods: ['GET', 'POST']
    }
});

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));

// Import routes
const whatsappRoutes = require('./routes/whatsapp');
const messageRoutes = require('./routes/messages');
const cloudApiRoutes = require('./routes/cloudApi');

// Middleware to attach io to request
app.use((req, res, next) => {
    req.io = io;
    next();
});

// Use routes
app.use('/api/whatsapp', whatsappRoutes);
app.use('/api/messages', messageRoutes);
app.use('/api/cloud-api', cloudApiRoutes);

// Socket.io connection
io.on('connection', (socket) => {
    console.log('New client connected:', socket.id);

    socket.on('disconnect', () => {
        console.log('Client disconnected:', socket.id);
    });

    socket.on('join-workspace', (workspaceId) => {
        socket.join(`workspace-${workspaceId}`);
        console.log(`Socket ${socket.id} joined workspace ${workspaceId}`);
    });
});

// Make io accessible to routes
app.set('io', io);

// Health check endpoint
app.get('/health', (req, res) => {
    res.json({ status: 'ok', message: 'WhatsApp Service is running' });
});

const PORT = process.env.PORT || 3000;

server.listen(PORT, () => {
    console.log(`WhatsApp Service running on port ${PORT}`);
});

module.exports = { app, io };

