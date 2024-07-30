# PHP container for running WordPress

FROM --platform=$BUILDPLATFORM php:fpm-alpine3.19 AS base
ARG BUILDPLATFORM

RUN addgroup -g 33 xfs || true \
	&& addgroup xfs www-data \
	&& addgroup www-data xfs

COPY --chown=www-data:www-data --from=composer:latest /usr/bin/composer /usr/local/bin/composer

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
	install-php-extensions exif imagick/imagick@master zip memcached redis mysqli intl yaml

ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/
RUN chmod a+rx /usr/local/bin/wp-cli.phar && \
	mv /usr/local/bin/wp-cli.phar /usr/local/bin/wp

RUN apk update && apk add mysql-client 

FROM base AS lando

RUN apk add git mysql py3-pip py-cryptography mandoc aws-cli linux-headers bash

EXPOSE 9000

FROM lando AS lando-efs

FROM base AS cloud

RUN apk add npm

# This is a bit awkward, but we want to COPY --chown=www-data:www-data only the necessary files to the
# container. If we COPY --chown=www-data:www-data the entire root directory of the project, there will
# be a lot of junk files from development that we don't need.
COPY --chown=www-data:www-data wp-cli.yml /app/
COPY --chown=www-data:www-data ./site /app/site
COPY --chown=www-data:www-data ./simplesamlphp /app/simplesamlphp
COPY --chown=www-data:www-data ./themes /app/themes
COPY --chown=www-data:www-data ./config /app/config
COPY --chown=www-data:www-data ./scripts /app/scripts
COPY --chown=www-data:www-data ./core-plugins /app/core-plugins
COPY --chown=www-data:www-data ./forked-plugins /app/forked-plugins
COPY --chown=www-data:www-data ./ancillary-plugins /app/ancillary-plugins
COPY --chown=www-data:www-data ./mu-plugins /app/mu-plugins

RUN rm -rf /app/site/web/app/plugins/* && \
	rm -rf /app/site/web/app/themes/* && \
	rm -rf /app/site/web/app/mu-plugins/* && \
	mkdir -p /app/site/web/app/plugins && \
	mkdir -p /app/site/web/app/themes && \
	mkdir -p /app/site/web/app/mu-plugins && \
	chown www-data:www-data /app/site/web/app/plugins && \
	chown www-data:www-data /app/site/web/app/themes && \
	chown www-data:www-data /app/site/web/app/mu-plugins && \
	ln -s /app/ancillary-plugins/*/ /app/site/web/app/plugins/ && \
	ln -s /app/core-plugins/*/ /app/site/web/app/plugins/ && \
	ln -s /app/forked-plugins/*/ /app/site/web/app/plugins/ && \
	ln -s /app/mu-plugins/* /app/site/web/app/mu-plugins/ && \
	ln -s /app/themes/*/ /app/site/web/app/themes/ && \
	ln -s /app/commons-connect/cc-client /app/site/web/app/plugins/commons-connect

COPY --chown=www-data:www-data composer.json /app/
COPY --chown=www-data:www-data composer.lock /app/


# Linking uploads folders to EFS volume mounted at /media
RUN mkdir -p /media && \
chown www-data:www-data /media && \
ln -sf /media/uploads /app/site/web/app/uploads && \
	ln -sf /media/blogs.dir /app/site/web/app/blogs.dir
	
	RUN rm -rf /usr/local/etc/php/php.ini && \
	ln -sf /app/config/all/php/php.ini /usr/local/etc/php/php.ini && \
	rm -rf /usr/local/etc/php-fpm.d/www.conf && \
	ln -sf /app/config/all/php/www.conf /usr/local/etc/php-fpm.d/www.conf
	
	RUN rm -rf /app/config/all/simplesamlphp/log && \
	rm -rf /app/config/all/simplesamlphp/tmp && \
	mkdir -p /app/config/all/simplesamlphp/log && \
	mkdir -p /app/config/all/simplesamlphp/tmp && \
	chown -R www-data:www-data /app/config/all/simplesamlphp
	
	WORKDIR /app
	USER www-data
	RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader
	WORKDIR /app/core-plugins/humcore/
	RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader
	WORKDIR /app/scripts/cron/mailchimp
	RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader
	WORKDIR /app
	
	WORKDIR /app/site/web/app/plugins/cc-client
	RUN npm install && npm run build
	
	WORKDIR /app/themes/boss-child
	RUN npm install && npm install gulp && node node_modules/gulp-cli/bin/gulp sass

	WORKDIR /app

	ENTRYPOINT ["/app/scripts/build-scripts/docker-php-entrypoint.sh"] 
	CMD ["php-fpm"]
	
	FROM cloud AS cron
	
USER root
RUN apk add bash
RUN crontab -u www-data /app/scripts/cron/commons.crontab

ENTRYPOINT ["crond", "-f"]