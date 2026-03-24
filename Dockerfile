# Use PHP 8.3 CLI Alpine (built-in server; no FPM needed)
FROM php:8.3-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql mbstring zip exif pcntl bcmath gd intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install dependencies (update lock file for PHP 8.3)
RUN composer update --no-dev --optimize-autoloader --no-interaction

# Set permissions - make storage fully writable
RUN chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure start.sh has Unix line endings and is executable
RUN sed -i 's/\r//' /var/www/html/start.sh && chmod +x /var/www/html/start.sh

# Expose port
EXPOSE ${PORT:-10000}

# Force sync queue (no queue worker on Render)
ENV QUEUE_CONNECTION=sync

# Start Laravel server via startup script
CMD ["/bin/sh", "/var/www/html/start.sh"]
