#!/bin/sh
set -e

# Assurez-vous que Nginx peut écrire dans le répertoire de cache
mkdir -p /var/cache/nginx
chown -R www-data:www-data /var/cache/nginx

# Démarrer PHP-FPM
php-fpm -D

# Démarrer Nginx
nginx -g "daemon off;"
