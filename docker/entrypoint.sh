#!/bin/sh
set -e

# Volume с хоста часто монтируется от root — php-fpm (www-data) не может писать логи/кэш.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

exec docker-php-entrypoint "$@"
