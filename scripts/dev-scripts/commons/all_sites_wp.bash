#!/bin/bash
set -x

domains="$(mysql -BNe 'SELECT domain FROM hcommons.wp_blogs')"
path="/srv/www/commons/current/web/wp"
pre_php=/tmp/__pre.php; [[ -e "$pre_php" ]] || echo "<?php error_reporting( 0 ); define( 'WP_DEBUG', false );" > "$pre_php"

# show help & bail if no arguments passed
if [[ -z "$*" ]]
then
        echo "usage: $0 [wp command]"
        echo "  e.g. $0 plugin activate debug-bar"
        exit 1
fi

for domain in $domains
do
        sudo -u www-data wp --require="$pre_php" --url="$domain" --path="$path" $*
done
