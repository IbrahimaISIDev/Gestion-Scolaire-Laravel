#!/bin/sh
set -e

# Start PHP-FPM
php-fpm -D

# Start Nginx
nginx -g "daemon off;"