#!/bin/bash

# Démarrez Nginx
nginx

# Exécutez les migrations de la base de données si nécessaire
php artisan migrate --force

# Démarrez PHP-FPM
php-fpm