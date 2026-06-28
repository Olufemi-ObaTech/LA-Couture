#!/bin/bash
echo "=============================="
echo " L.A. Couture — Boot Sequence"
echo "=============================="

echo ""
echo "[1/5] Clearing config cache..."
php /app/artisan config:clear

echo "[2/5] Rebuilding config cache with live env vars..."
php /app/artisan config:cache

echo "[3/5] Caching routes..."
php /app/artisan route:cache

echo "[4/5] Running database migrations..."
php /app/artisan migrate --force

echo "[5/5] Seeding database..."
php /app/artisan db:seed --force

echo ""
echo ">>> Starting server on 0.0.0.0:${PORT:-8080}"
exec php /app/artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
