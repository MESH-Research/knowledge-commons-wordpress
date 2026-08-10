#!/bin/bash
#
# wp-cron-multisite.bash — process WordPress cron for every site, one site per
# isolated wp-cli process.
#
# This is the "external cron" that replaces WordPress's built-in loopback
# WP-Cron (which is disabled in production via DISABLE_WP_CRON — see
# site/config/application.php). It is the wp-cli realisation of the pattern in
# https://www.lucasrolff.com/wordpress/why-wp-cron-sucks/ ("Solution 2"):
# instead of firing cron on front-end page loads, a scheduled task walks every
# site and runs that site's due events.
#
# Why one wp-cli process per site (rather than a single PHP script looping over
# all sites): each `wp cron event run` is a fresh PHP process whose memory is
# reclaimed by the OS on exit. Iterating thousands of sites inside one long-lived
# process would re-create exactly the unbounded-growth problem we are trying to
# avoid; per-site processes keep memory bounded (this is why the dedicated cron
# task's memory sawtooths cleanly instead of climbing).
#
# Fault tolerance: one broken site must never stop cron for the rest of the
# network, so a per-site failure is logged and skipped, and the run as a whole
# still succeeds (exit 0). The run only fails loudly if the site list itself
# cannot be retrieved.
#
# Environment:
#   WP_PATH        Path to the WordPress core install (default: /app/site/web/wp)
#   WP_CRON_QUIET  If "true", suppress per-site "processed" lines (failures still logged)
#
# Note: RUN_CRON gating lives in the crontab entry that invokes this script, to
# match the other jobs in commons.crontab.

set -uo pipefail

WP_PATH="${WP_PATH:-/app/site/web/wp}"
QUIET="${WP_CRON_QUIET:-false}"

log() { echo "$(date '+%Y-%m-%d %H:%M:%S') wp-cron-multisite: $*"; }

# Retrieve every site across every network in the install. `wp site list`
# reads the global wp_blogs table, so a single call covers all networks.
sites="$(wp site list --field=url --path="$WP_PATH" 2>/dev/null)"
if [ $? -ne 0 ] || [ -z "$sites" ]; then
    log "ERROR: could not retrieve site list from '$WP_PATH' — aborting"
    exit 1
fi

total=0
failed=0
start_ts="$(date '+%s' 2>/dev/null || echo 0)"

while IFS= read -r url; do
    [ -z "$url" ] && continue
    total=$((total + 1))
    # Isolated process per site; --due-now runs only events that are actually due.
    if wp cron event run --due-now --path="$WP_PATH" --url="$url" >/dev/null 2>&1; then
        [ "$QUIET" = "true" ] || log "ok   $url"
    else
        failed=$((failed + 1))
        log "WARN cron run failed for $url (continuing)"
    fi
done <<< "$sites"

end_ts="$(date '+%s' 2>/dev/null || echo 0)"
elapsed=$((end_ts - start_ts))
log "done: $total site(s) processed, $failed failure(s), ${elapsed}s"

# A per-site failure is not a run failure — cron must keep working for the rest.
exit 0
