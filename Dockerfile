FROM php:7.4-cli
RUN apt-get update

# Install PDO and PGSQL Drivers
RUN apt-get install -y libpq-dev zip unzip  \
  && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
  && docker-php-ext-install pdo pdo_pgsql pgsql 
COPY ./app/rmk_notification.php /usr/src/myapp/
COPY ./.env	/usr/src/myapp/
CMD cd /usr/src/myapp/
#RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && chmod +x /usr/local/bin/composer
#RUN composer require vlucas/phpdotenv --prefer-dist
# RUN composer install

WORKDIR /usr/src/myapp
CMD [ "php", "/usr/src/myapp/rmk_notification.php" ]
