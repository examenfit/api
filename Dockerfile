# 8.5.12 is the Forge php of staging/production sites that run 8.5 (composer.json allows ~8.3.36 || ~8.5.12).
FROM php:8.5.12-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
      git unzip poppler-utils imagemagick \
      libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libicu-dev libonig-dev libxml2-dev libxslt1-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl bcmath exif mbstring pcntl xsl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# node 22 for the katex CLI (the 0.19 CLI needs Node >= 22.12; Debian's nodejs is 18); copy only node and npm, /usr/local also holds php
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s ../lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s ../lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx

WORKDIR /var/www/html
EXPOSE 8000
# not `artisan serve`: it drops env vars that .env also defines, so the DB_HOST override from compose is lost
CMD ["sh", "-c", "mkdir -p storage/app/public/collections storage/app/public/question-correction && composer install --no-interaction && npm ci --omit=dev --no-audit --no-fund && php -S 0.0.0.0:8000 -t public server.php"]
