#!/bin/sh

# Build containers and push to AWS.
# Requires authenticated session with AWS CLI.

# Pre-build steps

composer update

cd site/web/app/plugins/cc-client
npm install
npm run build
cd ../../../../../

cd themes/boss-child
npm install
gulp sass
cd ../../

# Docker build and push
aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin 755997884632.dkr.ecr.us-east-1.amazonaws.com

docker image build -t wordpress-nginx:latest -f Dockerfile.nginx .
docker image build -t wordpress-app:latest -f Dockerfile.php --target cloud .
docker image build -t wordpress-cron:latest -f Dockerfile.php --target cron .

docker tag wordpress-nginx:latest 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-nginx:latest
docker tag wordpress-app:latest 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-app:latest
docker tag wordpress-cron:latest 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-cron:latest

docker push 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-nginx:latest
docker push 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-app:latest
docker push 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-wordpress-cron:latest