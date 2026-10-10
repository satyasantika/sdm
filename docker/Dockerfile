# syntax=docker/dockerfile:1
# Image produksi SDM FKIP: PHP-FPM 8.4 (target "app") dan Nginx (target "web") dari satu Dockerfile.

# ---- (a) aset Vite -------------------------------------------------------------------------------
FROM node:22-alpine AS aset
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
# Subpath produksi (STANDAR-TEKNIS §2.4): ASSET_URL menjadi base Vite agar url() di CSS (font) berawalan /sdm/build/.
ARG ASSET_URL=https://supportfkip.unsil.ac.id/sdm
RUN ASSET_URL="$ASSET_URL" npm run build

# ---- (b) dependensi composer ---------------------------------------------------------------------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Ekstensi PHP belum ada di image ini; platform diperiksa lagi saat package:discover di tahap app.
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist --optimize-autoloader --ignore-platform-reqs

# ---- (c) runtime PHP-FPM -------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS app
COPY --from=ghcr.io/mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql redis intl gd zip bcmath opcache pcntl \
    && apk add --no-cache fcgi mysql-client \
    && rm -rf /var/cache/apk/*

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/produksi.ini /usr/local/etc/php/conf.d/zz-produksi.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=aset --chown=www-data:www-data /build/public/build ./public/build
RUN rm -rf docker tests docs vibecoding .github \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/tmp bootstrap/cache \
    && php artisan package:discover --ansi \
    && php artisan filament:assets --ansi \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
ENV APP_ENV=production APP_DEBUG=false
EXPOSE 9000
ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

# ---- (d) Nginx statis + proxy FastCGI ------------------------------------------------------------
FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
EXPOSE 80
