#!/bin/sh
echo "Running WordPress build script..."

echo "Installing Xdebug..."
apk add --no-cache $PHPIZE_DEPS
pecl install xdebug
docker-php-ext-enable xdebug

echo "Resetting plugins & themes directories..."


echo "Installing Composer dependencies..."
cd /app
composer update

echo "Finished running WordPress build script."