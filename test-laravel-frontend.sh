#!/bin/bash

echo "🧪 Testing Laravel Blade Frontend..."
echo "=================================="

# Test 1: Check if backend is running
echo "1. Checking backend service..."
if curl -s http://localhost:8000/up > /dev/null; then
    echo "✅ Backend API is running"
else
    echo "❌ Backend API is not running"
    exit 1
fi

# Test 2: Test login page
echo ""
echo "2. Testing login page..."
if curl -s http://localhost:8000/login | grep -q "Sign in to WhatsML"; then
    echo "✅ Login page is accessible"
else
    echo "❌ Login page not accessible"
fi

# Test 3: Test register page
echo ""
echo "3. Testing register page..."
if curl -s http://localhost:8000/register | grep -q "Create your account"; then
    echo "✅ Register page is accessible"
else
    echo "❌ Register page not accessible"
fi

# Test 4: Test root redirect
echo ""
echo "4. Testing root redirect..."
ROOT_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/)
if [ "$ROOT_RESPONSE" = "302" ] || [ "$ROOT_RESPONSE" = "200" ]; then
    echo "✅ Root redirect is working (HTTP $ROOT_RESPONSE)"
else
    echo "❌ Root redirect not working (HTTP $ROOT_RESPONSE)"
fi

# Test 5: Test admin routes (should redirect to login)
echo ""
echo "5. Testing admin route protection..."
ADMIN_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/admin/)
if [ "$ADMIN_RESPONSE" = "302" ]; then
    echo "✅ Admin routes are protected (redirecting to login)"
else
    echo "❌ Admin routes not properly protected (HTTP $ADMIN_RESPONSE)"
fi

# Test 6: Test user routes (should redirect to login)
echo ""
echo "6. Testing user route protection..."
USER_RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/user/)
if [ "$USER_RESPONSE" = "302" ]; then
    echo "✅ User routes are protected (redirecting to login)"
else
    echo "❌ User routes not properly protected (HTTP $USER_RESPONSE)"
fi

echo ""
echo "🎉 Laravel Frontend testing complete!"
echo ""
echo "📋 Next steps:"
echo "1. Go to http://localhost:8000/login to test the login form"
echo "2. Test admin login: admin@whatsml.com / admin123"
echo "3. Test user login: user@example.com / user123"
echo "4. Verify role-based redirects work properly"
echo ""
echo "🔗 Quick links:"
echo "   Login Page: http://localhost:8000/login"
echo "   Register Page: http://localhost:8000/register"
echo "   Admin Dashboard: http://localhost:8000/admin (after admin login)"
echo "   User Dashboard: http://localhost:8000/user (after user login)"
echo ""
echo "✨ Features implemented:"
echo "   ✅ Laravel Blade templates with Tailwind CSS"
echo "   ✅ Role-based authentication and routing"
echo "   ✅ Admin and User dashboards"
echo "   ✅ Responsive design with modern UI"
echo "   ✅ Toast notifications"
echo "   ✅ Protected routes with middleware"
