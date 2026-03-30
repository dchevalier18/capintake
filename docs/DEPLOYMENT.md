# CAPIntake Deployment Guide

This guide covers deploying CAPIntake to a production environment. It includes Docker, VPS (bare metal), environment configuration, database setup, and ongoing maintenance.

## Table of Contents

- [Docker Deployment](#docker-deployment)
- [VPS Deployment (Ubuntu 22.04)](#vps-deployment-ubuntu-2204)
- [Environment Configuration](#environment-configuration)
- [Database Setup and Seeding](#database-setup-and-seeding)
- [Queue Workers and Cron Jobs](#queue-workers-and-cron-jobs)
- [Backup Strategy](#backup-strategy)
- [Updating and Upgrading](#updating-and-upgrading)

---

## Docker Deployment

### Prerequisites

- Docker Engine 24+ and Docker Compose v2+
- A domain name pointed to your server (for SSL)

### docker-compose.yml

Create a `docker-compose.yml` in your project root:

```yaml
services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    restart: unless-stopped
    volumes:
      - .:/var/www/html
      - storage:/var/www/html/storage
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    depends_on:
      db:
        condition: service_healthy
    networks:
      - capintake

  web:
    image: nginx:1.25-alpine
    restart: unless-stopped
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - .:/var/www/html:ro
      - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf:ro
      - certs:/etc/letsencrypt
    depends_on:
      - app
    networks:
      - capintake

  db:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: "${DB_ROOT_PASSWORD:-secret}"
      MYSQL_DATABASE: "${DB_DATABASE:-capintake}"
      MYSQL_USER: "${DB_USERNAME:-capintake}"
      MYSQL_PASSWORD: "${DB_PASSWORD:-secret}"
    volumes:
      - dbdata:/var/lib/mysql
    ports:
      - "3306:3306"
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - capintake

  queue:
    build:
      context: .
      dockerfile: Dockerfile
    restart: unless-stopped
    command: php artisan queue:work --sleep=3 --tries=3 --max-time=3600
    volumes:
      - .:/var/www/html
      - storage:/var/www/html/storage
    depends_on:
      db:
        condition: service_healthy
    networks:
      - capintake

  scheduler:
    build:
      context: .
      dockerfile: Dockerfile
    restart: unless-stopped
    command: >
      sh -c "while true; do php artisan schedule:run --no-interaction; sleep 60; done"
    volumes:
      - .:/var/www/html
      - storage:/var/www/html/storage
    depends_on:
      db:
        condition: service_healthy
    networks:
      - capintake

volumes:
  dbdata:
  storage:
  certs:

networks:
  capintake:
    driver: bridge
```

### Dockerfile

```dockerfile
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

WORKDIR /var/www/html

# Copy application
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction
RUN npm ci && npm run build

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000
CMD ["php-fpm"]
```

### Nginx Configuration

Create `docker/nginx/default.conf`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/html/public;

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass app:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Running with Docker

```bash
# Copy and configure environment
cp .env.example .env

# Edit .env with production values (see Environment Configuration below)
# Key settings to change:
#   APP_ENV=production
#   APP_DEBUG=false
#   APP_URL=https://your-domain.com
#   DB_CONNECTION=mysql
#   DB_HOST=db
#   DB_DATABASE=capintake
#   DB_USERNAME=capintake
#   DB_PASSWORD=your-secure-password

# Build and start containers
docker compose up -d --build

# Generate application key
docker compose exec app php artisan key:generate

# Run migrations and seed data
docker compose exec app php artisan migrate --seed --force

# Verify everything is running
docker compose ps
```

### Adding SSL with Let's Encrypt

For production, add certbot to your compose setup or use a reverse proxy like Traefik or Caddy. A basic approach using certbot:

```bash
# Install certbot on the host
sudo apt install certbot

# Obtain certificate (stop nginx first or use webroot)
docker compose stop web
sudo certbot certonly --standalone -d your-domain.com
docker compose start web
```

Then update your nginx config to serve HTTPS and mount the certificate volume.

### Using PostgreSQL Instead of MySQL

Replace the `db` service in `docker-compose.yml`:

```yaml
  db:
    image: postgres:15-alpine
    restart: unless-stopped
    environment:
      POSTGRES_DB: "${DB_DATABASE:-capintake}"
      POSTGRES_USER: "${DB_USERNAME:-capintake}"
      POSTGRES_PASSWORD: "${DB_PASSWORD:-secret}"
    volumes:
      - dbdata:/var/lib/postgresql/data
    ports:
      - "5432:5432"
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U ${DB_USERNAME:-capintake}"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - capintake
```

Update the Dockerfile to install `pdo_pgsql` instead of `pdo_mysql`:

```dockerfile
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip
```

And update `.env`:

```
DB_CONNECTION=pgsql
DB_PORT=5432
```

---

## VPS Deployment (Ubuntu 22.04)

This section covers a traditional deployment on a VPS running Ubuntu 22.04 with Nginx, PHP-FPM, and MySQL.

### 1. System Packages

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install prerequisites
sudo apt install -y software-properties-common curl git unzip

# Add PHP repository
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# Install PHP 8.3 and extensions
sudo apt install -y \
    php8.3-fpm \
    php8.3-cli \
    php8.3-mysql \
    php8.3-pgsql \
    php8.3-mbstring \
    php8.3-xml \
    php8.3-curl \
    php8.3-zip \
    php8.3-gd \
    php8.3-bcmath \
    php8.3-intl \
    php8.3-readline

# Install MySQL 8
sudo apt install -y mysql-server

# Install Nginx
sudo apt install -y nginx

# Install Node.js 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 2. MySQL Setup

```bash
# Secure the installation
sudo mysql_secure_installation

# Create database and user
sudo mysql -u root -p
```

```sql
CREATE DATABASE capintake CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'capintake'@'localhost' IDENTIFIED BY 'your-secure-password';
GRANT ALL PRIVILEGES ON capintake.* TO 'capintake'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Application Setup

```bash
# Create web directory
sudo mkdir -p /var/www/capintake
sudo chown $USER:$USER /var/www/capintake

# Clone the repository
git clone https://github.com/your-org/capintake.git /var/www/capintake
cd /var/www/capintake

# Install PHP dependencies (production)
composer install --no-dev --optimize-autoloader --no-interaction

# Install and build frontend
npm ci
npm run build

# Configure environment
cp .env.example .env
nano .env  # Edit with production values (see Environment Configuration below)

# Generate application key
php artisan key:generate

# Run migrations and seed
php artisan migrate --seed --force

# Set permissions
sudo chown -R www-data:www-data /var/www/capintake/storage
sudo chown -R www-data:www-data /var/www/capintake/bootstrap/cache
sudo chmod -R 775 /var/www/capintake/storage
sudo chmod -R 775 /var/www/capintake/bootstrap/cache

# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 4. Nginx Configuration

Create `/etc/nginx/sites-available/capintake`:

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/capintake/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Deny access to sensitive files
    location ~ /\.(env|git|svn) {
        deny all;
        return 404;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/capintake /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default  # Remove default site
sudo nginx -t  # Test configuration
sudo systemctl reload nginx
```

### 5. SSL with Certbot

```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-nginx

# Obtain and install certificate
sudo certbot --nginx -d your-domain.com

# Certbot automatically configures Nginx for HTTPS and sets up auto-renewal
# Verify auto-renewal is working
sudo certbot renew --dry-run
```

### 6. PHP-FPM Tuning

Edit `/etc/php/8.3/fpm/pool.d/www.conf` for your server's capacity:

```ini
; For a small VPS (2GB RAM, 2 vCPUs)
pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500
```

Restart PHP-FPM after changes:

```bash
sudo systemctl restart php8.3-fpm
```

---

## Environment Configuration

The `.env` file controls all runtime configuration. Below is every setting you should review for a production deployment.

### Application Settings

```ini
APP_NAME=CAPIntake
APP_ENV=production
APP_KEY=                          # Generated by php artisan key:generate
APP_DEBUG=false                   # MUST be false in production
APP_URL=https://your-domain.com   # Your public URL, including https://
```

**Important:** `APP_KEY` is used to encrypt all data marked with the `encrypted` cast (SSN fields, etc.). Back up this key securely. If you lose it, encrypted data becomes unrecoverable.

### Database

```ini
DB_CONNECTION=mysql               # mysql, pgsql, or sqlite
DB_HOST=127.0.0.1                 # Database server address
DB_PORT=3306                      # 3306 for MySQL, 5432 for PostgreSQL
DB_DATABASE=capintake             # Database name
DB_USERNAME=capintake             # Database user
DB_PASSWORD=your-secure-password  # Database password
```

For Docker deployments, `DB_HOST` should be the service name (for example, `db`).

### Session and Cache

```ini
SESSION_DRIVER=database           # database, file, redis
SESSION_LIFETIME=120              # Minutes before session expires
SESSION_ENCRYPT=false

CACHE_STORE=database              # database, file, redis
QUEUE_CONNECTION=database         # database, redis, sync
```

For high-traffic installations, consider using Redis for session, cache, and queue:

```ini
SESSION_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### Mail

Configure mail if you want password reset emails and notifications:

```ini
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.com
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="CAPIntake"
```

For development or if you do not need email, leave the default `MAIL_MAILER=log`.

### Logging

```ini
LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=warning                 # Use "warning" or "error" in production
```

### Security Considerations

- **Never set `APP_DEBUG=true` in production.** Debug mode exposes sensitive configuration, environment variables, and stack traces.
- **Use strong, unique passwords** for `DB_PASSWORD` and any other credentials.
- **Back up `APP_KEY`** securely. It is the encryption key for all PII (SSN, etc.). Losing it means losing access to encrypted data.
- **Restrict `.env` file permissions:** `chmod 600 .env` so only the application user can read it.

---

## Database Setup and Seeding

### Initial Setup

```bash
# Run all migrations
php artisan migrate --force

# Seed reference data
php artisan db:seed --force
```

The `--force` flag is required in production environments (when `APP_ENV=production`).

### What the Seeders Create

Seeders run in this order:

1. **FederalPovertyLevelSeeder** -- 2025 HHS Poverty Guidelines for continental US, Alaska, and Hawaii (household sizes 1-8 for each region).
2. **NpiSeeder** -- All 7 NPI goals and their 27 indicators (1.1 through 7.6).
3. **ProgramSeeder** -- Three default programs (CSBG, Emergency Services, Weatherization) with 15 services total.
4. **NpiServiceMappingSeeder** -- Links each service to its relevant NPI indicators.

Seeders use `updateOrCreate` or check for existing data, so they are safe to run multiple times.

### Running Individual Seeders

To run a specific seeder (for example, after updating FPL data for a new year):

```bash
php artisan db:seed --class=FederalPovertyLevelSeeder --force
```

### Database Migrations

When updating CAPIntake, always run migrations after pulling new code:

```bash
php artisan migrate --force
```

---

## Queue Workers and Cron Jobs

### Queue Worker

CAPIntake uses the database queue by default for background jobs. In production, you need a process manager to keep the queue worker running.

#### Systemd Service (Recommended)

Create `/etc/systemd/system/capintake-worker.service`:

```ini
[Unit]
Description=CAPIntake Queue Worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/capintake
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=5
StandardOutput=append:/var/log/capintake/worker.log
StandardError=append:/var/log/capintake/worker-error.log

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo mkdir -p /var/log/capintake
sudo chown www-data:www-data /var/log/capintake
sudo systemctl daemon-reload
sudo systemctl enable capintake-worker
sudo systemctl start capintake-worker
```

After deployments, restart the worker so it picks up new code:

```bash
sudo systemctl restart capintake-worker
```

### Cron (Task Scheduler)

Laravel's task scheduler needs to run every minute. Add to the `www-data` user's crontab:

```bash
sudo crontab -u www-data -e
```

Add this line:

```cron
* * * * * cd /var/www/capintake && php artisan schedule:run >> /dev/null 2>&1
```

---

## Backup Strategy

### What to Back Up

1. **Database** -- All client data, enrollments, service records, and audit logs.
2. **`.env` file** -- Contains `APP_KEY` (encryption key for PII) and all credentials.
3. **`storage/` directory** -- Uploaded files and application logs.

### Database Backup

#### MySQL

```bash
# Full database dump
mysqldump -u capintake -p capintake > /backups/capintake_$(date +%Y%m%d_%H%M%S).sql

# Compressed
mysqldump -u capintake -p capintake | gzip > /backups/capintake_$(date +%Y%m%d_%H%M%S).sql.gz
```

#### PostgreSQL

```bash
pg_dump -U capintake capintake > /backups/capintake_$(date +%Y%m%d_%H%M%S).sql

# Compressed
pg_dump -U capintake capintake | gzip > /backups/capintake_$(date +%Y%m%d_%H%M%S).sql.gz
```

### Automated Daily Backups

Create `/etc/cron.daily/capintake-backup`:

```bash
#!/bin/bash
set -euo pipefail

BACKUP_DIR="/backups/capintake"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
RETENTION_DAYS=30

mkdir -p "$BACKUP_DIR"

# Database backup
mysqldump -u capintake -p'your-password' capintake | gzip > "$BACKUP_DIR/db_${TIMESTAMP}.sql.gz"

# Environment file backup
cp /var/www/capintake/.env "$BACKUP_DIR/env_${TIMESTAMP}.bak"

# Storage directory backup
tar -czf "$BACKUP_DIR/storage_${TIMESTAMP}.tar.gz" -C /var/www/capintake storage/

# Remove backups older than retention period
find "$BACKUP_DIR" -type f -mtime +${RETENTION_DAYS} -delete

echo "Backup completed: ${TIMESTAMP}"
```

Make it executable:

```bash
sudo chmod +x /etc/cron.daily/capintake-backup
```

### Offsite Backups

Store backups offsite. Options include:

- **rsync** to a remote server
- **rclone** to S3, Backblaze B2, or another cloud storage provider
- **Restic** or **BorgBackup** for encrypted, deduplicated backups

Example with rclone to S3:

```bash
rclone sync /backups/capintake remote:your-bucket/capintake-backups/
```

### Restoring from Backup

```bash
# Restore database
gunzip < /backups/capintake/db_20260329_020000.sql.gz | mysql -u capintake -p capintake

# Restore .env
cp /backups/capintake/env_20260329_020000.bak /var/www/capintake/.env

# Restore storage
tar -xzf /backups/capintake/storage_20260329_020000.tar.gz -C /var/www/capintake/
```

**Critical:** The `.env` file contains `APP_KEY`. Without this key, all encrypted fields (SSN, etc.) are permanently unreadable. Always include `.env` in your backup strategy.

---

## Updating and Upgrading

### Standard Update Process

When a new version of CAPIntake is released:

```bash
cd /var/www/capintake

# Enable maintenance mode
php artisan down

# Pull latest code
git pull origin main

# Install updated PHP dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Install updated JavaScript dependencies and rebuild assets
npm ci
npm run build

# Run any new migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue worker to pick up new code
sudo systemctl restart capintake-worker

# Disable maintenance mode
php artisan up
```

### Docker Update Process

```bash
cd /path/to/capintake

# Pull latest code
git pull origin main

# Rebuild containers
docker compose build

# Run migrations
docker compose exec app php artisan migrate --force

# Restart services
docker compose up -d
```

### Major Version Upgrades

For major version changes (for example, Laravel framework upgrades), read the release notes carefully. Major upgrades may require:

- PHP version updates
- Configuration file changes
- Migration adjustments
- Dependency compatibility checks

Always back up the database and `.env` file before a major upgrade. Test the upgrade in a staging environment first.

### Rollback

If an update causes problems:

```bash
# Enable maintenance mode
php artisan down

# Revert to previous code
git checkout <previous-tag-or-commit>

# Reinstall dependencies at that version
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

# Roll back the last batch of migrations if needed
php artisan migrate:rollback

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart queue worker
sudo systemctl restart capintake-worker

# Disable maintenance mode
php artisan up
```

If you need to restore the database, use your most recent backup (see [Restoring from Backup](#restoring-from-backup) above).
