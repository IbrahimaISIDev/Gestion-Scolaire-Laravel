FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Copy the entrypoint script and set permissions
COPY entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

# Create necessary directories and set permissions
RUN mkdir -p /var/lib/nginx /var/lib/nginx/body /var/log/nginx /var/cache/nginx /run/nginx \
    && chown -R www-data:www-data /var/lib/nginx /var/log/nginx /var/cache/nginx /run/nginx \
    && chown -R www-data:www-data /var/www

# Expose port 80
EXPOSE 80

# Set the entrypoint
ENTRYPOINT ["entrypoint.sh"]
