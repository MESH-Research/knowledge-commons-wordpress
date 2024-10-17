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

touch ENV_FILE
echo "$secret" | jq -r 'to_entries|map("\(.key|tostring)=\(.value|@sh)")|.[]' >> $ENV_FILE
export $(cat $ENV_FILE | xargs)
