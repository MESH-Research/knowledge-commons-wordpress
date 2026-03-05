#!/bin/sh
set -e

# Patch PHP-FPM to listen on all interfaces (production binds to 127.0.0.1)
sed -i 's/^listen = 127\.0\.0\.1:9000/listen = 0.0.0.0:9000/' /usr/local/etc/php-fpm.d/www.conf

# Create media directories expected by the cloud Dockerfile symlinks
mkdir -p /media/uploads /media/blogs.dir
chown -R www-data:www-data /media

# Create symlink for force-bp-config mu-plugin (mounted at /app/mu-plugins/ but
# WordPress loads from /app/site/web/app/mu-plugins/ via build-time symlinks)
if [ -f /app/mu-plugins/force-bp-config.php ]; then
    ln -sf /app/mu-plugins/force-bp-config.php /app/site/web/app/mu-plugins/force-bp-config.php
fi

exec "$@"
