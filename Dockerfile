FROM php:7.2-cli
COPY . /usr/src/conveyor
WORKDIR /usr/src/conveyor
RUN curl -sS https://getcomposer.org/installer | \
            php -- --install-dir=/usr/bin/ --filename=composer
RUN composer install
CMD [ "php", "./server.php" ]
