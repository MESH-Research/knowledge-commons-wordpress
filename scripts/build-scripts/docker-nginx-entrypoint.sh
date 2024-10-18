#!/bin/sh
set -e

echo "Starting custom entrypoint script"

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

exec /docker-entrypoint.sh "$@"
