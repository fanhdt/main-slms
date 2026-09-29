# =============================================================================
# Stage 1: PHP Dependencies
# =============================================================================
FROM composer:2.7 AS composer

WORKDIR /app

# Copy only composer files first to leverage Docker layer caching.
# If composer.json/lock hasn't changed, this layer is reused.
# BENAR
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

# =============================================================================
# Stage 2: Runtime Image
# =============================================================================
FROM php:8.3-fpm-alpine AS runtime

LABEL maintainer="SLMS Team"
LABEL org.opencontainers.image.description="SLMS Backend - Laravel 12"

# ---- System dependencies ----
RUN apk add --no-cache \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        gd \
        zip \
        intl \
        mbstring \
        exif \
        pcntl \
        bcmath \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

# ---- PHP configuration ----
COPY docker/backend/php.ini /usr/local/etc/php/conf.d/slms.ini
COPY docker/backend/php-fpm.conf /usr/local/etc/php-fpm.d/zz-slms.conf

# ---- Application user (non-root for security) ----
RUN addgroup -g 1000 -S slms && \
    adduser -u 1000 -S slms -G slms

WORKDIR /var/www/html

# ---- Copy vendor from composer stage ----
COPY --from=composer --chown=slms:slms /app/vendor ./vendor

# ---- Copy application code ----
# BENAR
COPY --chown=slms:slms . .

# ---- Directories that Laravel writes to ----
RUN mkdir -p storage/logs storage/framework/{cache,sessions,views} \
    bootstrap/cache \
    && chown -R slms:slms storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

USER slms

EXPOSE 9000

CMD ["php-fpm"]

# =============================================================================
# Stage 3: Development image (extends runtime, adds dev tools)
# =============================================================================
FROM runtime AS development

USER root

# Dev-only: xdebug for debugging/profiling
RUN apk add --no-cache $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

# Install composer in dev image for artisan/composer commands
COPY --from=composer /usr/bin/composer /usr/bin/composer

# Full composer install (with dev deps)
COPY composer.json composer.lock /var/www/html/
# RUN composer install --no-interaction --no-scripts

# COPY docker/backend/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

USER slms
CMD php artisan serve --host=0.0.0.0 --port=${PORT:-8080}