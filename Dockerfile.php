# syntax=docker/dockerfile:1
# PHP container for running WordPress

ARG BASE_IMAGE=base

# FROM php:8.5.12-fpm-alpine3.24 AS base
FROM php:8.5.12-fpm-alpine3.24 AS base
#FROM 755997884632.dkr.ecr.us-east-1.amazonaws.com/commons-php-fpm:8.5.12-alpine AS base

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
    subversion \
    $PHPIZE_DEPS \
    && rm -rf /var/cache/apk/*

RUN git config --global --add safe.directory /app

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
# PECL-hosted extensions are installed from pinned source tarballs rather than
# by name. A bare name makes the installer shell out to `pecl install`, which
# fetches metadata and the tarball from pecl.php.net with no retries and a
# ~20s cap; when that host is slow or returning 504s (as it was in Oct 2026)
# the whole image build fails with "Package X does not have REST info xml
# available". The owner/repo@tag form is resolved by the installer to a
# codeload.github.com tarball, downloaded with curl --retry, and built from the
# package.xml inside it, so the build no longer depends on pecl.php.net (the
# installer still runs a tolerated, non-fatal `pecl channel-update`). Pinning
# also makes the base image reproducible. Bump the tags here to upgrade.
#
# Two exceptions:
# - redis: phpredis vendors liblzf as a git submodule, which GitHub's archive
#   tarballs omit, so its package.xml fails validation. It is shallow-cloned
#   with submodules instead and handed to the installer as a local directory.
# - protobuf: its PECL release is generated at publish time and the GitHub repo
#   carries no package.xml, so the PECL tarball is the only source. It is still
#   downloaded from pecl.php.net, but with curl retries rather than the pecl
#   client's single attempt, and unpacked into the layout the installer wants
#   (package.xml inside the source directory, not beside it). Nothing in this
#   repo references protobuf; drop it from this list if it is confirmed unused.
RUN chmod +x /usr/local/bin/install-php-extensions && \
    git clone -q -c advice.detachedHead=false --depth 1 --recurse-submodules --shallow-submodules \
        --branch 6.3.0 https://github.com/phpredis/phpredis.git /tmp/src-phpredis && \
    curl -fsSL --retry 5 --retry-delay 10 --retry-all-errors --connect-timeout 15 \
        -o /tmp/protobuf.tgz https://pecl.php.net/get/protobuf-5.36.2.tgz && \
    mkdir -p /tmp/src-protobuf && \
    tar -xzf /tmp/protobuf.tgz -C /tmp/src-protobuf && \
    mv /tmp/src-protobuf/package.xml /tmp/src-protobuf/protobuf-5.36.2/ && \
    install-php-extensions \
    exif \
    Imagick/imagick@3.8.1 \
    zip \
    php-memcached-dev/php-memcached@v3.4.0 \
    /tmp/src-phpredis \
    mysqli \
    intl \
    php/pecl-file_formats-yaml@2.3.0 \
    /tmp/src-protobuf/protobuf-5.36.2 && \
    rm -rf /tmp/src-phpredis /tmp/src-protobuf /tmp/protobuf.tgz

# OPcache ships with the official image as a shared extension. Some image
# versions load it by default, others do not; enable it only when it is not
# already loaded (docker-php-ext-enable exits non-zero on an already-loaded
# module). The cloud stage below repeats this because that stage is built on a
# prebuilt base image that may predate this line.
RUN php -m | grep -q 'Zend OPcache' || docker-php-ext-enable opcache

ADD https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar /usr/local/bin/wp
RUN chmod a+rx /usr/local/bin/wp


FROM base AS lando

EXPOSE 9000

FROM lando AS lando-efs

FROM ${BASE_IMAGE} AS cloud

# Build-time version metadata — consumed by the late "write version.json"
# step. Declared here so the ARGs are in scope; only the layer that uses them
# (near the end of this stage) is invalidated when the values change, so
# cache stays warm across builds with new versions.
ARG APP_VERSION=dev
ARG BUILD_TAG=dev
ARG GIT_SHA=unknown
ARG APP_BRANCH=unknown

RUN apk add npm

# --- Directory structure (rarely changes) ---
RUN mkdir -p /app /app/site /app/site/web \
    /app/site/web/app/plugins \
    /app/site/web/app/themes \
    /app/site/web/app/mu-plugins && \
    chown -R www-data:www-data /app

# --- EFS mount points ---
RUN mkdir -p /media && \
    chown www-data:www-data /media && \
    ln -sf /media/uploads /app/site/web/app/uploads && \
    ln -sf /media/blogs.dir /app/site/web/app/blogs.dir

# --- Config files (changes rarely) ---
COPY --chown=www-data:www-data ./config /app/config
COPY --chown=www-data:www-data wp-cli.yml /app/
COPY --chown=www-data:www-data ./site/config /app/site/config

RUN rm -rf /usr/local/etc/php/php.ini && \
    ln -sf /app/config/all/php/php.ini /usr/local/etc/php/php.ini && \
    rm -rf /usr/local/etc/php-fpm.d/www.conf && \
    ln -sf /app/config/all/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# --- OPcache (effective on every deploy, independent of the base image) ---
# The deploy workflow builds this stage FROM the prebuilt
# commons-wordpress-base image, which is only rebuilt when Dockerfile.php
# changes on main. Enabling here guarantees the extension is loaded on dev,
# staging and production regardless of the base image's age. The settings
# live in config/all/php/opcache.ini and load from conf.d after php.ini.
RUN ( php -m | grep -q 'Zend OPcache' || docker-php-ext-enable opcache ) && \
    ln -sf /app/config/all/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

# --- Composer manifests ONLY (cached unless lockfiles change) ---
COPY --chown=www-data:www-data composer.json composer.lock /app/
COPY --chown=www-data:www-data scripts/cron/mailchimp/composer.json scripts/cron/mailchimp/composer.lock /app/scripts/cron/mailchimp/
COPY --chown=www-data:www-data scripts/dev-scripts/content-export/composer.json scripts/dev-scripts/content-export/composer.lock /app/scripts/dev-scripts/content-export/
COPY --chown=www-data:www-data themes/dahd-tainacan/composer.json themes/dahd-tainacan/composer.lock /app/themes/dahd-tainacan/
# COPY --chown=www-data:www-data plugins/wp-graphql-tax-query/composer.json plugins/wp-graphql-tax-query/composer.lock /app/plugins/wp-graphql-tax-query/
# COPY --chown=www-data:www-data themes/learningspace/composer.json themes/learningspace/composer.lock /app/themes/learningspace/
COPY --chown=www-data:www-data plugins/hc-styles/composer.json plugins/hc-styles/composer.lock /app/plugins/hc-styles/

# --- Composer install (cached when lockfiles unchanged) ---
RUN composer self-update 2.6.6

WORKDIR /app
USER www-data
RUN --mount=type=cache,target=/home/www-data/.composer/cache,uid=82,gid=82 \
    php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --optimize-autoloader && \
    cd /app/scripts/cron/mailchimp && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader && \
    cd /app/scripts/dev-scripts/content-export/ && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader && \
    cd /app/themes/dahd-tainacan/ && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader && \
#    cd /app/plugins/wp-graphql-tax-query/ && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader && \
#    cd /app/themes/learningspace/ && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader && \
    cd /app/plugins/hc-styles/ && php -d default_socket_timeout=30000 $(which composer) install --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader

# --- npm builds for composer-installed packages (cached with composer layer) ---
RUN --mount=type=cache,target=/home/www-data/.npm,uid=82,gid=82 \
    cd /app/site/web/app/plugins/kcworks-on-wp && npm install @wordpress/scripts --save-dev && \
    cd /app/site/web/app/plugins/kcworks-on-wp && npm ci && npm run build

RUN --mount=type=cache,target=/home/www-data/.npm,uid=82,gid=82 \
    cd /app/site/web/app/plugins/cc-client && npm ci && npm run build

# --- Source code (changes frequently — everything above is cached) ---
COPY --chown=www-data:www-data ./scripts /app/scripts
COPY --chown=www-data:www-data ./site/web/*.* /app/site/web/
COPY --chown=www-data:www-data ./site/web/app/sunrise.php /app/site/web/app/sunrise.php
COPY --chown=www-data:www-data ./plugins /app/plugins
COPY --chown=www-data:www-data ./mu-plugins /app/mu-plugins
COPY --chown=www-data:www-data ./themes /app/themes

# --- Branch-specific extra plugins (dev-only plugins etc.) ---
# extra-plugins/<branch>/ is a self-contained composer project whose
# installer-paths point back into site/web/app/. The manifests live on every
# branch; only the one matching APP_BRANCH is installed, so promoting a change
# between branches never carries another branch's plugins with it. Kept as a
# separate layer so the main composer layer above stays cache-shared across
# branches. See extra-plugins/README.md.
COPY --chown=www-data:www-data ./extra-plugins /app/extra-plugins
RUN --mount=type=cache,target=/home/www-data/.composer/cache,uid=82,gid=82 \
    sh /app/scripts/build-scripts/install-extra-plugins.sh "${APP_BRANCH}" /app

# --- Theme npm builds (need full source for gulpfile.js and sass sources) ---
RUN --mount=type=cache,target=/home/www-data/.npm,uid=82,gid=82 \
    cd /app/themes/boss-child && npm ci && npm install gulp && node node_modules/gulp-cli/bin/gulp sass && \
    cd /app/themes/boss-child-refresh && npm ci && npm install gulp && node node_modules/gulp-cli/bin/gulp sass

# --- Symlinks for custom plugins/themes/mu-plugins ---
# Remove only existing symlinks (not composer-installed plugins) then recreate
RUN find /app/site/web/app/plugins/ -maxdepth 1 -type l -delete && \
    find /app/site/web/app/themes/ -maxdepth 1 -type l -delete && \
    rm -rf /app/site/web/app/mu-plugins/* && \
    ln -s /app/plugins/*/ /app/site/web/app/plugins/ && \
    ln -s /app/mu-plugins/* /app/site/web/app/mu-plugins/ && \
    ln -s /app/themes/*/ /app/site/web/app/themes/

