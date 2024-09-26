#!/bin/sh

# Démarre PHP-FPM en arrière-plan
php-fpm &

# Attendre un peu que PHP-FPM soit prêt
sleep 5

# Démarre Nginx
service nginx start

# Garder le script en cours d'exécution
wait