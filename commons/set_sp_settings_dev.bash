#!/bin/bash
set -ex

domain=$(hostname)
networks=("" "ajs" "aseees" "caa" "mla" "up")
path="/srv/www/commons/current/web/wp"
pre_php=/tmp/__pre.php; [[ -e "$pre_php" ]] || echo "<?php error_reporting( 0 ); define( 'WP_DEBUG', false );" > "$pre_php"

# echo env variable values
# $1 name of .env variable
# $2 server from which to fetch value
_get_env_var() {
  local line=$(ssh $remote_user@$2 "grep '^$1' /srv/www/commons/current/.env")
  echo $line | awk -F '=' '{print $2}' | tr -d '"'
}

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

	wp="sudo -u www-data wp --require=$pre_php --url=$url --path=$path"

	echo $wp
	json='{"from_email":"'$from_email'","from_name":"'$from_name'","password":"'$password'","enable_sparkpost":true,"sending_method":"api","enable_tracking":true}'
        $wp option update --format=json sp_settings "$json"


	# also update bp-reply-by-email options to match

	inbound_domain="$(if [[ -z "$slug" ]]; then echo 'reply.hcommons-dev.org'; else echo "reply.$slug.hcommons-dev.org"; fi)"

	$wp option get bp-rbe || \
		$wp option add bp-rbe '{"mode":"inbound","key":"5a00895941b8d","inbound-provider":"sparkpost","inbound-domain":"'$inbound_domain'","keepalive":"15"}' --format='json'

	$wp option patch insert bp-rbe inbound-provider sparkpost || \
		$wp option patch update bp-rbe inbound-provider sparkpost

        $wp option patch insert bp-rbe inbound-domain $inbound_domain || \
		$wp option patch update bp-rbe inbound-domain $inbound_domain

	# set mashsharer_hashtag to dev twitter account if found

	env_twitter_username=$(_get_env_var TWITTER_USERNAME)

	$wp option get mashsb_settings || \
		$wp option patch update mashsb_settings mashsharer_hashtag $env_twitter_username

done
