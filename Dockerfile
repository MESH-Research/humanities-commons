FROM php:8.1-fpm-alpine

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
	install-php-extensions exif imagick zip memcached redis mysqli

ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/
RUN chmod a+x /usr/local/bin/wp-cli.phar && \
	mv /usr/local/bin/wp-cli.phar /usr/local/bin/wp

RUN apk add --no-cache $PHPIZE_DEPS \
	&& pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug \
	&& touch /tmp/xdebug.log \
	&& chmod 777 /tmp/xdebug.log

RUN apk add mysql mysql-client

EXPOSE 9000