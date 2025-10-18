# WhatsML Deployment Guide

This guide will help you deploy WhatsML to production.

## Prerequisites

- Ubuntu 20.04+ server with root access
- Domain name pointed to your server
- At least 4GB RAM and 2 CPU cores
- Docker and Docker Compose installed

## Quick Start with Docker

### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/whatsml.git
cd whatsml
```

### 2. Configure Environment Variables

```bash
cp .env.docker.example .env
nano .env
```

Update the following variables:
- `DB_ROOT_PASSWORD`, `DB_PASSWORD` - Set secure passwords
- `APP_URL` - Your domain name
- API keys for OpenAI, Google Gemini, Google Maps
- Payment gateway credentials (Stripe, PayPal, Coingate)
- SMTP settings for email

### 3. Start Services

```bash
docker-compose up -d
```

This will start:
- MySQL database
- Redis cache
- Laravel backend
- Queue worker
- WhatsApp service
- Nginx web server

### 4. Check Service Status

```bash
docker-compose ps
docker-compose logs -f
```

### 5. Access the Application

- Frontend: http://yourdomain.com
- Backend API: http://yourdomain.com/api
- WhatsApp Service: http://yourdomain.com/whatsapp

## SSL Configuration

### Using Let's Encrypt with Certbot

1. Install Certbot:
```bash
sudo apt-get update
sudo apt-get install certbot python3-certbot-nginx
```

2. Obtain SSL certificate:
```bash
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

3. Update nginx configuration to use SSL (uncomment HTTPS section in nginx/conf.d/default.conf)

4. Restart nginx:
```bash
docker-compose restart nginx
```

## Manual Deployment (Without Docker)

### Backend (Laravel)

1. Install PHP 8.2+ and required extensions:
```bash
sudo apt-get install php8.2 php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-zip php8.2-redis
```

2. Install Composer:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

3. Set up Laravel:
```bash
cd backend
composer install --optimize-autoloader --no-dev
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

4. Configure Nginx:
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/whatsml/backend/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

5. Set up Queue Worker (Supervisor):
```bash
sudo nano /etc/supervisor/conf.d/whatsml-worker.conf
```

```ini
[program:whatsml-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/whatsml/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/whatsml/backend/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start whatsml-worker:*
```

### WhatsApp Service (Node.js)

1. Install Node.js 18+:
```bash
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs
```

2. Set up the service:
```bash
cd whatsapp-service
npm install --production
```

3. Create systemd service:
```bash
sudo nano /etc/systemd/system/whatsml-whatsapp.service
```

```ini
[Unit]
Description=WhatsML WhatsApp Service
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/whatsml/whatsapp-service
ExecStart=/usr/bin/node server.js
Restart=on-failure
Environment=NODE_ENV=production
Environment=PORT=3000

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl enable whatsml-whatsapp
sudo systemctl start whatsml-whatsapp
sudo systemctl status whatsml-whatsapp
```

## Database Backup

### Automated Backup Script

Create a backup script:
```bash
sudo nano /usr/local/bin/whatsml-backup.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/whatsml"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="whatsml"
DB_USER="whatsml_user"
DB_PASS="your_password"

mkdir -p $BACKUP_DIR

# Backup database
mysqldump -u$DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Backup uploaded files
tar -czf $BACKUP_DIR/storage_$DATE.tar.gz /var/www/whatsml/backend/storage

# Backup WhatsApp sessions
tar -czf $BACKUP_DIR/whatsapp_$DATE.tar.gz /var/www/whatsml/whatsapp-service/.wwebjs_auth

# Delete backups older than 30 days
find $BACKUP_DIR -name "*.gz" -mtime +30 -delete

echo "Backup completed: $DATE"
```

```bash
sudo chmod +x /usr/local/bin/whatsml-backup.sh
```

Add to crontab (daily at 2 AM):
```bash
sudo crontab -e
0 2 * * * /usr/local/bin/whatsml-backup.sh
```

## Monitoring

### Application Monitoring

1. Install and configure Laravel Telescope (development):
```bash
cd backend
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

2. Set up server monitoring (Prometheus + Grafana):
```bash
docker run -d -p 9090:9090 prom/prometheus
docker run -d -p 3001:3000 grafana/grafana
```

### Log Management

Logs are stored in:
- Laravel: `backend/storage/logs/laravel.log`
- WhatsApp Service: Check with `docker-compose logs whatsapp_service`
- Nginx: `/var/log/nginx/`

## Security Checklist

- [ ] Change all default passwords
- [ ] Configure firewall (UFW):
  ```bash
  sudo ufw allow 22/tcp
  sudo ufw allow 80/tcp
  sudo ufw allow 443/tcp
  sudo ufw enable
  ```
- [ ] Set up fail2ban for SSH protection
- [ ] Enable HTTPS with SSL certificate
- [ ] Configure CORS properly in `.env`
- [ ] Set `APP_DEBUG=false` in production
- [ ] Restrict database access
- [ ] Set up regular backups
- [ ] Configure rate limiting
- [ ] Enable Laravel security features

## Troubleshooting

### Backend Issues

Check logs:
```bash
docker-compose logs -f backend
tail -f backend/storage/logs/laravel.log
```

Clear cache:
```bash
docker-compose exec backend php artisan cache:clear
docker-compose exec backend php artisan config:clear
docker-compose exec backend php artisan route:clear
docker-compose exec backend php artisan view:clear
```

### WhatsApp Service Issues

Check logs:
```bash
docker-compose logs -f whatsapp_service
```

Restart service:
```bash
docker-compose restart whatsapp_service
```

Clear WhatsApp sessions:
```bash
docker-compose exec whatsapp_service rm -rf .wwebjs_auth/*
docker-compose exec whatsapp_service rm -rf .wwebjs_cache/*
```

### Database Issues

Access MySQL:
```bash
docker-compose exec mysql mysql -u root -p
```

Check connection:
```bash
docker-compose exec backend php artisan tinker
>>> DB::connection()->getPdo();
```

## Performance Optimization

1. Enable OPcache for PHP
2. Configure Redis for caching
3. Set up CDN for static assets
4. Enable Gzip compression in Nginx
5. Optimize database queries
6. Use queue workers for heavy tasks
7. Implement rate limiting

## Scaling

### Horizontal Scaling

1. Set up load balancer (Nginx, HAProxy)
2. Deploy multiple backend instances
3. Use centralized Redis for sessions
4. Use cloud storage for assets (S3, MinIO)
5. Set up database replication

### Vertical Scaling

1. Increase server resources (CPU, RAM)
2. Optimize database indexes
3. Use database query caching
4. Implement application-level caching

## Support

For issues and support:
- Documentation: https://whatsml.com/docs
- GitHub Issues: https://github.com/yourusername/whatsml/issues
- Email: support@whatsml.com

## License

WhatsML is proprietary software. All rights reserved.


