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
echo "$secret" | jq -r 'to_entries | .[] | "export \(.key)=\(.value)"' | while read -r line; do
    eval "$line"
done

# Optionally, you can print a success message
echo "AWS secrets have been successfully retrieved and exported to the environment."

