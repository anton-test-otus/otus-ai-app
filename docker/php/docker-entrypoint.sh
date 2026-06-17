#!/bin/sh

run_bootstrap() {
    if [ -z "${DATABASE_URL:-}" ]; then
        echo "DATABASE_URL is not set, skipping database bootstrap."
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
    fi

    echo "Warming up cache..."
    php bin/console cache:warmup --no-debug
}

if [ "${1:-}" = "php-fpm" ]; then
    run_bootstrap
fi

exec docker-php-entrypoint "$@"
