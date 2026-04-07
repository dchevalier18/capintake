#!/bin/sh
set -e

echo "==> CAPIntake container starting..."

# Wait for database to be ready (works with MySQL and PostgreSQL)
if [ -n "$DB_HOST" ]; then
    echo "==> Waiting for database at $DB_HOST..."
    timeout=60
    while ! php -r "try { \$pdo = new PDO(getenv('DB_CONNECTION') === 'pgsql' ? 'pgsql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:'5432') : 'mysql:host='.getenv('DB_HOST').';port='.(getenv('DB_PORT')?:'3306'), getenv('DB_USERNAME'), getenv('DB_PASSWORD')); exit(0); } catch (Exception \$e) { exit(1); }" 2>/dev/null; do
        timeout=$((timeout - 1))
        if [ $timeout -le 0 ]; then
            echo "ERROR: Database connection timed out after 60s"
            exit 1
        fi
        sleep 1
    done
    echo "==> Database is ready"
fi

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ]; then
    if [ -f .env ] && grep -q "^APP_KEY=$" .env; then
        echo "==> Generating application key..."
        php artisan key:generate --force
    fi
fi

# Run migrations
echo "==> Running migrations..."
php artisan migrate --force

# Seed if database is empty (first boot)
LOOKUP_COUNT=$(php artisan tinker --execute="echo \App\Models\LookupCategory::count();" 2>/dev/null | tr -d '[:space:]')
if [ "$LOOKUP_COUNT" = "0" ] || [ -z "$LOOKUP_COUNT" ]; then
    echo "==> First boot detected — seeding reference data..."
    php artisan db:seed --force
fi

# Cache configuration for production
if [ "$APP_ENV" = "production" ]; then
    echo "==> Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Create storage symlink if it doesn't exist
php artisan storage:link 2>/dev/null || true

echo "==> CAPIntake is ready"

# Execute the main container command (php-fpm, queue:work, etc.)
exec "$@"
