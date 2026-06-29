#!/bin/bash
echo "=============================="
echo " L.A. Couture — Boot Sequence"
echo "=============================="

echo "[1] Clearing config cache..."
php /app/artisan config:clear 2>&1 || echo "SKIP: config:clear"

echo "[2] Caching config with live env vars..."
php /app/artisan config:cache 2>&1 || echo "SKIP: config:cache"

echo "[3] Caching routes..."
php /app/artisan route:cache 2>&1 || echo "SKIP: route:cache"

echo "[4] Running database migrations..."
php /app/artisan migrate --force 2>&1 || echo "WARN: migrate failed — DB may not be ready"

echo "[5] Seeding database..."
php /app/artisan db:seed --force 2>&1 || echo "WARN: seed failed"

echo ""
echo ">>> Starting Laravel on 0.0.0.0:8001 (Caddy proxies $PORT -> 8001)"
exec php /app/artisan serve --host=0.0.0.0 --port=8001
