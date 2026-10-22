#!/bin/bash
# Se ejecuta cada vez que arranca el contenedor "app".
set -e
cd /var/www/html

# Laravel necesita un .env (puede ir vacio: la configuracion llega por variables de entorno)
[ -f .env ] || touch .env

# APP_KEY: si no se indica, se genera una sola vez y se guarda en el volumen de storage,
# asi sobrevive aunque se recree el contenedor (las sesiones no se invalidan).
if [ -z "${APP_KEY:-}" ]; then
    KEY_FILE=storage/app/.app_key
    if [ ! -s "$KEY_FILE" ]; then
        php artisan key:generate --show --no-interaction > "$KEY_FILE"
    fi
    APP_KEY="$(tr -d '\r\n' < "$KEY_FILE")"
    export APP_KEY
fi

mkdir -p storage/app/private/books storage/framework/cache/data storage/framework/sessions \
         storage/framework/views storage/logs bootstrap/cache public/uploads/covers

# Espera a la base de datos, la crea si falta, migra y (solo la primera vez) carga los datos de demostracion
SEED_FLAG=""
if [ "${WONDERLAND_SEED:-true}" = "false" ]; then
    SEED_FLAG="--no-seed"
fi
php artisan wonderland:install --no-interaction --wait=120 ${SEED_FLAG}

chown -R www-data:www-data storage bootstrap/cache public/uploads 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache public/uploads 2>/dev/null || true

echo ">> Library of Wonderland lista en http://localhost:${APP_PORT:-8080}"
exec docker-php-entrypoint "$@"
