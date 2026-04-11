#!/bin/bash
set -e

APP_DIR="/var/www/html"

echo "──────────────────────────────────────────"
echo "  Opticedge Credit – Container Startup"
echo "──────────────────────────────────────────"

# ── Wait for MySQL to be ready ─────────────────────────────────────────────────
if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" = "mysql" ]; then
    echo "[1/7] Waiting for MySQL at $DB_HOST:${DB_PORT:-3306}..."
    until php -r "new PDO('mysql:host=$DB_HOST;port=${DB_PORT:-3306};dbname=$DB_DATABASE', '$DB_USERNAME', '$DB_PASSWORD');" 2>/dev/null; do
        sleep 2
    done
    echo "      MySQL ready."
fi

# ── Generate app key if missing ────────────────────────────────────────────────
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "[2/7] Generating APP_KEY..."
    php "$APP_DIR/artisan" key:generate --force
else
    echo "[2/7] APP_KEY present – skipping."
fi

# ── Run migrations ─────────────────────────────────────────────────────────────
echo "[3/7] Running migrations..."
php "$APP_DIR/artisan" migrate --force

# ── Seed roles & permissions (only if table empty) ────────────────────────────
echo "[4/7] Seeding roles & permissions if needed..."
php "$APP_DIR/artisan" db:seed --class=RolesAndPermissionsSeeder --force --no-interaction 2>/dev/null || true

# ── Storage symlink ───────────────────────────────────────────────────────────
echo "[5/7] Creating storage symlink..."
php "$APP_DIR/artisan" storage:link --force --no-interaction 2>/dev/null || true

# ── Warm caches ────────────────────────────────────────────────────────────────
echo "[6/7] Warming caches..."
php "$APP_DIR/artisan" config:cache
php "$APP_DIR/artisan" route:cache
php "$APP_DIR/artisan" view:cache
php "$APP_DIR/artisan" event:cache

# ── Fix permissions ────────────────────────────────────────────────────────────
echo "[7/7] Fixing storage permissions..."
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

echo "──────────────────────────────────────────"
echo "  Startup complete. Starting services..."
echo "──────────────────────────────────────────"

exec "$@"
