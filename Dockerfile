FROM php:7.2-cli
COPY . /usr/src/conveyor
WORKDIR /usr/src/conveyor
RUN curl -sS https://getcomposer.org/installer | \
	php -- --install-dir=/usr/bin/ --filename=composer
RUN apt-get update && apt-get install -y \
	libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev unzip git
RUN docker-php-ext-install gd && \
	docker-php-ext-install bcmath && \
	docker-php-ext-install exif
RUN composer install
CMD [ "php", "./server.php" ]
