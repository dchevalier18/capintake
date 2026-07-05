#!/bin/sh
set -e

echo "==> CAPIntake container starting..."

# On Render, derive APP_URL (with https:// scheme) from the injected
# RENDER_EXTERNAL_URL so asset and redirect URLs are generated correctly.
if [ -n "$RENDER_EXTERNAL_URL" ] && [ -z "$APP_URL" ]; then
    export APP_URL="$RENDER_EXTERNAL_URL"
    echo "==> APP_URL set from RENDER_EXTERNAL_URL: $APP_URL"
fi

# Normalize APP_KEY into a valid, STABLE Laravel key. Laravel only
# base64-decodes a key that begins with "base64:"; otherwise it uses the
# string as raw AES-256-CBC key bytes. Render's `generateValue: true`
# injects a bare random string (no prefix, not 32 raw bytes), so cookie/
# session encryption throws "Unsupported cipher or incorrect key length"
# and every web route 500s while /up (no session) still passes its health
# check. The derivation below is deterministic, so the key stays identical
# across restarts/deploys and encrypted PII (SSN/DOB/income) remains
# decryptable. Runs before config:cache so the cached config captures it.
if [ -n "$APP_KEY" ]; then
    case "$APP_KEY" in
        base64:*)
            : # already a Laravel key (user-supplied) — leave it as-is
            ;;
        *)
            if php -r '$d = base64_decode(getenv("APP_KEY"), true); exit($d !== false && strlen($d) === 32 ? 0 : 1);'; then
                # Valid base64 of 32 bytes — just add the prefix Laravel needs.
                export APP_KEY="base64:$APP_KEY"
            else
                # Anything else (e.g. Render's bare value) — derive a stable
                # 32-byte key from it so the format is always valid.
                export APP_KEY="base64:$(php -r 'echo base64_encode(hash("sha256", getenv("APP_KEY"), true));')"
            fi
            echo "==> APP_KEY normalized to a valid base64 Laravel key"
            ;;
    esac
fi

# Render the standalone nginx config for the container's HTTP port.
# Only ${PORT} is substituted so nginx's own $variables are untouched.
if [ -f /etc/nginx/templates/capintake.conf.template ]; then
    export PORT="${PORT:-8080}"
    envsubst '$PORT' < /etc/nginx/templates/capintake.conf.template > /etc/nginx/conf.d/capintake.conf
    echo "==> nginx configured to listen on port $PORT"
fi

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
