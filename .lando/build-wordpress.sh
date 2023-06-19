#!/bin/sh
echo "Running WordPress build script..."

echo "Installing Xdebug..."
apk add --no-cache $PHPIZE_DEPS
pecl install xdebug-3.1.6
docker-php-ext-enable xdebug
touch /tmp/xdebug.log
chown www-data:www-data /tmp/xdebug.log

echo "Installing Composer dependencies..."
cd /app
composer update

echo "Installing Composer dependencies for SimpleSAMLphp..."
cd /app/simplesamlphp
composer update

echo "Finished running WordPress build script."
