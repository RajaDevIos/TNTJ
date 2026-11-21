# TNTJ Thanjai North website: PHP 8.3 + Apache. Works on Railway, Render, Fly.io or any Docker host.
# Configure with environment variables (see config/config.env.php and DEPLOY.md).
FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
 && (a2dismod -f mpm_event mpm_worker || true) && a2enmod mpm_prefork \
 && a2enmod rewrite headers \
 && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
 && printf '<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' > /etc/apache2/conf-available/tntj.conf \
 && a2enconf tntj \
 && printf 'upload_max_filesize=12M\npost_max_size=14M\nexpose_php=Off\ndate.timezone=Asia/Kolkata\n' > /usr/local/etc/php/conf.d/tntj.ini

WORKDIR /var/www/html
COPY . .
RUN rm -f config/config.php \
 && mkdir -p public/uploads storage \
 && chown -R www-data:www-data public/uploads storage

COPY docker/entrypoint.sh /usr/local/bin/tntj-entrypoint
RUN chmod +x /usr/local/bin/tntj-entrypoint
EXPOSE 80
ENTRYPOINT ["tntj-entrypoint"]
CMD ["apache2-foreground"]
