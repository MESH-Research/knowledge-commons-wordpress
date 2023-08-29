#!/bin/sh
echo "Running WordPress build script..."

echo "Installing Xdebug..."
apk add --no-cache $PHPIZE_DEPS
pecl install xdebug-3.1.6
docker-php-ext-enable xdebug
touch /tmp/xdebug.log
chown www-data:www-data /tmp/xdebug.log

echo "Resetting plugins & themes directories..."
rm -rf /app/site/web/app/plugins/*
rm -rf /app/site/web/app/themes/*
rm -rf /app/site/web/app/mu-plugins/*

echo "Installing Composer dependencies..."
cd /app
rm -rf vendor/
composer update

echo "Relinking Commons plugins & themes..."
ln -s /app/ancillary-plugins/*/ /app/site/web/app/plugins/
ln -s /app/core-plugins/*/ /app/site/web/app/plugins/
ln -s /app/forked-plugins/*/ /app/site/web/app/plugins/
ln -s /app/mu-plugins/* /app/site/web/app/mu-plugins/
ln -s /app/themes/*/ /app/site/web/app/themes/

echo "Finished running WordPress build script."