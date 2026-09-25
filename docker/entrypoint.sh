#!/bin/sh
set -e

PORT="${PORT:-80}"
sed "s/__PORT__/${PORT}/" /etc/nginx/site.conf.template > /etc/nginx/sites-enabled/default

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

php-fpm -D
exec nginx -g "daemon off;"
