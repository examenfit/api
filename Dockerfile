# 8.5.12 is the Forge php of staging/production sites that run 8.5 (composer.json allows ~8.3.36 || ~8.5.12).
FROM php:8.5.12-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip poppler-utils imagemagick nodejs npm \
      libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev libonig-dev libxml2-dev libxslt1-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl bcmath exif mbstring pcntl xsl \
    && npm install -g katex \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
EXPOSE 8000
# not `artisan serve`: it drops env vars that .env also defines, so the DB_HOST override from compose is lost
CMD ["sh", "-c", "composer install --no-interaction && php -S 0.0.0.0:8000 -t public server.php"]
