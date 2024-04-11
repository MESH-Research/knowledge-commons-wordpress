#!/bin/sh
echo "Running WordPress build script..."

echo "Installing Xdebug..."
apk add --no-cache $PHPIZE_DEPS
pecl install xdebug
docker-php-ext-enable xdebug

echo "Resetting plugins & themes directories..."
rm -rf /app/site/web/app/plugins/*
rm -rf /app/site/web/app/themes/*
rm -rf /app/site/web/app/mu-plugins/*

echo "Installing Composer dependencies..."
cd /app
composer update

echo "Finished running WordPress build script."