FROM php:8.3-fpm AS base

# Install system dependencies.
# libpq-dev is required to compile pdo_pgsql; libicu-dev to compile intl
# (required by filament/support); libjpeg/libfreetype give gd JPEG +
# TrueType support (agency logos rendered into PDFs by dompdf).
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    libicu-dev \
    zip \
    unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip intl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install Node.js 20
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# ---------- Dependencies stage ----------
FROM base AS dependencies

# Copy dependency manifests first for better layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY package.json package-lock.json ./
RUN npm ci

# ---------- Build stage ----------
FROM dependencies AS build

# Copy application code
COPY . .

# Run post-autoload-dump scripts (package discovery, filament upgrade)
RUN composer run-script post-autoload-dump

# Build frontend assets
RUN npm run build

# Remove node_modules after build — not needed at runtime
RUN rm -rf node_modules

# ---------- Production stage ----------
FROM base AS production

# nginx + supervisor make the container self-contained for single-container
# hosts (Render, Fly): nginx answers HTTP on $PORT and proxies to php-fpm.
# php-fpm still listens on 9000, so docker-compose nginx sidecars keep working.
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    gettext-base \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

WORKDIR /var/www/html

# Copy built application from build stage
COPY --from=build /var/www/html /var/www/html

# HTTP serving config (template rendered with $PORT by the entrypoint)
COPY docker/nginx-standalone.conf.template /etc/nginx/templates/capintake.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/capintake.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/supervisord.conf"]
