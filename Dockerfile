FROM php:7.2-cli
COPY . /usr/src/conveyor
WORKDIR /usr/src/conveyor
CMD [ "php", "./server.php" ]
