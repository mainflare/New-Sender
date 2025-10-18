#!/bin/bash

echo "🧪 Testing WhatsML Dashboards..."
echo "=================================="

# Test 1: Check if services are running
echo "1. Checking services..."
if curl -s http://localhost:8000/api/health > /dev/null; then
    echo "✅ Backend API is running"
else
    echo "❌ Backend API is not running"
    exit 1
fi

if curl -s http://localhost:3001 > /dev/null; then
    echo "✅ Frontend is running"
else
    echo "❌ Frontend is not running"
    exit 1
fi

# Test 2: Test admin login API
echo ""
echo "2. Testing admin login API..."
ADMIN_RESPONSE=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@whatsml.com","password":"admin123"}')

if echo "$ADMIN_RESPONSE" | grep -q '"success":true'; then
    echo "✅ Admin login API working"
    ADMIN_ROLE=$(echo "$ADMIN_RESPONSE" | grep -o '"role":"[^"]*"' | cut -d'"' -f4)
    echo "   Role: $ADMIN_ROLE"
else
    echo "❌ Admin login API failed"
    echo "   Response: $ADMIN_RESPONSE"
fi

# Test 3: Test user login API
echo ""
echo "3. Testing user login API..."
USER_RESPONSE=$(curl -s -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"user123"}')

if echo "$USER_RESPONSE" | grep -q '"success":true'; then
    echo "✅ User login API working"
    USER_ROLE=$(echo "$USER_RESPONSE" | grep -o '"role":"[^"]*"' | cut -d'"' -f4)
    echo "   Role: $USER_ROLE"
else
    echo "❌ User login API failed"
    echo "   Response: $USER_RESPONSE"
fi

# Test 4: Test frontend routes
echo ""
echo "4. Testing frontend routes..."
if curl -s http://localhost:3001/login | grep -q "WhatsML"; then
    echo "✅ Login page accessible"
else
    echo "❌ Login page not accessible"
fi

if curl -s http://localhost:3001/test | grep -q "Login Test Dashboard"; then
    echo "✅ Test dashboard accessible"
else
    echo "❌ Test dashboard not accessible"
fi

# Test 5: Check for compilation errors
echo ""
echo "5. Checking for compilation errors..."
if tail -5 /tmp/whatsml-frontend.log | grep -q "webpack compiled"; then
    echo "✅ Frontend compiled successfully"
else
    echo "❌ Frontend compilation issues detected"
    echo "   Last 5 lines of frontend log:"
    tail -5 /tmp/whatsml-frontend.log
fi

echo ""
echo "🎉 Dashboard testing complete!"
echo ""
echo "📋 Next steps:"
echo "1. Go to http://localhost:3001/test to run interactive tests"
echo "2. Test admin login: admin@whatsml.com / admin123"
echo "3. Test user login: user@example.com / user123"
echo "4. Verify redirects work properly"
echo ""
echo "🔗 Quick links:"
echo "   Test Dashboard: http://localhost:3001/test"
echo "   Login Page: http://localhost:3001/login"
echo "   Admin Dashboard: http://localhost:3001/admin (after admin login)"
echo "   User Dashboard: http://localhost:3001/user (after user login)"
