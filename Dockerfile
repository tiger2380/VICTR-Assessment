FROM docker.io/php:8.3-apache

# install Composer
COPY docker/install-composer.sh /tmp
RUN /tmp/install-composer.sh

# enable Apache rewrite module
RUN a2enmod rewrite
