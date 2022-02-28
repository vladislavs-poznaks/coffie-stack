FROM php:8.1-fpm-alpine

ENV PHPUSER=stack
ENV PHPGROUP=stack

RUN adduser -g ${PHPGROUP} -s /bin/sh -D ${PHPUSER}

RUN sed -i "s/user = www-data/user = ${PHPUSER}/g" /usr/local/etc/php-fpm.d/www.conf
RUN sed -i "s/group = www-data/group = ${PHPGROUP}/g" /usr/local/etc/php-fpm.d/www.conf

RUN mkdir -p /var/www/html

RUN docker-php-ext-install pdo pdo_mysql

CMD ["php-fpm", "-y", "/usr/local/etc/php-fpm.conf", "-R"]
