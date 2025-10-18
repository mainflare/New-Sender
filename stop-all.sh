#!/bin/bash

echo "🛑 Stopping WhatsML Platform..."

# Stop Backend
if [ -f "/tmp/whatsml-backend.pid" ]; then
    BACKEND_PID=$(cat /tmp/whatsml-backend.pid)
    if ps -p $BACKEND_PID > /dev/null 2>&1; then
        echo "Stopping Backend (PID: $BACKEND_PID)..."
        kill $BACKEND_PID 2>/dev/null
    fi
    rm /tmp/whatsml-backend.pid
fi

# Stop WhatsApp Service
if [ -f "/tmp/whatsml-whatsapp.pid" ]; then
    WHATSAPP_PID=$(cat /tmp/whatsml-whatsapp.pid)
    if ps -p $WHATSAPP_PID > /dev/null 2>&1; then
        echo "Stopping WhatsApp Service (PID: $WHATSAPP_PID)..."
        kill $WHATSAPP_PID 2>/dev/null
    fi
    rm /tmp/whatsml-whatsapp.pid
fi

# Stop Frontend
if [ -f "/tmp/whatsml-frontend.pid" ]; then
    FRONTEND_PID=$(cat /tmp/whatsml-frontend.pid)
    if ps -p $FRONTEND_PID > /dev/null 2>&1; then
        echo "Stopping Frontend (PID: $FRONTEND_PID)..."
        kill $FRONTEND_PID 2>/dev/null
    fi
    rm /tmp/whatsml-frontend.pid
fi

echo "✅ All services stopped!"

