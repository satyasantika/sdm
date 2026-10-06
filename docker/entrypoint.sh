#!/bin/sh
# Entry point image SDM: tunggu DB, migrasi (hanya bila RUN_MIGRATIONS=true), lalu cache optimasi.
set -e

cd /var/www/html

if [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
    echo "Menunggu database ${DB_HOST:-mysql}:${DB_PORT:-3306} ..."
    tries=0
    until php -r '
        try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); }
        catch (Throwable $e) { exit(1); }
    '; do
        tries=$((tries + 1))
        if [ "$tries" -ge 60 ]; then echo "Database tidak tersedia setelah 120 detik." >&2; exit 1; fi
        sleep 2
    done
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

php artisan optimize
php artisan filament:optimize
php artisan icons:cache
php artisan event:cache

exec "$@"
