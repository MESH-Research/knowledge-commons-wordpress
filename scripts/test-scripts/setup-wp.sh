#!/bin/bash
set -e

WP="wp --allow-root --path=/app/site/web/wp"

# Create media directories
mkdir -p /media/uploads /media/blogs.dir
chown -R www-data:www-data /media 2>/dev/null || true

echo "==> Waiting for database..."
until $WP db check 2>/dev/null; do
    sleep 2
done
echo "==> Database is ready."

echo "==> Installing WordPress multisite..."
$WP core multisite-install \
    --url="http://hcommons.test" \
    --title="KC Test" \
    --admin_user=admin \
    --admin_email=admin@hcommons.test \
    --admin_password=admin \
    --subdomains \
    --skip-email \
    || true

# Fix home URL for Bedrock-style install (WP core is at /wp subdirectory)
$WP option update home "http://hcommons.test" || true

echo "==> Creating test user..."
$WP user create gihctester gihctester@hcommons.test \
    --role=subscriber \
    --user_pass=testpass \
    || true

$WP super-admin add gihctester \
    || true

echo "==> Activating plugins..."
$WP plugin activate buddypress --network \
    || true

$WP plugin activate bbpress --network \
    || true

$WP plugin activate buddypress-docs --network \
    || true

echo "==> Enabling site registration..."
$WP network meta update 1 registration all || true

echo "==> Phase 1 complete (WordPress installed, plugins activated)."
echo "==> BuddyPress configuration must be done via the app container after first web request."
echo "==> Run setup-bp.sh from the app container to complete setup."
