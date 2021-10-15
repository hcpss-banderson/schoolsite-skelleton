FROM banderson/drupal:base-fpm

RUN install-php-extensions exif

COPY ./docker/drupal/php.ini-production /usr/local/etc/php/php.ini
