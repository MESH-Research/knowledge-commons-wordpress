#!/bin/bash
#set -ex

domain=$(hostname)
networks=("ajs" "arlisna" "aseees" "caa" "mla" "msu" "sah" "up")
#networks=("ajs" "aseees" "caa" "mla" "up")
path="/srv/www/commons/current/web/wp"
pre_php=/tmp/__pre.php; [[ -e "$pre_php" ]] || echo "<?php error_reporting( 0 ); define( 'WP_DEBUG', false );" > "$pre_php"

# show help & bail if no arguments passed
if [[ -z "$*" ]]
then
        echo "usage: $0 [wp command]"
        echo "  e.g. $0 plugin activate debug-bar"
        exit 1
fi

# first the main network
sudo -u www-data wp --require="$pre_php" --url="$domain" --path="$path" $*

# now the rest
for slug in "${networks[@]}"
do
        sudo -u www-data wp --require="$pre_php" --url="$slug.$domain" --path="$path" $*
done
