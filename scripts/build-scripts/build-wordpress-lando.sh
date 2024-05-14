#!/bin/sh
echo "Running WordPress build script..."

echo "Installing Xdebug..."
apk add --no-cache $PHPIZE_DEPS
pecl install xdebug
docker-php-ext-enable xdebug

echo "Relinking Commons plugins & themes..."
ln -sf /app/ancillary-plugins/*/ /app/site/web/app/plugins/
ln -sf /app/core-plugins/*/ /app/site/web/app/plugins/
ln -sf /app/forked-plugins/*/ /app/site/web/app/plugins/
ln -sf /app/mu-plugins/* /app/site/web/app/mu-plugins/
ln -sf /app/themes/*/ /app/site/web/app/themes/

echo "Installing Composer dependencies..."
cd /app
composer update

echo "Finished running WordPress build script."