FROM php:8.3-fpm

# Installer les dépendances système
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx

# Nettoyer le cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Installer les extensions PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Obtenir le dernier Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier le contenu du répertoire existant
COPY . /var/www

# Copier le script d'entrypoint et définir les permissions
COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Créer les répertoires nécessaires et définir les permissions
RUN mkdir -p /var/lib/nginx /var/lib/nginx/body /var/log/nginx /var/cache/nginx /run/nginx \
    && chown -R www-data:www-data /var/lib/nginx /var/log/nginx /var/cache/nginx /run/nginx \
    && chown -R www-data:www-data /var/www

# Exposer le port 80
EXPOSE 80

# Définir l'entrypoint
ENTRYPOINT ["entrypoint.sh"]
