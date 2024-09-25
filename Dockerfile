# Étape de construction : utiliser une image PHP avec extensions requises pour Laravel
FROM php:8.3-fpm AS build

# Installer les dépendances nécessaires pour PHP et les extensions
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    libcurl4-openssl-dev \
    libssl-dev \
    pkg-config \
    libmagickwand-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb

# Installer les extensions PHP requises
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath

# Installer Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Définir le répertoire de travail
WORKDIR /var/www

# Copier tous les fichiers de l'application
COPY . .

# Installer les dépendances de Composer
RUN composer install --no-dev --optimize-autoloader

# Étape finale : utiliser une image PHP plus légère
FROM php:8.3-fpm

# Copier les fichiers de l'étape de construction
COPY --from=build /var/www /var/www

# Exposer le port 9000 pour PHP-FPM
EXPOSE 9000

# Changer les permissions du dossier de stockage et de cache
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Lancer PHP-FPM au démarrage du container
CMD ["php-fpm"]
