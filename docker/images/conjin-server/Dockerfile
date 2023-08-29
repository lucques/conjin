FROM php:8-apache

RUN apt-get update && apt-get install -y libgmp-dev
RUN docker-php-ext-install mysqli gmp
RUN a2enmod rewrite

COPY ./custom.ini /usr/local/etc/php/conf.d/custom.ini
COPY ./apache2.conf /etc/apache2/apache2.conf
COPY ./sites-available /etc/apache2/sites-available/000-default.conf

VOLUME [ "/files" ]

WORKDIR /files