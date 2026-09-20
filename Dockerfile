FROM php:apache

# Install pdo_mysql
RUN apt-get update
RUN docker-php-ext-install pdo_mysql
RUN apt-get clean
RUN rm -rf /var/lib/apt/lists/*