# --- Environment file permissions ---
USER root
RUN touch /etc/environment && \
    chown www-data:www-data /etc/environment && \
    chmod 664 /etc/environment && \
    touch /etc/profile && \
    chown root:www-data /etc/profile && \
    chmod 664 /etc/profile

# --- Branch-specific extra PHP extensions (root; see extra-plugins/README.md) ---
# extra-plugins/<branch>/php-extensions lists core extensions that branch's
# extra plugins need. Installed here, after the extras composer step, so the
# shared base image is untouched and other branches are unaffected.
RUN sh /app/scripts/build-scripts/install-extra-php-extensions.sh "${APP_BRANCH}" /app

# --- Build-version manifest (last RUN so version changes don't invalidate
#     earlier layers). Served from /app/site/web/.version.php which reads
#     this file at request time. ---
RUN printf '{"version":"%s","build":"%s","sha":"%s","branch":"%s"}\n' \
        "${APP_VERSION}" "${BUILD_TAG}" "${GIT_SHA}" "${APP_BRANCH}" \
        > /app/site/web/.version.json && \
    chown www-data:www-data /app/site/web/.version.json

# used for health check endpoint
RUN apk add --no-cache fcgi

ENTRYPOINT ["/app/scripts/build-scripts/docker-php-entrypoint.sh"]
CMD ["php-fpm"]
