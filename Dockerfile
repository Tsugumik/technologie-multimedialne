FROM php:8.2-apache

# Install PDO and MySQLi extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite
