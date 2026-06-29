# 1. Menggunakan stage composer untuk mengambil binary composer terbaru
FROM composer:latest AS composer-builder

# 2. Menggunakan PHP 8.3 Alpine sebagai base image yang ringan
FROM php:8.3-fpm-alpine

# Install system dependencies yang dibutuhkan oleh Laravel & ekstensi PHP
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    freetype-dev \
    libjpeg-turbo-dev \
    libzip-dev

# Mengonfigurasi dan menginstal ekstensi PHP (PDO, BCMath, GD, Zip)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql bcmath gd zip

# Copy composer dari stage builder ke image utama
COPY --from=composer-builder /usr/bin/composer /usr/bin/composer

# Menentukan direktori kerja di dalam kontainer
WORKDIR /var/www/html

# Menyalin seluruh source code aplikasi ke dalam kontainer
COPY . .

# Menjalankan composer install untuk mengunduh semua dependencies vendor
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Mengatur hak akses kepemilikan folder ke user bawaan php-fpm (www-data)
RUN chown -R www-data:www-data /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
