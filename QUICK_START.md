# WhatsML - Quick Start Guide

Get WhatsML up and running in 5 minutes!

## Prerequisites

- Docker and Docker Compose installed
- 4GB RAM minimum
- 10GB free disk space

## Installation Steps

### 1. Clone & Configure

```bash
# Clone the repository
cd /home/shady/Desktop/New-Sender

# Create environment file
cp .env.docker.example .env

# Edit configuration (set passwords and API keys)
nano .env
```

### 2. Start Services

```bash
# Start all services
docker-compose up -d

# Watch logs
docker-compose logs -f
```

### 3. Verify Installation

Check if all services are running:
```bash
docker-compose ps
```

You should see:
- ✅ whatsml_mysql (healthy)
- ✅ whatsml_redis (healthy)
- ✅ whatsml_backend (running)
- ✅ whatsml_queue (running)
- ✅ whatsml_whatsapp (running)
- ✅ whatsml_nginx (running)

### 4. Access the Application

- **Backend API**: http://localhost:8000/api
- **WhatsApp Service**: http://localhost:3000
- **Health Check**: http://localhost:8000/health

### 5. Test API

```bash
# Register a user
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'

# Login
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

## Common Commands

### Docker Management

```bash
# Stop all services
docker-compose down

# Restart a specific service
docker-compose restart backend

# View logs
docker-compose logs -f backend
docker-compose logs -f whatsapp_service

# Access container shell
docker-compose exec backend bash
docker-compose exec whatsapp_service sh
```

### Laravel Commands

```bash
# Run migrations
docker-compose exec backend php artisan migrate

# Clear cache
docker-compose exec backend php artisan cache:clear

# Create admin user
docker-compose exec backend php artisan tinker
>>> User::create(['name'=>'Admin','email'=>'admin@example.com','password'=>bcrypt('admin123'),'role'=>'super_admin']);
```

### Database Access

```bash
# Access MySQL
docker-compose exec mysql mysql -u root -p

# Access Redis
docker-compose exec redis redis-cli
```

## Development Mode

For local development without Docker:

### Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

### WhatsApp Service (Node.js)

```bash
cd whatsapp-service
npm install
npm start
```

### Queue Worker

```bash
cd backend
php artisan queue:work
```

## Environment Variables

Key variables to configure in `.env`:

```env
# Database
DB_DATABASE=whatsml
DB_USERNAME=whatsml_user
DB_PASSWORD=your_secure_password

# API Keys
OPENAI_API_KEY=sk-...
GOOGLE_GEMINI_API_KEY=...
GOOGLE_MAPS_API_KEY=...

# Payment Gateways
STRIPE_SECRET=sk_...
PAYPAL_CLIENT_ID=...
COINGATE_API_KEY=...

# Mail
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=...
MAIL_PASSWORD=...
```

## Troubleshooting

### Port Already in Use

If ports 8000, 3000, or 3306 are in use:

```bash
# Change ports in .env
HTTP_PORT=8080
BACKEND_PORT=8001
WHATSAPP_SERVICE_PORT=3001
DB_PORT=3307
```

### Permission Issues

```bash
# Fix storage permissions
docker-compose exec backend chmod -R 775 storage bootstrap/cache
docker-compose exec backend chown -R www-data:www-data storage bootstrap/cache
```

### Database Connection Failed

```bash
# Wait for MySQL to be ready
docker-compose restart backend

# Check MySQL is running
docker-compose logs mysql
```

### WhatsApp Session Issues

```bash
# Clear WhatsApp sessions
docker-compose exec whatsapp_service rm -rf .wwebjs_auth/*
docker-compose exec whatsapp_service rm -rf .wwebjs_cache/*
docker-compose restart whatsapp_service
```

## API Testing with Postman

Import the API collection:
1. Open Postman
2. Import `docs/WhatsML_API_Collection.json`
3. Set environment variables
4. Start testing!

## Next Steps

1. ✅ Create your first workspace
2. ✅ Connect WhatsApp account
3. ✅ Import contacts
4. ✅ Create a campaign
5. ✅ Set up AI chatbot
6. ✅ Configure automation rules

## Support

- 📖 Full Documentation: See `README.md`
- 🚀 Deployment Guide: See `DEPLOYMENT.md`
- 📋 API Reference: See `API_DOCUMENTATION.md`

## Production Deployment

For production deployment with SSL, monitoring, and backups, see `DEPLOYMENT.md`.

---

**Happy building with WhatsML! 🚀**


