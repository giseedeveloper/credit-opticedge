#!/bin/bash
set -e

APP_DIR="/var/www/html"

echo "──────────────────────────────────────────"
echo "  Opticedge Credit – Container Startup"
echo "──────────────────────────────────────────"

# ── Wait for MySQL to be ready ─────────────────────────────────────────────────
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" = "mysql" ]; then
    echo "[1/6] Waiting for MySQL at $DB_HOST:${DB_PORT:-3306}..."
    until php -r "new PDO('mysql:host=$DB_HOST;port=${DB_PORT:-3306};dbname=$DB_DATABASE', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; do
        sleep 2
    done
    echo "      MySQL ready."
fi

# ── Generate app key if missing ────────────────────────────────────────────────
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "[2/6] Generating APP_KEY..."
    php "$APP_DIR/artisan" key:generate --force
else
    echo "[2/6] APP_KEY present – skipping."
fi

# ── Run migrations ─────────────────────────────────────────────────────────────
echo "[3/6] Running migrations..."
php "$APP_DIR/artisan" migrate --force --no-interaction

# ── Seed roles & permissions (only if table empty) ────────────────────────────
echo "[4/6] Seeding roles & permissions if needed..."
php "$APP_DIR/artisan" db:seed --class=RolesAndPermissionsSeeder --force --no-interaction 2>/dev/null || true

# ── Warm caches ────────────────────────────────────────────────────────────────
echo "[5/6] Warming caches..."
php "$APP_DIR/artisan" config:cache
php "$APP_DIR/artisan" route:cache
php "$APP_DIR/artisan" view:cache
php "$APP_DIR/artisan" event:cache

# ── Fix permissions ────────────────────────────────────────────────────────────
echo "[6/6] Fixing storage permissions..."
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "──────────────────────────────────────────"
echo "  Startup complete. Starting services..."
echo "──────────────────────────────────────────"

exec "$@"
