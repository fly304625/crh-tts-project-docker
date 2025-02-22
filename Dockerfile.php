FROM php:8.2-apache

# Встановлення необхідних PHP розширень та залежностей
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl

# Увімкнення модулів Apache
RUN a2enmod rewrite

# Налаштування PHP
RUN echo "display_errors = On" >> /usr/local/etc/php/php.ini
RUN echo "error_reporting = E_ALL" >> /usr/local/etc/php/php.ini

# Встановлення робочої директорії
WORKDIR /var/www/html

# Налаштування Apache
RUN echo 'ServerName localhost' >> /etc/apache2/apache2.conf
