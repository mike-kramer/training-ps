# syntax=docker/dockerfile:1

############################################################
# Stage 1 — frontend (Vite)
############################################################
FROM node:22-bookworm-slim AS frontend

WORKDIR /app

# BuildKit sets TARGETARCH (amd64, arm64, …).
ARG TARGETARCH

COPY application/package.json application/package-lock.json application/.npmrc ./

# npm ci often skips platform optional deps for rolldown (npm/cli#4828).
# Install the matching native binding explicitly after ci.
RUN npm ci \
    && case "$TARGETARCH" in \
         amd64) npm install --no-save @rolldown/binding-linux-x64-gnu ;; \
         arm64) npm install --no-save @rolldown/binding-linux-arm64-gnu ;; \
         *) echo "Unsupported TARGETARCH=$TARGETARCH" >&2; exit 1 ;; \
       esac

COPY application/ ./
RUN rm -f public/hot \
    && npm run build

############################################################
# Stage 2 — PHP vendor (Composer --no-dev)
############################################################
FROM debian:trixie-slim AS vendor

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        lsb-release \
        ca-certificates \
        curl \
        apt-transport-https \
        gnupg \
        unzip \
        git \
    && curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb \
    && dpkg -i /tmp/debsuryorg-archive-keyring.deb \
    && echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
        > /etc/apt/sources.list.d/php.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.5-cli \
        php8.5-common \
        php8.5-pgsql \
        php8.5-mbstring \
        php8.5-xml \
        php8.5-curl \
        php8.5-zip \
        php8.5-intl \
        php8.5-redis \
        php8.5-sqlite3 \
    && curl -sS https://getcomposer.org/installer \
        | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/*

WORKDIR /app

COPY application/composer.json application/composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY application/ ./
COPY --from=frontend /app/public/build ./public/build

RUN rm -f public/hot \
    && composer install \
        --no-dev \
        --optimize-autoloader \
        --no-scripts \
        --prefer-dist \
        --no-interaction \
    && composer dump-autoload --optimize --classmap-authoritative \
    # --no-scripts skips package:discover; bootstrap/cache is empty in git — register packages explicitly.
    && php artisan package:discover --ansi \
    && rm -rf /root/.composer /tmp/*

############################################################
# Stage 3 — PHP-FPM runtime (no Xdebug, no Node)
############################################################
FROM debian:trixie-slim AS runtime

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        lsb-release \
        ca-certificates \
        curl \
        apt-transport-https \
        gnupg \
        rsync \
    && curl -sSLo /tmp/debsuryorg-archive-keyring.deb https://packages.sury.org/debsuryorg-archive-keyring.deb \
    && dpkg -i /tmp/debsuryorg-archive-keyring.deb \
    && echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" \
        > /etc/apt/sources.list.d/php.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        php8.5-fpm \
        php8.5-cli \
        php8.5-common \
        php8.5-pgsql \
        php8.5-mbstring \
        php8.5-xml \
        php8.5-curl \
        php8.5-zip \
        php8.5-intl \
        php8.5-redis \
        php8.5-sqlite3 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/*

# Built application lives here; entrypoint syncs into the shared volume.
COPY --from=vendor /app /opt/app-src

RUN mkdir -p \
        /opt/app-src/storage/app/public \
        /opt/app-src/storage/framework/cache/data \
        /opt/app-src/storage/framework/sessions \
        /opt/app-src/storage/framework/testing \
        /opt/app-src/storage/framework/views \
        /opt/app-src/storage/logs \
        /opt/app-src/bootstrap/cache \
    && chown -R www-data:www-data \
        /opt/app-src/storage \
        /opt/app-src/bootstrap/cache

COPY server-docker/php/php.ini /etc/php/8.5/fpm/php.ini
COPY server-docker/php/php-cli.ini /etc/php/8.5/cli/php.ini
COPY server-docker/php/pool.d/zz-docker.conf /etc/php/8.5/fpm/pool.d/zz-docker.conf
COPY server-docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["php-fpm8.5", "-F"]
