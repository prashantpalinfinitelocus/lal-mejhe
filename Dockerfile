FROM alpine:3.20
RUN apk add --no-cache apache2 php83 php83-apache2 php83-pdo_mysql php83-curl php83-session php83-mbstring php83-openssl php83-ctype php83-fileinfo \
 && mkdir -p /run/apache2 /var/www/html \
 && sed -i -e 's#^\#\(LoadModule rewrite_module .*\)#\1#' -e 's#^\#\(LoadModule headers_module .*\)#\1#' \
           -e 's#"/var/www/localhost/htdocs"#"/var/www/html"#g' -e 's#DirectoryIndex index.html#DirectoryIndex index.php index.html#' \
           -e 's#^ErrorLog .*#ErrorLog /dev/stderr#' /etc/apache2/httpd.conf \
 && printf 'ServerTokens Prod\nServerSignature Off\nTraceEnable Off\n' >> /etc/apache2/httpd.conf \
 && sed -i '/<Directory "\/var\/www\/html">/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/httpd.conf \
 && (grep -q "php_module" /etc/apache2/conf.d/*.conf || echo "LoadModule php_module modules/mod_php83.so" >> /etc/apache2/httpd.conf) \
 && printf '<FilesMatch "\\.php$">\n  SetHandler application/x-httpd-php\n</FilesMatch>\n' >> /etc/apache2/httpd.conf \
 && sed -i -e 's/^expose_php = .*/expose_php = Off/' -e 's/^display_errors = .*/display_errors = Off/' /etc/php83/php.ini
COPY public_html/ /var/www/html/
RUN mkdir -p /var/www/html/uploads /var/www/html/logs && chown -R apache:apache /var/www/html
EXPOSE 80
CMD ["httpd","-D","FOREGROUND"]
