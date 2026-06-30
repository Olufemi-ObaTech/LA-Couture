#!/bin/bash
echo "=============================="
echo " L.A. Couture — Boot Sequence"
echo "=============================="

echo "[1] Clearing ALL caches..."
php /app/artisan optimize:clear 2>&1 || echo "SKIP: optimize:clear"

echo "[2] Running database migrations..."
php /app/artisan migrate --force 2>&1 || echo "WARN: migrate failed — DB may not be ready"

echo "[3] Seeding database..."
php /app/artisan db:seed --force 2>&1 || echo "WARN: seed failed"

echo ""
echo ">>> Starting Laravel on 0.0.0.0:${PORT:-8000}"
exec php /app/artisan serve --host=0.0.0.0 --port=${PORT:-8000}
