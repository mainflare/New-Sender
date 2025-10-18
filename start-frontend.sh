#!/bin/bash

echo "🚀 Starting WhatsML Frontend..."

# Check if frontend directory exists
if [ ! -d "frontend" ]; then
    echo "❌ Frontend directory not found!"
    exit 1
fi

cd frontend

# Check if node_modules exists
if [ ! -d "node_modules" ]; then
    echo "📦 Installing dependencies..."
    npm install
fi

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "📝 Creating .env file..."
    cat > .env << EOF
REACT_APP_API_URL=http://localhost:8000/api
REACT_APP_WHATSAPP_SERVICE_URL=http://localhost:3000/api
REACT_APP_SOCKET_URL=http://localhost:3000
EOF
fi

# Kill any existing frontend process
if [ -f "/tmp/whatsml-frontend.pid" ]; then
    OLD_PID=$(cat /tmp/whatsml-frontend.pid)
    if ps -p $OLD_PID > /dev/null 2>&1; then
        echo "🛑 Stopping existing frontend (PID: $OLD_PID)..."
        kill $OLD_PID 2>/dev/null
        sleep 2
    fi
fi

# Start frontend on port 3001
echo "🔧 Starting React frontend on port 3001..."
PORT=3001 npm start > /tmp/whatsml-frontend.log 2>&1 &
FRONTEND_PID=$!
echo $FRONTEND_PID > /tmp/whatsml-frontend.pid

echo "✅ Frontend started (PID: $FRONTEND_PID)"
echo ""
echo "📊 WhatsML Frontend is now running!"
echo "   Frontend: http://localhost:3001"
echo ""
echo "📝 Logs:"
echo "   Frontend: tail -f /tmp/whatsml-frontend.log"
echo ""
echo "🛑 To stop frontend:"
echo "   kill $FRONTEND_PID"
echo ""
echo "PID saved to /tmp/whatsml-frontend.pid"

