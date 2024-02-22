#!/bin/bash

networks=("ajs" "arlisna" "mla" "msu" "sah" "up" "hastac")
path="/app/site/web/wp"

# show help & bail if no arguments passed
if [[ -z "$*" ]]
then
        echo "usage: $0 [wp command]"
        echo "  e.g. $0 plugin activate debug-bar"
        exit 1
fi

# first the main network
wp --url="$WP_DOMAIN" --path="$path" $*

# now the rest
for slug in "${networks[@]}"
do
        wp --url="$slug.$WP_DOMAIN" --path="$path" $*
done
