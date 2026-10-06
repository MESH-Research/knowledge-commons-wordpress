#!/bin/sh
set -e

echo "Starting custom entrypoint script"

# Refuse to start if a static AWS access key pair is in the environment.
# Secrets must be declared explicitly, per container, in the task definition.
# AWS API access comes from the task role, never from long-lived keys, and
# nothing here pulls secrets in bulk from Secrets Manager.
if [ -n "$AWS_ACCESS_KEY_ID" ] || [ -n "$AWS_SECRET_ACCESS_KEY" ]; then
    echo "FATAL: static AWS credentials (AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY) are set in this container's environment." >&2
    echo "Remove them from the task definition and declare the secrets this container actually needs. Refusing to start." >&2
    exit 78
fi

exec /docker-entrypoint.sh "$@"
