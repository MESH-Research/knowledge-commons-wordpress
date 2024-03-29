#!/bin/bash

# given a wordpress plugin or theme, this script is intended to automate the following steps:
#
# 1. write new composer.json with values parsed from package
# 2. zip package
# 3. upload to s3
#
# IMPORTANT - run from inside the package directory.

# e.g.
#
# cd path/to/unzipped/theme/
# ~/code/dev-scripts/commons/composerify_package.bash
#
# optional parameters, if empty the script will try to parse on its own
# $1 author
# $2 name
# $3 version
#

set -ex

# first determine package type
type=plugin
[[ -e 'style.css' ]] && type=theme

# read user input if provided, otherwise parse according to type
if [[ -n "$1" ]]
then
	author="$1"
	name="$2"
	version="$3"
elif [[ "$type" = 'theme' ]]
then
	author=$(grep '^Author:' style.css | awk '{print $2}')
	name=$(grep '^Theme Name:' style.css | awk '{print $3}' | tr '[:upper:]' '[:lower:]')
	version=$(grep '^Version:' style.css | awk '{print $2}')
else
	author=$(grep '^Author:' *php | awk '{print $2}')
	name=$(grep '^Plugin Name:' *php | awk '{print $3}' | tr '[:upper:]' '[:lower:]')
	version=$(grep '^Version:' *php | awk '{print $2}')
	echo 'plugin support still experimental, check for correct parsing before continuing'
	exit 1
fi

zipped="$author-$name-$version.zip"

# write composer.json
cat <<EOF > composer.json
{
	"name": "$author/$name",
	"type": "wordpress-$type",
	"version": "$version"
}
EOF

# zip package
zip -qr $zipped *

# upload to s3
aws s3 cp $zipped s3://mla-backup/commons/packages/

# output composer require command for copy/pasting
echo "aws s3 sync s3://mla-backup/commons/packages /tmp/wordpress-packages --only-show-errors --delete && composer require $author/$name"
