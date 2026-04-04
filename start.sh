#!/bin/sh

echo "==> Linking storage..."
php artisan storage:link || true

echo "==> Caching config..."
php artisan config:cache || true

echo "==> Caching routes..."
php artisan route:cache || true

echo "==> Caching views..."
php artisan view:cache || true

echo "==> Running migrations..."
php artisan migrate --force || true

echo "==> Running seeders..."
php artisan db:seed --force || true

echo "==> Publishing Filament assets..."
php artisan filament:assets || true

echo "==> Starting server on port ${PORT:-10000}..."
exec php -S 0.0.0.0:${PORT:-10000} -t /var/www/html/public /var/www/html/public/router.php
