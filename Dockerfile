# syntax=docker/dockerfile:1

# ─────────────────────────────────────────────────────────────────────────────
# Datacenter-sim — single image used by every service (web, reverb, queue,
# scheduler). PHP 8.3 + the extensions the app needs, with the front-end
# assets (Vite/Echo) built in.
# ─────────────────────────────────────────────────────────────────────────────
FROM php:8.3-cli-bookworm

# System libraries + PHP extensions (pcntl for reverb/queue; pdo_sqlite for
# local use, pdo_mysql/pdo_pgsql for a managed cloud database;
# gd/zip/mbstring/bcmath for the app & dompdf).
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip ca-certificates \
        libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
        libonig-dev libsqlite3-dev sqlite3 libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_sqlite pdo_mysql pdo_pgsql mbstring zip bcmath gd pcntl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Node 22 (for the Vite build) and Composer, pulled from their official images.
COPY --from=node:22-bookworm /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Dependency layers first for better build caching. Dev dependencies are kept
# on purpose: this demo image seeds its data with model factories (Faker), and
# lets you run the test suite inside the container.
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY package.json package-lock.json ./
RUN npm ci

# Application source.
COPY . .

# Finalize the PHP autoloader (runs package:discover).
RUN composer dump-autoload --optimize

# Build the front-end. Vite bakes the VITE_* values into the bundle, so the
# browser-facing Reverb host/port are fixed here (default: the host-mapped 8080).
ARG VITE_REVERB_APP_KEY=datacenterkey
ARG VITE_REVERB_HOST=localhost
ARG VITE_REVERB_PORT=8080
ARG VITE_REVERB_SCHEME=http
ENV VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY} \
    VITE_REVERB_HOST=${VITE_REVERB_HOST} \
    VITE_REVERB_PORT=${VITE_REVERB_PORT} \
    VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}
RUN npm run build && rm -rf node_modules

# Writable runtime dirs.
RUN chmod -R ug+rwX storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 8000 8080
ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
