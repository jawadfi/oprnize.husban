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

# Ensure provider support account exists (idempotent)
php artisan app:ensure-provider-support --email=support@init.com --password='Support@123' --no-interaction

# Optimize
php artisan optimize
