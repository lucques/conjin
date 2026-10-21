ARG BASE_IMAGE=php:8.3.32-apache-bookworm
FROM ${BASE_IMAGE} AS php-runtime

RUN apt-get update && \
    apt-get install -y libgmp-dev libcurl4-openssl-dev
RUN docker-php-ext-install mysqli gmp curl

# Test-only tools share the deployment interpreter and common extensions.
# Selecting this target does not configure or start Apache.
FROM php-runtime AS php-test
RUN apt-get update && \
    apt-get install -y --no-install-recommends libonig-dev libsqlite3-dev libxml2-dev unzip && \
    docker-php-ext-install mbstring pdo_sqlite dom xml xmlwriter && \
    rm -rf /var/lib/apt/lists/*
COPY --from=composer:2.10.3 /usr/bin/composer /usr/local/bin/composer
ENTRYPOINT [ "php" ]
CMD [ "--version" ]

# The default target remains the deployment server, without test tooling.
FROM php-runtime AS server
RUN a2enmod rewrite

COPY ./custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY ./apache2.conf /etc/apache2/apache2.conf
COPY ./sites-available /etc/apache2/sites-available/000-default.conf

VOLUME [ "/files" ]

WORKDIR /files
