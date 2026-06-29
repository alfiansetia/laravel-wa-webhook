#!/bin/sh
set -e

echo "========================================"
echo "  WAHA Dashboard - Container Startup"
echo "========================================"

cd /var/www/html

# ── 1. Install dependencies jika vendor belum ada ──
if [ ! -f "vendor/autoload.php" ]; then
    echo "[1/5] Installing composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
else
    echo "[1/5] Vendor already installed, skipping."
fi

# ── 2. Generate APP_KEY jika belum ada ──
if grep -q "^APP_KEY=$" .env 2>/dev/null || ! grep -q "^APP_KEY=" .env 2>/dev/null; then
    echo "[2/5] Generating application key..."
    php artisan key:generate --force
else
    echo "[2/5] APP_KEY already set, skipping."
fi

# ── 3. Buat file SQLite jika belum ada ──
echo "[3/5] Checking SQLite database..."
if [ ! -f "database/database.sqlite" ]; then
    echo "      Creating database/database.sqlite..."
    touch database/database.sqlite
fi

# ── 4. Jalankan migration ──
echo "[4/5] Running database migrations..."
php artisan migrate --force

# ── 5. Set permissions & cache ──
echo "[5/5] Setting permissions & caching..."
chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database
php artisan storage:link --force 2>/dev/null || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "========================================"
echo "  Setup complete! Starting PHP-FPM..."
echo "========================================"

exec "$@"
