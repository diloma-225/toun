FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod headers

COPY docker/php.ini /usr/local/etc/php/conf.d/toun.ini
COPY . /var/www/html/

# Configuration lue depuis les variables d'environnement (voir .env.example)
RUN mv docker/serveur.php config/serveur.php \
    && mv docker/paiement_cles.php config/paiement_cles.php \
    && rm -rf docker \
    && mkdir -p uploads \
    && chown -R www-data:www-data uploads
