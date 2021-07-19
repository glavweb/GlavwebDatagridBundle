FROM php:7.2.5-cli

ARG BUNDLE_VERSION

RUN apt-get update && \
    apt-get install -y \
      unzip \
      libzip-dev \
      libpq-dev \
    && docker-php-ext-install zip pdo_pgsql 

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /usr/src/

RUN composer create-project symfony/skeleton:"^5.3" app

WORKDIR /usr/src/app

RUN composer require --dev phpunit/phpunit symfony/test-pack

COPY environment .

COPY scripts/ ../scripts/
RUN chmod +x ../scripts/*

RUN composer config repositories.glavweb "{\"type\": \"path\", \"url\": \"../bundle\", \"options\": {\"versions\": { \"glavweb/datagrid-bundle\": \"$BUNDLE_VERSION\" }}}"