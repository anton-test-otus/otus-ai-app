#!/bin/sh

run_bootstrap() {
    if [ -z "${DB_HOST:-}" ] || [ -z "${DB_NAME:-}" ]; then
        echo "DB_HOST/DB_NAME is not set, skipping database bootstrap."
        return 0
    fi

    echo "Waiting for database..."
    until php bin/console doctrine:query:sql "SELECT 1" >/dev/null 2>&1; do
        sleep 1
    done

    echo "Running migrations..."
    if ! php bin/console doctrine:migrations:migrate --no-interaction --no-debug --allow-no-migration; then
        echo "Warning: migrations reported errors (schema may already exist)."
    fi

    if [ "$APP_AUTH_ENABLED" = "false" ] || [ "$APP_AUTH_ENABLED" = "0" ]; then
        echo "Ensuring single-user account..."
        php bin/console app:ensure-single-user --no-interaction
    else
        echo "Seeding demo data (if missing)..."
        php bin/console app:seed-demo-data --no-interaction --if-missing
    fi

    echo "Warming up cache..."
    php bin/console cache:warmup --no-debug
}

if [ "${1:-}" = "php-fpm" ]; then
    run_bootstrap
fi

exec docker-php-entrypoint "$@"
