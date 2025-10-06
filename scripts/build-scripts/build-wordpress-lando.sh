#!/bin/sh
echo "Running WordPress build script..."

echo "Installing Xdebug..."
apk add --no-cache $PHPIZE_DEPS
pecl install xdebug
docker-php-ext-enable xdebug

echo "Relinking plugins and themes..."
find /app/site/web/app/plugins/ -type l -delete
find /app/site/web/app/mu-plugins/ -type l -delete 
find /app/site/web/app/themes/ -type l -delete
ln -sf /app/plugins/*/ /app/site/web/app/plugins/
ln -sf /app/mu-plugins/* /app/site/web/app/mu-plugins/
ln -sf /app/themes/*/ /app/site/web/app/themes/

echo "Copying plugins and themes from EFS..."
echo "Copying plugins..."
#rsync -a --info=progress2 /content/plugins/* /app/site/web/app/plugins/

echo "Copying themes..."
#rsync -a --info=progress2 /content/themes/* /app/site/web/app/themes/

echo "Installing Composer dependencies..."
cd /app
composer update

echo "Finished running WordPress build script."