#!/bin/sh
set -e

# Ensure Nginx can write to the cache directory
mkdir -p /var/cache/nginx
chown -R www-data:www-data /var/cache/nginx

# Start PHP-FPM
php-fpm -D

# Start Nginx
nginx -g "daemon off;"
