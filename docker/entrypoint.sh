#!/bin/sh
set -eu

PORT="${PORT:-8080}"
DB_DATABASE="${DB_DATABASE:-/data/database.sqlite}"
DATA_DIR="$(dirname "$DB_DATABASE")"

mkdir -p "$DATA_DIR" \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

touch "$DB_DATABASE"
chown -R www-data:www-data "$DATA_DIR" storage bootstrap/cache

# Point Apache at the port Fly (or Compose) expects.
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \\*:.*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export DB_DATABASE

php artisan migrate --force

exec apache2-foreground
