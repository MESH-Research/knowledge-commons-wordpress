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

set -ex

if [[ -e 'style.css' ]]
then
	author=$(grep '^Author:' style.css | awk '{print $2}')
	name=$(grep '^Theme Name:' style.css | awk '{print $3}' | tr '[:upper:]' '[:lower:]')
	version=$(grep '^Version:' style.css | awk '{print $2}')
	type=theme
else
	echo "plugins not yet supported, sorry"
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
s3cmd sync $zipped s3://mla-backup/commons/packages/

# output composer require command for copy/pasting
echo composer require $author/$name
