#!/bin/bash

# WhatsML Startup Script
echo "🚀 Starting WhatsML Platform..."

# Kill any existing processes on required ports
echo "📌 Cleaning up existing processes..."
lsof -ti:8000 | xargs kill -9 2>/dev/null || true
lsof -ti:3000 | xargs kill -9 2>/dev/null || true

# Start Laravel Backend
echo "🔧 Starting Laravel Backend on port 8000..."
cd /home/shady/Desktop/New-Sender/backend
php artisan serve --host=0.0.0.0 --port=8000 > /tmp/whatsml-backend.log 2>&1 &
BACKEND_PID=$!
echo "✅ Backend started (PID: $BACKEND_PID)"

# Wait for backend to initialize
sleep 3

# Start WhatsApp Service
echo "📱 Starting WhatsApp Service on port 3000..."
cd /home/shady/Desktop/New-Sender/whatsapp-service
npm start > /tmp/whatsml-whatsapp.log 2>&1 &
WHATSAPP_PID=$!
echo "✅ WhatsApp Service started (PID: $WHATSAPP_PID)"

# Wait a moment for services to start
sleep 2

echo ""
echo "🎉 WhatsML Platform is now running!"
echo ""
echo "📊 Service Status:"
echo "   Backend API: http://localhost:8000"
echo "   WhatsApp Service: http://localhost:3000"
echo ""
echo "📝 Logs:"
echo "   Backend: tail -f /tmp/whatsml-backend.log"
echo "   WhatsApp: tail -f /tmp/whatsml-whatsapp.log"
echo ""
echo "🛑 To stop services:"
echo "   kill $BACKEND_PID $WHATSAPP_PID"
echo ""

# Save PIDs to file
echo "$BACKEND_PID" > /tmp/whatsml-backend.pid
echo "$WHATSAPP_PID" > /tmp/whatsml-whatsapp.pid

echo "PIDs saved to /tmp/whatsml-*.pid"

