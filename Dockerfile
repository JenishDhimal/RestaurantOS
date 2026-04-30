FROM php:8.2-apache

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public


RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN a2enmod rewrite \
 && echo "ServerName localhost" >> /etc/apache2/apache2.conf \
 && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
 && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf


RUN printf "session.cookie_httponly = On\nsession.cookie_samesite = Strict\nexpose_php = Off\n" \
    > /usr/local/etc/php/conf.d/restaurantos-security.ini


COPY src/ /var/www/html/


RUN chown -R www-data:www-data /var/www/html
