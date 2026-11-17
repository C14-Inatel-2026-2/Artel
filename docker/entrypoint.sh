#!/bin/sh
set -eu

PORT="${PORT:-8080}"
DB_DATABASE="${DB_DATABASE:-/data/database.sqlite}"
DATA_DIR="$(dirname "$DB_DATABASE")"

DB_CONNECTION="${DB_CONNECTION:-sqlite}"

mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# O arquivo SQLite só é necessário quando o banco é SQLite (Compose); no Render usa Postgres.
if [ "$DB_CONNECTION" = "sqlite" ]; then
    mkdir -p "$DATA_DIR"
    touch "$DB_DATABASE"
    chown -R www-data:www-data "$DATA_DIR"
fi

chown -R www-data:www-data storage bootstrap/cache

# Point Apache at the port Render (or Compose) expects.
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \\*:.*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

export DB_CONNECTION
export DB_DATABASE

php artisan migrate --force

exec apache2-foreground
