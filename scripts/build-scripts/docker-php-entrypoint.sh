#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
        set -- php-fpm "$@"
fi

rm -f /app/config/all/simplesamlphp/cert/saml.pem
rm -f /app/config/all/simplesamlphp/cert/saml.crt
echo "$SAML_PEM" > /app/config/all/simplesamlphp/cert/saml.pem
echo "$SAML_CRT" > /app/config/all/simplesamlphp/cert/saml.crt

exec "$@"