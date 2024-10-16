#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
        set -- php-fpm "$@"
fi

# Retrieve AWS secrets and export them as environment variables
if [ -f /app/scripts/build-scripts/get-aws-secrets.sh ]; then
    echo "Retrieving AWS secrets..."
    source /app/scripts/build-scripts/get-aws-secrets.sh
    if [ $? -eq 0 ]; then
        echo "AWS secrets retrieved and exported successfully."
    else
        echo "Failed to retrieve AWS secrets. Continuing without them."
    fi
else
    echo "AWS secrets retrieval script not found. Skipping."
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