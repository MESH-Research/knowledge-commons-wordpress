#!/bin/bash
#
# Run due wp-cron events for every live site across all networks.
#
# Page loads no longer spawn wp-cron.php (DISABLE_WP_CRON is true), so this
# script is the only place scheduled events run. It executes events in-process
# via WP-CLI, so no load lands on the web-serving containers.

set -u

path="/app/site/web/wp"

# wp site list covers every network's sites (wp_blogs is installation-wide);
# skip archived, deleted and spam sites.
wp site list --field=url --path="$path" --archived=0 --deleted=0 --spam=0 \
  | xargs -r -n1 -I{} wp cron event run --due-now --path="$path" --url="{}"
