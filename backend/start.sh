#!/bin/bash
set -e

echo ">>> [1/5] Clearing config cache..."
php artisan config:clear

echo ">>> [2/5] Building config cache with live env vars..."
php artisan config:cache

echo ">>> [3/5] Caching routes..."
php artisan route:cache

echo ">>> [4/5] Running database migrations..."
php artisan migrate --force

echo ">>> [5/5] Seeding database..."
php artisan db:seed --force

echo ">>> Starting Laravel on 0.0.0.0:${PORT:-8080}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
