FROM php:8.2-apache

# System-Abhängigkeiten installieren
RUN apt-get update && apt-get install -y \
    unzip \
    libzip-dev \
    libcurl4-openssl-dev \
    nmap \
    curl \
    && docker-php-ext-install zip pdo_mysql curl

# Composer installieren
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Apache Konfiguration (Mod Rewrite aktivieren)
RUN a2enmod rewrite

WORKDIR /var/www/html
