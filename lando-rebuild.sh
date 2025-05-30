#!/bin/sh

# Build php container for wordpress and cron services before running
# lando rebuild -y because lando doesn't seem to support multistage builds
# yet.

docker build -t lando-wordpress-php:latest -f Dockerfile.php --target lando .
lando rebuild -y