FROM php:8.2-apache

# Install PDO, MySQLi and GD extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install mysqli pdo pdo_mysql gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite
