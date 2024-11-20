#!/bin/sh
echo "Running WordPress build script for cloud deployment..."

echo "Installing Composer dependencies..."
cd /app
composer install

echo "Installing Composer dependencies for humcore..."
cd /app/plugins/humcore/
composer install

echo "Installing php.ini..."
rm -rf /usr/local/etc/php/php.ini
ln -s /app/config/all/php/php.ini /usr/local/etc/php/php.ini

echo "Setting up SimpleSAMLphp tmp and log directories..."
rm -rf /app/config/all/simplesamlphp/log
rm -rf /app/config/all/simplesamlphp/tmp
mkdir -p /app/config/all/simplesamlphp/log
mkdir -p /app/config/all/simplesamlphp/tmp
chown -R www-data:www-data /app/config/all/simplesamlphp/log
chown -R www-data:www-data /app/config/all/simplesamlphp/tmp

echo "Copying redis drop-in..."
cp /app/site/web/app/plugins/redis-cache/includes/object-cache.php /app/site/web/app/object-cache.php

echo "Finished running WordPress build script."