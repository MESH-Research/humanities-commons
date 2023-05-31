FROM php:7.4.33-fpm-alpine3.16

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
	install-php-extensions exif imagick zip memcached redis mysqli

ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/
RUN chmod a+x /usr/local/bin/wp-cli.phar && \
	mv /usr/local/bin/wp-cli.phar /usr/local/bin/wp

RUN apk add --no-cache $PHPIZE_DEPS \
	&& pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug

EXPOSE 9000