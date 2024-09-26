# Utilisez l'image officielle PHP 8.3 avec FPM comme base
FROM php:8.3-fpm

# Installez Nginx et d'autres dépendances nécessaires
RUN apt-get update && apt-get install -y \
    nginx \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql zip gd

# Installe Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définit le répertoire de travail
WORKDIR /var/www/html

# Copie les fichiers de l'application
COPY . .

# Installe les dépendances PHP
RUN composer install --no-dev --optimize-autoloader

# Définit les permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Copie la configuration Nginx
COPY nginx.conf /etc/nginx/nginx.conf

# Copie le script de démarrage
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Expose le port 80 pour Nginx
EXPOSE 80

# Définit la commande de démarrage
CMD ["/usr/local/bin/start.sh"]