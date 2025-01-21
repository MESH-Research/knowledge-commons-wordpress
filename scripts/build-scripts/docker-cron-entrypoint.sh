#!/bin/sh
set -e

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

# Install the crontab
crontab -u www-data /app/scripts/cron/commons.crontab

# Change to the /app directory before starting cron
cd /app

# Run crond in the foreground with logging
exec crond -f -L /dev/stdout
