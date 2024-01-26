#!/bin/bash
set -ex

# Helper script to scaffold a new plugin using HC-specific defaults.

#wp scaffold plugin
#<slug>
#[--dir=<dirname>]
#[--plugin_name=<title>]
#[--plugin_description=<description>]
#[--plugin_author=<author>]
#[--plugin_author_uri=<url>]
#[--plugin_uri=<url>]
#[--skip-tests]
#[--ci=<provider>]
#[--activate]
#[--activate-network]
#[--force]


slug="hc-notifications"
name="HC Notifications"
desc="$name"

sudo -u www-data wp --url=$(hostname) scaffold plugin \
	$slug \
	--dir=/srv/www/commons/current/web/app/plugins/ \
	--plugin_name="$name" \
	--plugin_description="$desc" \
	--plugin_author=MLA \
	--plugin_author_uri=https://github.com/mlaa \
	--plugin_uri=https://github.com/mlaa/$slug.git
