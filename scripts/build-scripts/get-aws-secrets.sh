#!/bin/bash

# Check if required environment variables are set
if [ -z "$AWS_ACCESS_KEY_ID" ] || [ -z "$AWS_SECRET_ACCESS_KEY" ] || [ -z "$AWS_REGION" ] || [ -z "$AWS_SECRETS_ARN" ]; then
    echo "Error: Required AWS environment variables are not set."
    exit 1
fi

# Retrieve the secret from AWS Secrets Manager
secret=$(aws secretsmanager get-secret-value --secret-id "$AWS_SECRETS_ARN" --query SecretString --output text)

# Check if the secret was retrieved successfully
if [ $? -ne 0 ]; then
    echo "Error: Failed to retrieve secret from AWS Secrets Manager."
    exit 1
fi

# Parse the JSON and export each key/value pair
while IFS='=' read -r key value; do
    # Remove surrounding quotes if present
    value=$(echo "$value" | sed -e 's/^"//' -e 's/"$//')
    # Export the key-value pair
    export "$key=$value"
done < <(echo "$secret" | jq -r 'to_entries[] | "\(.key)=\(.value)"')

# Optionally, you can print a success message
echo "AWS secrets have been successfully retrieved and exported to the environment."
