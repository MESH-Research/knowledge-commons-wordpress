# PHP container for running WordPress

FROM php:8.2.26-fpm-alpine3.20 AS base

WORKDIR /app
RUN chown -R www-data:www-data /app

RUN apk update && \
    apk add --no-cache \
    mysql-client \
    bash \
    aws-cli \
    jq \
    npm \
    mysql \
    py3-pip \
    py-cryptography \
    mandoc \
    linux-headers \
    git \
    grpc-cpp \
    grpc-dev \
    rsync \
    $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

RUN git config --global --add safe.directory /app

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
    exif \
    imagick \
    zip \
    memcached \
    redis \
    mysqli \
    intl \
    yaml \
    protobuf

ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/wp
RUN chmod a+rx /usr/local/bin/wp


FROM base AS lando

EXPOSE 9000

FROM lando AS lando-efs

FROM base AS cloud

RUN apk add npm

RUN mkdir -p /app && chown www-data:www-data /app
RUN mkdir -p /app/site && chown www-data:www-data /app/site
RUN mkdir -p /app/site/web && chown www-data:www-data /app/site/web
COPY --chown=www-data:www-data ./scripts /app/scripts
COPY --chown=www-data:www-data ./site/web/*.* /app/site/web/
COPY --chown=www-data:www-data ./plugins /app/plugins
COPY --chown=www-data:www-data ./mu-plugins /app/mu-plugins
COPY --chown=www-data:www-data ./themes /app/themes
COPY --chown=www-data:www-data ./site/config /app/site/config
COPY --chown=www-data:www-data wp-cli.yml /app/
COPY --chown=www-data:www-data ./simplesamlphp /app/simplesamlphp
COPY --chown=www-data:www-data ./config /app/config

COPY --chown=www-data:www-data composer.json /app/
COPY --chown=www-data:www-data composer.lock /app/

RUN rm -rf /app/site/web/app/plugins/* && \
    rm -rf /app/site/web/app/themes/* && \
    rm -rf /app/site/web/app/mu-plugins/* && \
    mkdir -p /app/site/web/app/plugins && \
    mkdir -p /app/site/web/app/themes && \
    mkdir -p /app/site/web/app/mu-plugins && \
    chown www-data:www-data /app/site/web/app/plugins && \
    chown www-data:www-data /app/site/web/app/themes && \
    chown www-data:www-data /app/site/web/app/mu-plugins && \
    ln -s /app/plugins/*/ /app/site/web/app/plugins/ && \
    ln -s /app/mu-plugins/* /app/site/web/app/mu-plugins/ && \
    ln -s /app/themes/*/ /app/site/web/app/themes/

#Linking uploads folders to EFS volume mounted at /media
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

RUN echo "Installing Composer dependencies..."
WORKDIR /app
USER www-data
RUN php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --optimize-autoloader --no-cache && \
    cd /app/scripts/cron/mailchimp && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --optimize-autoloader --no-cache && \
    cd /app/scripts/dev-scripts/content-export/ && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --optimize-autoloader --no-cache && \
    cd /app/themes/dahd-tainacan/ && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --optimize-autoloader --no-cache && \
    cd /app/plugins/wp-graphql-tax-query/ && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --optimize-autoloader --no-cache && \
    cd /app/themes/learningspace/ && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --optimize-autoloader --no-cache && \
    cd /app/plugins/hc-styles/ && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --optimize-autoloader --no-cache
RUN echo "Finished installing Composer dependencies"

RUN cd /app/site/web/app/plugins/kcworks-on-wp && npm install @wordpress/scripts --save-dev && \
    cd /app/site/web/app/plugins/kcworks-on-wp && npm ci && npm run build

RUN cd /app/site/web/app/plugins/cc-client && npm ci && npm run build && \
    cd /app/themes/boss-child && npm ci && npm install gulp && node node_modules/gulp-cli/bin/gulp sass && \
    cd /app/themes/boss-child-refresh && npm ci && npm install gulp && node node_modules/gulp-cli/bin/gulp sass

USER root
RUN touch /etc/environment && \
    chown www-data:www-data /etc/environment && \
    chmod 664 /etc/environment && \
    touch /etc/profile && \
    chown root:www-data /etc/profile && \
    chmod 664 /etc/profile

ENTRYPOINT ["/app/scripts/build-scripts/docker-php-entrypoint.sh"]
CMD ["php-fpm"]
