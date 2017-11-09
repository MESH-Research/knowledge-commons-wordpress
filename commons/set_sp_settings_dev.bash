#!/bin/bash
set -e

domain=$(hostname)
networks=("" "ajs" "aseees" "caa" "mla" "up")
path="/srv/www/commons/current/web/wp"
pre_php=/tmp/__pre.php; [[ -e "$pre_php" ]] || echo "<?php error_reporting( 0 ); define( 'WP_DEBUG', false );" > "$pre_php"

# sparkpost api token
password="a5f9421a85cb0373cc8e3d080b01c7a1dbe41149"

for slug in "${networks[@]}"
do
        if [[ "" == "$slug" ]]
	then
		from_email="hc@hcommons-dev.org"
		from_name="Humanities Commons DEV"
		url="$domain"
	else
		from_email="$slug@hcommons-dev.org"
		from_name="$(echo $slug | tr [a-z] [A-Z]) Commons DEV"
		url="$slug.$domain"
	fi

	json='{"from_email":"'$from_email'","from_name":"'$from_name'","password":"'$password'","enable_sparkpost":true,"sending_method":"api","enable_tracking":true}'
        sudo -u www-data wp --require="$pre_php" --url="$url" --path="$path" option update --format=json sp_settings "$json"
done
