#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
        set -- php-fpm "$@"
fi

rm -f /app/config/all/simplesamlphp/cert/saml.pem
rm -f /app/config/all/simplesamlphp/cert/saml.crt
mkdir -p /app/config/all/simplesamlphp/cert
echo "$SAML_PEM" > /app/config/all/simplesamlphp/cert/saml.pem
echo "$SAML_CRT" > /app/config/all/simplesamlphp/cert/saml.crt

if [ "$WP_ENV" = "production" ]; then
        cp /app/site/web/app/plugins/redis-cache/includes/object-cache.php /app/site/web/app/object-cache.php
fi

exec "$@"