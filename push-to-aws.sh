#!/bin/sh

# Build containers and push to AWS.
# Requires authenticated session with AWS CLI.

# Pre-build steps

if [[ -z "$1" ]]; then
	tag="latest"
else
	tag=$1
fi

# Docker build and push
aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin 755997884632.dkr.ecr.us-east-1.amazonaws.com

docker image build -t wordpress-nginx:$tag -f Dockerfile.nginx .
docker image build -t wordpress-app:$tag -f Dockerfile.php --target cloud .
docker image build -t wordpress-cron:$tag -f Dockerfile.php --target cron .

docker tag wordpress-nginx:$tag 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-nginx:$tag
docker tag wordpress-app:$tag 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-app:$tag
docker tag wordpress-cron:$tag 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-cron:$tag

docker push 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-nginx:$tag
docker push 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-app:$tag
docker push 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-cron:$tag