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

echo "==> Starting server..."
exec php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
