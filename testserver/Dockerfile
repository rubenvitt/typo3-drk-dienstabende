# Local TYPO3 13 with SQLite, only for trying out drk_dienstabende. Not for production.
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev unzip git sqlite3 \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install intl zip gd opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

ENV APACHE_DOCUMENT_ROOT=/var/www/project/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && printf 'max_execution_time=240\nmax_input_vars=1500\nmemory_limit=512M\n' > /usr/local/etc/php/conf.d/typo3.ini

WORKDIR /var/www/project
COPY . /ext/drk_dienstabende
COPY testserver/composer.json ./
RUN composer install --no-interaction --no-progress

COPY testserver/setup.sh /usr/local/bin/setup.sh
CMD ["setup.sh"]
