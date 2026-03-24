#!/bin/sh
set -e

echo "==> Linking storage..."
php artisan storage:link || true

echo "==> Caching config..."
php artisan config:cache

echo "==> Caching routes..."
php artisan route:cache

echo "==> Caching views..."
php artisan view:cache

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Running seeders..."
php artisan db:seed --force

echo "==> Publishing Filament assets..."
php artisan filament:assets || true

echo "==> Starting server..."
exec php -S 0.0.0.0:${PORT:-10000} -t /var/www/html/public /var/www/html/public/router.php
