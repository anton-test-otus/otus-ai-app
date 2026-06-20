#!/bin/sh

ensure_jwt_keys() {
    if [ -f config/jwt/private.pem ] && [ -f config/jwt/public.pem ]; then
        return 0
    fi

    mkdir -p config/jwt

    if [ -f /opt/app-jwt/private.pem ] && [ -f /opt/app-jwt/public.pem ]; then
        echo "Installing JWT keys from Docker image..."
        cp /opt/app-jwt/private.pem /opt/app-jwt/public.pem config/jwt/
        chmod 644 config/jwt/private.pem config/jwt/public.pem
        chown -R www-data:www-data config/jwt 2>/dev/null || true
        return 0
    fi

    echo "Warning: /opt/app-jwt missing, generating JWT keys..."
    openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:4096
    openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem
    chmod 644 config/jwt/private.pem config/jwt/public.pem
    chown -R www-data:www-data config/jwt 2>/dev/null || true
}

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
    ensure_jwt_keys
    run_bootstrap
fi

exec docker-php-entrypoint "$@"
