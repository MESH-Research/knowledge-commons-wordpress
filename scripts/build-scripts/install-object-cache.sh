#!/bin/sh
# Install (or remove) the Redis Object Cache drop-in.
#
# Sourced by both container entrypoints. Both must run it: a container that
# writes to the database without the drop-in (the cron container running
# wp-cli, say) cannot invalidate keys that the other container is serving from
# Valkey, so the two would drift apart.
#
#   WP_REDIS_HOST unset     -> no drop-in (local/test environments)
#   KC_OBJECT_CACHE=off     -> no drop-in, even if a host is set (escape hatch)
#   otherwise               -> copy the plugin's drop-in into place
#
# APP_ROOT defaults to /app; tests point it at a scratch directory.

APP_ROOT="${APP_ROOT:-/app}"
DROPIN_SRC="$APP_ROOT/site/web/app/plugins/redis-cache/includes/object-cache.php"
DROPIN_DST="$APP_ROOT/site/web/app/object-cache.php"

if [ "$KC_OBJECT_CACHE" = "off" ]; then
    rm -f "$DROPIN_DST"
    echo "Object cache installation: disabled by KC_OBJECT_CACHE=off."
elif [ -z "$WP_REDIS_HOST" ]; then
    rm -f "$DROPIN_DST"
    echo "Object cache installation: skipped (WP_REDIS_HOST not set)."
elif [ -f "$DROPIN_SRC" ]; then
    cp "$DROPIN_SRC" "$DROPIN_DST"
    echo "Object cache installation: installed for $WP_REDIS_HOST."
else
    echo "Object cache installation: skipped (redis-cache plugin not found at $DROPIN_SRC)." >&2
fi
