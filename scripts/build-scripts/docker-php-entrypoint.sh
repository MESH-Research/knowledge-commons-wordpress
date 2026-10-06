#!/bin/sh
set -e

# first arg is `-f` or `--some-option`
if [ "${1#-}" != "$1" ]; then
        set -- php-fpm "$@"
fi

# Refuse to start if a static AWS access key pair is in the environment.
# Secrets must be declared explicitly, per container, in the task definition.
# AWS API access comes from the task role, never from long-lived keys, and
# nothing here pulls secrets in bulk from Secrets Manager.
if [ -n "$AWS_ACCESS_KEY_ID" ] || [ -n "$AWS_SECRET_ACCESS_KEY" ]; then
    echo "FATAL: static AWS credentials (AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY) are set in this container's environment." >&2
    echo "Remove them from the task definition and declare the secrets this container actually needs. Refusing to start." >&2
    exit 78
fi

# Link EFS themes and plugins
if [ -f /app/scripts/build-scripts/link-efs-themes-plugins.sh ]; then
    echo "Linking EFS themes and plugins..."
    source /app/scripts/build-scripts/link-efs-themes-plugins.sh
fi

if [ "$WP_ENV" = "production" ]; then
        # cp /app/site/web/app/plugins/redis-cache/includes/object-cache.php /app/site/web/app/object-cache.php
        echo "Object cache installation: skipped."
fi

exec "$@"