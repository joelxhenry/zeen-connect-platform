# =============================================================================
# Stage 1: Frontend Build
# =============================================================================
FROM node:22-alpine AS frontend

WORKDIR /app

# Copy package files first for better caching
COPY package.json package-lock.json ./

# Install dependencies
RUN npm ci

# Copy frontend source files
COPY resources/ resources/
COPY vite.config.ts tsconfig.json ./
COPY public/ public/

# Build frontend assets
RUN npm run build

# =============================================================================
# Stage 2: Composer Dependencies
# =============================================================================
FROM composer:2 AS vendor

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies without dev packages
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

# Copy application code for autoloader optimization
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# =============================================================================
# Stage 3: Production Runtime
# =============================================================================
FROM php:8.2-fpm-alpine AS production

# Install system dependencies
RUN apk add --no-cache \
    curl \
    nginx \
    supervisor \
    libpng \
    libjpeg-turbo \
    libwebp \
    freetype \
    libzip \
    icu-libs \
    oniguruma \
    postgresql-libs

# Install PHP extension build dependencies and compile extensions
RUN apk add --no-cache --virtual .build-deps \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_pgsql \
        gd \
        exif \
        zip \
        intl \
        pcntl \
        bcmath \
        mbstring \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# Set working directory
WORKDIR /var/www/html

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Copy supervisord configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy PHP configuration
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

# Copy application code
COPY --chown=www-data:www-data . .

# Copy vendor from composer stage
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor

# Copy built frontend assets
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

# Create necessary directories and set permissions
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && mkdir -p storage/logs \
    && mkdir -p bootstrap/cache \
    && mkdir -p /var/log/supervisor \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Expose port
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

# Start supervisord (manages nginx + php-fpm)
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
