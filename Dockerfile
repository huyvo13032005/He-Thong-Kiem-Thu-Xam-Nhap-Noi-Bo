FROM php:8.3-apache-bookworm

ARG PDO_SQLSRV_VERSION=5.13.3
RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl libgssapi-krb5-2 unixodbc-dev $PHPIZE_DEPS \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/packages-microsoft-prod.deb -o /tmp/microsoft-prod.deb \
    && dpkg -i /tmp/microsoft-prod.deb \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 \
    && pecl install pdo_sqlsrv-${PDO_SQLSRV_VERSION} \
    && docker-php-ext-enable pdo_sqlsrv \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/* /tmp/microsoft-prod.deb /tmp/pear

WORKDIR /var/www/html
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/pentest.ini
COPY . .
RUN mkdir -p storage/evidence && chown -R www-data:www-data storage
EXPOSE 80
CMD ["apache2-foreground"]
