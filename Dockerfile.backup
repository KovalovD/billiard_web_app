# Multi-stage build for production
FROM node:22.14-alpine AS node-builder

WORKDIR /app

  # Copy package files
COPY package*.json ./
RUN npm ci

  # Copy source files
COPY . .

  # Build assets
RUN npm run build

  # PHP stage
FROM php:8.4-fpm-alpine

  # Install system dependencies
RUN apk add --no-cache \
nginx \
supervisor \
curl \
zip \
unzip \
git \
libpng-dev \
libjpeg-turbo-dev \
libzip-dev \
icu-dev \
oniguruma-dev \
freetype-dev

  # Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
&& docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath gd intl

  # Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

  # Copy application files
COPY . .

  # Copy built assets from node stage
COPY --from=node-builder /app/public/build ./public/build

  # Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

  # Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

  # Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

  # Create necessary directories
RUN mkdir -p /var/log/supervisor

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
