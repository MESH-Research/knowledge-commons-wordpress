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

# Link EFS themes and plugins
if [ -f /app/scripts/build-scripts/link-efs-themes-plugins.sh ]; then
    echo "Linking EFS themes and plugins..."
    source /app/scripts/build-scripts/link-efs-themes-plugins.sh
fi

if [ "$WP_ENV" = "production" ]; then
        cp /app/site/web/app/plugins/redis-cache/includes/object-cache.php /app/site/web/app/object-cache.php
fi

exec "$@"