FROM php:7.2-cli
COPY . /usr/src/conveyor
WORKDIR /usr/src/conveyor
RUN composer install
CMD [ "php", "./server.php" ]
