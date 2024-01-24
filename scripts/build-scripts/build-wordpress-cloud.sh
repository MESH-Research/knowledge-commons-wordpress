#!/bin/sh
echo "Running WordPress build script for cloud deployment..."

# If ENVIRONMENT is not set, exit and throw an error
if [ -z "$ENVIRONMENT" ]; then
  echo "ENVIRONMENT environment variable is not set. Exiting."
  exit 1
fi

rm -f /app/site/.env
ln -s /app/site/$ENVIRONMENT.env /app/site/.env

echo "Installing Composer dependencies..."
cd /app
composer install

echo "Installing Coposer dependencies for humcore..."
cd /app/core-plugins/humcore/
composer install

if [ "$ENVIRONMENT" != "production" ]; then
  echo "Installing staging php.ini..."
  rm -rf /usr/local/etc/php/php.ini
  ln -s /app/config/staging/php/php.ini /usr/local/etc/php/php.ini
  
  echo "Installing phpinfo..."
  rm -rf /app/site/web/phpinfo.php
  ln -s /app/scripts/build-scripts/phpinfo.php /app/site/web/phpinfo.php
fi

echo "Setting up SimpleSAMLphp tmp and log directories..."
rm -rf /app/config/$ENVIRONMENT/simplesamlphp/log
rm -rf /app/config/$ENVIRONMENT/simplesamlphp/tmp
mkdir -p /app/config/$ENVIRONMENT/simplesamlphp/log
mkdir -p /app/config/$ENVIRONMENT/simplesamlphp/tmp
chown -R www-data:www-data /app/config/$ENVIRONMENT/simplesamlphp/log
chown -R www-data:www-data /app/config/$ENVIRONMENT/simplesamlphp/tmp

echo "Finished running WordPress build script."