# Stage 1: Node – build Vite assets
# ──────────────────────────────────────────────────────────────────────────────
FROM node:22-alpine AS node-builder

WORKDIR /app
COPY package*.json ./
RUN npm ci --silent
COPY . .
RUN npm run build

# ──────────────────────────────────────────────────────────────────────────────
# Stage 2: Composer – install PHP dependencies (no dev)
# ──────────────────────────────────────────────────────────────────────────────
FROM composer:2 AS composer-builder

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs \
    --quiet

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --quiet --no-scripts

# ──────────────────────────────────────────────────────────────────────────────
# Stage 3: Production image
# ──────────────────────────────────────────────────────────────────────────────
FROM php:8.4-fpm-alpine AS production

LABEL maintainer="Opticedge Credit <ops@opticedge.co.tz>"

# ── System deps ────────────────────────────────────────────────────────────────
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    supervisor \
    nginx \
    tzdata \
    && cp /usr/share/zoneinfo/Africa/Dar_es_Salaam /etc/localtime \
    && echo "Africa/Dar_es_Salaam" > /etc/timezone

# ── PHP extensions ─────────────────────────────────────────────────────────────
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        xml \
dom \
        opcache


# ── Redis extension (via PECL) ─────────────────────────────────────────────────
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# ── Copy PHP config files ──────────────────────────────────────────────────────
COPY docker/php/php.ini        /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/php-fpm.conf   /usr/local/etc/php-fpm.d/www.conf

# ── Nginx config ───────────────────────────────────────────────────────────────
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# ── Supervisor config ──────────────────────────────────────────────────────────
COPY docker/supervisord.conf   /etc/supervisor/conf.d/supervisord.conf

# ── App source ─────────────────────────────────────────────────────────────────
WORKDIR /var/www/html

COPY --from=composer-builder /app/vendor       ./vendor
COPY --from=composer-builder /app              .
COPY --from=node-builder     /app/public/build ./public/build

# ── Permissions ────────────────────────────────────────────────────────────────
RUN mkdir -p storage/framework/{sessions,views,cache} storage/logs storage/app/public bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R 775 storage bootstrap/cache

# ── Entrypoint ─────────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80 443

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
