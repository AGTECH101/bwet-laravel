#!/bin/sh
set -eu

PORT="${PORT:-8080}"
APP_ROOT="/var/www"
APP_PUBLIC="${APP_ROOT}/public"
APP_STORAGE="${APP_ROOT}/storage"
APP_CACHE="${APP_ROOT}/bootstrap/cache"
APP_DB_DIR="${APP_ROOT}/database"
TEMPLATE_FILE="${APP_ROOT}/docker/nginx/default.conf.template"

mkdir -p "$APP_STORAGE" "$APP_CACHE" "$APP_DB_DIR"
touch "$APP_DB_DIR/database.sqlite"
chown -R www-data:www-data "$APP_STORAGE" "$APP_CACHE" "$APP_DB_DIR" "$APP_PUBLIC"

if [ ! -f "$APP_ROOT/.env" ] && [ -f "$APP_ROOT/.env.example" ]; then
    cp "$APP_ROOT/.env.example" "$APP_ROOT/.env"
fi

if [ -f "$APP_ROOT/artisan" ]; then
    php artisan config:clear >/dev/null 2>&1 || true
    php artisan cache:clear >/dev/null 2>&1 || true
    php artisan route:clear >/dev/null 2>&1 || true
    php artisan view:clear >/dev/null 2>&1 || true
    php artisan optimize:clear >/dev/null 2>&1 || true

    php artisan key:generate --force >/dev/null 2>&1 || true
    php artisan migrate --force >/dev/null 2>&1 || true
fi

mkdir -p /run/nginx /etc/nginx/http.d

sed -e "s|\${PORT}|${PORT}|g" "$TEMPLATE_FILE" > /etc/nginx/http.d/default.conf

php-fpm -D
exec nginx -g 'daemon off;'
