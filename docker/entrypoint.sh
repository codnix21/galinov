#!/bin/sh
set -e

cd /var/www/html

echo "Waiting for database..."
i=0
while [ "$i" -lt 60 ]; do
    if php artisan db:show >/dev/null 2>&1; then
        echo "Database is ready."
        break
    fi
    i=$((i + 1))
    if [ "$i" -eq 60 ]; then
        echo "Database is not available after 120s."
        exit 1
    fi
    sleep 2
done

php artisan migrate --force

php artisan storage:link --force 2>/dev/null || true

chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [ "${APP_ENV:-local}" = "production" ]; then
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

docker-php-entrypoint php-fpm -D
exec nginx -g 'daemon off;'
