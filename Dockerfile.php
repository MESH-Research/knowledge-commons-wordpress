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
COPY wp-cli.yml /app/
COPY ./site /app/site
COPY ./simplesamlphp /app/simplesamlphp
COPY ./themes /app/themes
COPY ./config /app/config
COPY ./scripts /app/scripts
COPY ./core-plugins /app/core-plugins
COPY ./forked-plugins /app/forked-plugins
COPY ./ancillary-plugins /app/ancillary-plugins
COPY ./mu-plugins /app/mu-plugins

COPY composer.json /app/
COPY composer.lock /app/

# Linking uploads folders to EFS volume mounted at /media
RUN mkdir -p /media && \
	chown www-data:www-data /media && \
	rm -rf /app/site/web/app/uploads && \
	ln -s /media/uploads /app/site/web/app/uploads && \
	rm -rf /app/site/web/app/blogs.dir && \
	ln -s /media/blogs.dir /app/site/web/app/blogs.dir

RUN rm -rf /usr/local/etc/php/php.ini && \
	ln -s /app/config/all/php/php.ini /usr/local/etc/php/php.ini

RUN rm -rf /app/config/all/simplesamlphp/log && \
	rm -rf /app/config/all/simplesamlphp/tmp && \
	mkdir -p /app/config/all/simplesamlphp/log && \
	mkdir -p /app/config/all/simplesamlphp/tmp

RUN chown -R www-data:www-data /app

WORKDIR /app
USER www-data
RUN composer install --no-dev --no-interaction --no-progress --no-suggest --optimize-autoloader
WORKDIR /app/core-plugins/humcore/
RUN composer install --no-dev --no-interaction --no-progress --no-suggest --optimize-autoloader

# Redis drop-in
RUN cp /app/site/web/app/plugins/redis-cache/includes/object-cache.php /app/site/web/app/object-cache.php

FROM cloud AS cron

USER root
RUN apk add bash
RUN crontab -u www-data /app/scripts/cron/commons.crontab

ENTRYPOINT ["crond", "-f"]