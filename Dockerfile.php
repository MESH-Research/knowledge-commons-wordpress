# PHP container for running WordPress

FROM php:8.2-fpm-alpine AS base

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
	install-php-extensions exif imagick zip memcached redis mysqli intl yaml

ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/
RUN chmod a+rx /usr/local/bin/wp-cli.phar && \
	mv /usr/local/bin/wp-cli.phar /usr/local/bin/wp

RUN apk update && apk add mysql-client 

FROM base AS lando

RUN apk add git mysql py3-pip py-cryptography mandoc aws-cli linux-headers bash

EXPOSE 9000

FROM base AS cloud

# This is a bit awkward, but we want to copy only the necessary files to the
# container. If we copy the entire root directory of the project, there will
# be a lot of junk files from development that we don't need.
COPY ./site /app/site
COPY ./scripts /app/scripts
COPY ./core-plugins /app/core-plugins
COPY ./forked-plugins /app/forked-plugins
COPY ./ancillary-plugins /app/ancillary-plugins
COPY ./mu-plugins /app/mu-plugins
COPY ./themes /app/themes
COPY ./simplesamlphp /app/simplesamlphp
COPY ./config /app/config

COPY composer.json /app/
COPY composer.lock /app/
COPY wp-cli.yml /app/

RUN chown -R www-data:www-data /app && \
	find /app -type d -exec chmod 755 {} \; && \
	find /app -type f -exec chmod 644 {} \;

# Linking uploads folders to EFS volume mounted at /media
RUN mkdir -p /media && \
	chown www-data:www-data /media && \
	rm -rf /app/site/web/app/uploads && \
	ln -s /media/uploads /app/site/web/app/uploads && \
	rm -rf /app/site/web/app/blogs.dir && \
	ln -s /media/blogs.dir /app/site/web/app/blogs.dir

WORKDIR /app
ENV COMPOSER_ALLOW_SUPERUSER=1
# Should get rid of this script and just run the commands directly
RUN chmod a+x /app/scripts/build-scripts/build-wordpress-cloud.sh && \
	/app/scripts/build-scripts/build-wordpress-cloud.sh

FROM cloud AS cron

RUN apk add bash
RUN crontab -u www-data /app/scripts/cron/commons.crontab

ENTRYPOINT ["crond", "-f"]