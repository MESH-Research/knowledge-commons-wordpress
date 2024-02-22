#!/bin/sh

# This script is used to replace {DOMAIN_NAME} in the nginx configuration files
# with the domain set in the DOMAIN_NAME environment variable.

# Exit if DOMAIN_NAME is not set
if [ -z "$DOMAIN_NAME" ]; then
  echo "DOMAIN_NAME environment variable is not set. Exiting."
  exit 1
fi

# Replace {DOMAIN_NAME} with the value of DOMAIN_NAME in the nginx configuration files
find /etc/nginx -type f -exec sed -i "s/{DOMAIN_NAME}/$DOMAIN_NAME/g" {} +
