# 🔐 WhatsML Login Credentials

## 🌐 Access URLs

### **Frontend Dashboard**
- **URL**: http://localhost:3001
- **Login Page**: http://localhost:3001/login
- **Register Page**: http://localhost:3001/register

---

## 👑 **Super Admin Account**
**Full system access - can manage all users, subscriptions, and system settings**

- **Email**: `admin@whatsml.com`
- **Password**: `admin123`
- **Role**: `super_admin`
- **Access**: 
  - ✅ All user features
  - ✅ Admin dashboard
  - ✅ User management
  - ✅ Subscription management
  - ✅ System settings
  - ✅ Payment management
  - ✅ System health monitoring

---

## 🛡️ **Admin Account**
**Workspace management and team oversight**

- **Email**: `admin@example.com`
- **Password**: `admin123`
- **Role**: `admin`
- **Access**:
  - ✅ All user features
  - ✅ Workspace management
  - ✅ Team member management
  - ✅ Campaign creation
  - ✅ Analytics access

---

## 👤 **Regular User Accounts**
**Standard user access for agents and team members**

### User 1
- **Email**: `user@example.com`
- **Password**: `user123`
- **Role**: `user`
- **Access**:
  - ✅ View conversations
  - ✅ Send messages
  - ✅ View contacts
  - ✅ View campaigns
  - ✅ Basic dashboard access

### User 2
- **Email**: `test@example.com`
- **Password**: `test123`
- **Role**: `user`
- **Access**:
  - ✅ View conversations
  - ✅ Send messages
  - ✅ View contacts
  - ✅ View campaigns
  - ✅ Basic dashboard access

---

## 🚀 **Quick Start Guide**

### **1. Access the Platform**
1. Open your browser
2. Go to: **http://localhost:3001**
3. You'll be redirected to the login page

### **2. Login as Super Admin**
1. Click on the login page
2. Enter:
   - **Email**: `admin@whatsml.com`
   - **Password**: `admin123`
3. Click "Sign in"
4. You'll be redirected to the dashboard

### **3. Explore Features**
- **Dashboard**: Overview of analytics and quick actions
- **WhatsApp**: Connect WhatsApp accounts
- **Campaigns**: Create and manage marketing campaigns
- **Contacts**: Import and manage your contact list
- **Admin Panel**: System management (super admin only)

---

## 🔧 **Backend API Access**

### **API Base URL**
- **URL**: http://localhost:8000/api
- **Health Check**: http://localhost:8000/api/health

### **Authentication**
- All API requests require a Bearer token
- Token is automatically included when logged in via frontend
- For direct API access, use the login endpoint to get a token

### **Login Endpoint**
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@whatsml.com","password":"admin123"}'
```

---

## 📱 **WhatsApp Service**

### **Service URL**
- **URL**: http://localhost:3000
- **Health Check**: http://localhost:3000/health

---

## 🛠️ **Troubleshooting**

### **If Login Fails**
1. Check if backend is running: `curl http://localhost:8000/api/health`
2. Check if frontend is running: `curl http://localhost:3001`
3. Clear browser localStorage and try again
4. Check browser console for errors

### **If You Can't Access Admin Features**
1. Make sure you're logged in as `admin@whatsml.com` (super admin)
2. Check your user role in the database
3. Clear browser cache and localStorage

### **Reset Users (if needed)**
```bash
cd backend
php artisan db:seed --class=UserSeeder
```

---

## 🔒 **Security Notes**

⚠️ **These are default credentials for development/testing only!**

- Change all passwords before deploying to production
- Use strong, unique passwords
- Enable 2FA in production
- Regularly rotate API keys and tokens
- Monitor access logs

---

## 📞 **Support**

If you encounter any issues:
1. Check the logs: `tail -f /tmp/whatsml-backend.log`
2. Check frontend logs: `tail -f /tmp/whatsml-frontend.log`
3. Verify all services are running: `bash start-all.sh`
4. Check the documentation in `README.md`

---

**Happy Testing! 🎉**
