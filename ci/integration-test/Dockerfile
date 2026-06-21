FROM php:8.4-cli

RUN apt-get update -y && \
    apt-get install -y \
      git \
      unzip \
      libzip-dev \
      libpq-dev \
    && docker-php-ext-install zip pdo_pgsql \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN cat > /usr/local/etc/php/conf.d/xdebug.ini <<EOF
xdebug.mode=debug
xdebug.client_host="host.docker.internal"
EOF

ENV PHP_IDE_CONFIG="serverName=glavweb-datagrid-bundle"

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /usr/src/

RUN git config --global init.defaultBranch master \
    && git config --global --add safe.directory '*' \
    && composer create-project symfony/skeleton:"^8.0" app \
    && cd app \
    && composer require -n --dev phpunit/phpunit symfony/test-pack orm-fixtures \
    && composer config repositories.0 "{\"type\": \"path\", \"url\": \"../bundle\", \"options\": {\"symlink\": true}}" \
    && composer config "minimum-stability" dev

COPY scripts/ scripts/
RUN chmod +x scripts/*

WORKDIR /usr/src/app

COPY environment .