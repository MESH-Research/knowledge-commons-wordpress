#!/bin/bash
set -e

REPO_ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
COMPOSE="docker compose -f $REPO_ROOT/docker-compose.test.yml"

dump_logs() {
    echo "==> PHP fatal error log (if any):"
    $COMPOSE exec app cat /tmp/php-fatal.log 2>/dev/null || echo "(none)"
    echo "==> PHP-FPM error log (last 50 lines):"
    $COMPOSE logs --tail=50 app 2>&1 | grep -i -E "fatal|error|exception" || echo "(none)"
}

cleanup() {
    dump_logs
    echo "==> Tearing down containers..."
    $COMPOSE down -v --remove-orphans 2>/dev/null || true
}

trap cleanup EXIT

echo "==> Building containers..."
$COMPOSE build

echo "==> Starting services..."
$COMPOSE up -d db app nginx

echo "==> Waiting for app to be ready..."
sleep 5

echo "==> Phase 1: WordPress setup (install, plugins, users)..."
$COMPOSE run --rm setup

echo "==> Phase 2: Triggering BuddyPress initialization via web request..."
# BP auto-initializes on first web request through PHP-FPM.
# We must let this happen BEFORE configuring BP, because it overwrites
# any CLI-set options (active components, pages, etc).
# Hit an admin page to trigger bp_admin_init (where bp_version_updater runs).
$COMPOSE exec app curl -s -o /dev/null -H "Host: hcommons.test" \
    -H "Cookie: wordpress_logged_in_dummy=1" \
    "http://nginx:80/wp/wp-admin/" || true
sleep 3
# Second admin request to ensure all init hooks have fired
$COMPOSE exec app curl -s -o /dev/null -H "Host: hcommons.test" \
    -H "Cookie: wordpress_logged_in_dummy=1" \
    "http://nginx:80/wp/wp-admin/" || true
sleep 1

echo "==> Phase 3: Configuring BuddyPress (from app container)..."
# Must run from the app container so BP's PHP state is consistent
$COMPOSE exec app bash /app/scripts/test-scripts/setup-bp.sh

echo "==> Running Playwright tests..."
# Disable set -e so we can capture the exit code and dump logs before cleanup
set +e
$COMPOSE run --rm playwright
TEST_EXIT=$?
set -e

echo "==> Tests complete."
exit $TEST_EXIT
