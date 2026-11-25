# Image de développement local : PHP 8.4 et Apache, comme l'hébergement cPanel.
# Le code n'est pas copié : compose.yaml monte le projet dans /var/www/html.
FROM php:8.4-apache

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# gd : traitement des images (intervention/image) ; pcntl : délais du worker.
RUN install-php-extensions exif gd intl opcache pcntl pdo_mysql zip \
    && apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite \
    && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername

COPY php.ini /usr/local/etc/php/conf.d/zz-foursquare.ini
COPY vhost.conf /etc/apache2/sites-available/000-default.conf
COPY entrypoint.sh /usr/local/bin/foursquare-entrypoint
RUN sed -i 's/\r$//' /usr/local/bin/foursquare-entrypoint \
    && chmod +x /usr/local/bin/foursquare-entrypoint

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html
ENTRYPOINT ["foursquare-entrypoint"]
CMD ["apache2-foreground"]
