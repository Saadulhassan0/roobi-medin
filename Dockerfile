# Use official PHP image with Apache
FROM php:8.2-apache

# Install required PHP extensions for MySQL connection and CA certificates
RUN apt-get update && apt-get install -y ca-certificates && \
    docker-php-ext-install pdo pdo_mysql mysqli

# Install Composer (just in case it's needed)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy your code into the container
COPY . /var/www/html/

# Install composer dependencies
RUN if [ -f "composer.json" ]; then composer install --no-dev --optimize-autoloader; fi

# Fix permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
