FROM docker.io/php:8.3-apache

# install Composer
COPY docker/install-composer.sh /tmp
RUN /tmp/install-composer.sh

# install PHP extensions
RUN docker-php-ext-configure pdo_mysql && docker-php-ext-install pdo_mysql
RUN docker-php-ext-configure opcache && docker-php-ext-install opcache

# enable Apache rewrite module
RUN a2enmod rewrite
