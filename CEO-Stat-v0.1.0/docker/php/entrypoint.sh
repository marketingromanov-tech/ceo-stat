#!/usr/bin/env sh
set -eu

if [ ! -f .env ]; then
    cp .env.docker .env
fi

mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
# Bind mounts from Docker Desktop do not preserve the image user/group.
# These directories contain local runtime data only and must be writable by PHP-FPM.
chmod -R a+rwX storage bootstrap/cache

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

if ! grep -Eq '^APP_KEY=base64:.+' .env; then
    php artisan key:generate --force
fi

php artisan migrate --seed --force

exec "$@"
