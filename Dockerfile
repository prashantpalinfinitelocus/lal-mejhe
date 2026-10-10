FROM php:8.3-apache
RUN docker-php-ext-install pdo_mysql && a2enmod headers rewrite \
 && printf '<Directory /var/www/html>\n  AllowOverride All\n</Directory>\n' > /etc/apache2/conf-available/lm.conf && a2enconf lm \
 && sed -i 's/^ServerTokens .*/ServerTokens Prod/; s/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf \
 && cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
 && printf 'expose_php=Off\n' > /usr/local/etc/php/conf.d/zz-security.ini
COPY public_html/ /var/www/html/
RUN mkdir -p /var/www/html/uploads /var/www/html/logs && chown -R www-data:www-data /var/www/html
