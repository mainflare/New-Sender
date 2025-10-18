#!/bin/bash

echo "🚀 Starting WhatsML Complete Platform..."
echo ""

# Get the directory where the script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Stop any existing services
echo "📌 Cleaning up existing processes..."
if [ -f "/tmp/whatsml-backend.pid" ]; then
    kill $(cat /tmp/whatsml-backend.pid) 2>/dev/null
fi
if [ -f "/tmp/whatsml-whatsapp.pid" ]; then
    kill $(cat /tmp/whatsml-whatsapp.pid) 2>/dev/null
fi
if [ -f "/tmp/whatsml-frontend.pid" ]; then
    kill $(cat /tmp/whatsml-frontend.pid) 2>/dev/null
fi
sleep 2

# Start Backend
echo "🔧 Starting Laravel Backend on port 8000..."
cd backend
php artisan serve --host=0.0.0.0 --port=8000 > /tmp/whatsml-backend.log 2>&1 &
BACKEND_PID=$!
echo $BACKEND_PID > /tmp/whatsml-backend.pid
echo "✅ Backend started (PID: $BACKEND_PID)"
cd ..

# Wait a moment for backend to initialize
sleep 2

# Start WhatsApp Service
echo "📱 Starting WhatsApp Service on port 3000..."
cd whatsapp-service
npm start > /tmp/whatsml-whatsapp.log 2>&1 &
WHATSAPP_PID=$!
echo $WHATSAPP_PID > /tmp/whatsml-whatsapp.pid
echo "✅ WhatsApp Service started (PID: $WHATSAPP_PID)"
cd ..

# Wait a moment for WhatsApp service to initialize
sleep 2

# Start Frontend
echo "🎨 Starting React Frontend on port 3001..."
cd frontend

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo "📦 Installing frontend dependencies..."
    npm install
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "📝 Creating frontend .env file..."
    cat > .env << EOF
REACT_APP_API_URL=http://localhost:8000/api
REACT_APP_WHATSAPP_SERVICE_URL=http://localhost:3000/api
REACT_APP_SOCKET_URL=http://localhost:3000
EOF
fi

PORT=3001 npm start > /tmp/whatsml-frontend.log 2>&1 &
FRONTEND_PID=$!
echo $FRONTEND_PID > /tmp/whatsml-frontend.pid
echo "✅ Frontend started (PID: $FRONTEND_PID)"
cd ..

echo ""
echo "🎉 WhatsML Complete Platform is now running!"
echo ""
echo "📊 Service URLs:"
echo "   🎨 Frontend:         http://localhost:3001"
echo "   🔧 Backend API:      http://localhost:8000/api"
echo "   📱 WhatsApp Service: http://localhost:3000/api"
echo ""
echo "📝 View Logs:"
echo "   Backend:   tail -f /tmp/whatsml-backend.log"
echo "   WhatsApp:  tail -f /tmp/whatsml-whatsapp.log"
echo "   Frontend:  tail -f /tmp/whatsml-frontend.log"
echo ""
echo "🛑 To stop all services:"
echo "   kill $BACKEND_PID $WHATSAPP_PID $FRONTEND_PID"
echo "   or run: bash stop-all.sh"
echo ""
echo "PIDs saved to /tmp/whatsml-*.pid"

