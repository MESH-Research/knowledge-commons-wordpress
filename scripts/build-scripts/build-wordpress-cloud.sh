#!/bin/sh
echo "Running WordPress build script for cloud deployment..."

echo "Installing Composer dependencies..."
cd /app
composer install

echo "Installing php.ini..."
rm -rf /usr/local/etc/php/php.ini
ln -s /app/config/all/php/php.ini /usr/local/etc/php/php.ini

echo "Copying redis drop-in..."
cp /app/site/web/app/plugins/redis-cache/includes/object-cache.php /app/site/web/app/object-cache.php

echo "Finished running WordPress build script."