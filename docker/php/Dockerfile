FROM php:8.3-fpm

# Install system dependencies and PHP extensions required by Drupal
RUN apt-get update \
  && apt-get install -y --no-install-recommends \
    git curl unzip libpng-dev libjpeg-dev libfreetype6-dev libxml2-dev \
    libzip-dev zlib1g-dev libicu-dev libonig-dev libpq-dev \
    libwebp-dev libavif-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-avif \
  && docker-php-ext-install -j$(nproc) \
    pdo pdo_pgsql pgsql gd xml zip intl opcache bcmath

# Optional: install APCu
RUN pecl install apcu && docker-php-ext-enable apcu || true

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create www-data directory and ensure permissions
RUN usermod -u 1000 www-data || true
COPY php.ini /usr/local/etc/php/conf.d/zz-custom.ini
WORKDIR /var/www/html

EXPOSE 9000

CMD ["php-fpm"]
