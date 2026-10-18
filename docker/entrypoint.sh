#!/bin/sh
# Entry point image SDM: tunggu DB, migrasi (hanya bila RUN_MIGRATIONS=true), lalu cache optimasi.
set -e

cd /var/www/html

# MariaDB native di host (DB_HOST=host.docker.internal). Driver Laravel "mariadb" dan "mysql" memakai DSN PDO yang sama.
case "${DB_CONNECTION:-mariadb}" in
mariadb | mysql)
    echo "Menunggu database ${DB_HOST:-host.docker.internal}:${DB_PORT:-3306} ..."
    tries=0
    until php -r '
        try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); }
        catch (Throwable $e) { exit(1); }
    '; do
        tries=$((tries + 1))
        if [ "$tries" -ge 60 ]; then echo "Database tidak tersedia setelah 120 detik." >&2; exit 1; fi
        sleep 2
    done
    ;;
esac

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

php artisan optimize
php artisan filament:optimize
php artisan icons:cache
php artisan event:cache

exec "$@"
