#!/bin/sh

ENV_FILE="/etc/environment"

if [ -z "$AWS_ACCESS_KEY_ID" ] || [ -z "$AWS_SECRET_ACCESS_KEY" ] || [ -z "$AWS_REGION" ] || [ -z "$AWS_SECRETS_ARN" ]; then
    echo "Error: Required AWS environment variables are not set."
    exit 1
fi

secret=$(aws secretsmanager get-secret-value --secret-id "$AWS_SECRETS_ARN" --query SecretString --output text)

if [ $? -ne 0 ]; then
    echo "Error: Failed to retrieve secret from AWS Secrets Manager."
    exit 1
fi

echo "$secret" | jq -r 'to_entries[] | "\(.key)=\(.value)"' | while IFS='=' read -r key value; do
    value=$(echo "$value" | sed -e 's/^"//' -e 's/"$//')
    export "$key=$value"
    echo "export $key='$value'" >> "$ENV_FILE"
done

chmod 600 "$ENV_FILE"

echo "AWS secrets have been successfully retrieved and exported to the environment."
echo "Environment variables have been written to $ENV_FILE"

if ! grep -q "source $ENV_FILE" /etc/profile; then
    echo "source $ENV_FILE" >> /etc/profile
fi