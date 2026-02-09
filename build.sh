#!/usr/bin/env bash
# exit on error
set -o errexit

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Clear caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Run migrations
php artisan migrate --force --no-interaction

# Optimize
php artisan optimize
