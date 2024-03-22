#!/bin/bash
set -e

#
# Index (or optionally setup, then index) elasticpress content.
#
# To setup, pass the string "setup" as the first parameter to this script.
# e.g.
# bash bin/index_elasticpress.bash setup
#
# Otherwise, with no parameters, existing content is reindexed without deleting anything.
# e.g.
# bash bin/index_elasticpress.bash
#

all_networks_wp=/app/scripts/cron/all_networks_wp.bash

if [[ "$1" = "setup" ]]
then
  $all_networks_wp elasticpress sync --setup --yes --force
else
  wp --url="$WP_DOMAIN" --path="/app/site/web/wp" elasticpress-buddypress index_from_all_networks --post-type="humcore_deposit"
fi
wp --url="$WP_DOMAIN" --path="/app/site/web/wp" elasticpress-buddypress index members
$all_networks_wp elasticpress-buddypress index groups