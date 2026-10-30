# syntax=docker/dockerfile:1.7

# Zarf — production image for Coolify (and any Docker host).
# Multi-stage: build the Inertia/React assets (Vite + Wayfinder, which needs PHP),
# then ship a lean nginx + php-fpm runtime. Database is MySQL (see docker-compose.yml).

############################################
# Stage 1 — build PHP deps + frontend assets
############################################
FROM serversideup/php:8.3-cli AS build

USER root

# Node 22 for the Vite build.
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl gnupg \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_22.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Composer deps first (cached until composer.json/lock change). Autoload is dumped
# after the full source is copied so package discovery sees every provider.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist

# JS deps next (cached until package-lock changes).
COPY package.json package-lock.json ./
RUN npm ci

# Full source, then build.
COPY . .
RUN cp .env.example .env \
    && composer dump-autoload --optimize --no-dev \
    && php artisan key:generate --force \
    && php artisan wayfinder:generate --with-form \
    && npm run build \
    && rm -f .env public/hot \
    && rm -rf node_modules

############################################
# Stage 2 — runtime (nginx + php-fpm)
############################################
FROM serversideup/php:8.3-fpm-nginx AS runtime

USER root
RUN install-php-extensions pdo_mysql bcmath intl
USER www-data

# serversideup auto-run: on boot, link storage and run migrations (once, isolated).
ENV AUTORUN_ENABLED=true \
    AUTORUN_LARAVEL_STORAGE_LINK=true \
    AUTORUN_LARAVEL_MIGRATION=true \
    AUTORUN_LARAVEL_MIGRATION_ISOLATION=true \
    PHP_OPCACHE_ENABLE=1 \
    SSL_MODE=off

WORKDIR /var/www/html

# Copy the built application (vendor + public/build included) with correct ownership.
COPY --chown=www-data:www-data --from=build /app /var/www/html

# nginx serves on 8080 as the non-root www-data user.
EXPOSE 8080
