#!/bin/bash
set -ex

# usage:
# after updating a package already required by composer,
# run this script & pass the package name, & optionally branch (default master),
# to make composer require the latest version
# e.g.
#
# composer_require.bash mlaa/hc-custom
# composer_require.bash mlaa/hc-custom develop

package="$1" # e.g. mlaa/hc-custom
if [[ -n "$2" ]]
then
	branch="$2"
else
	branch="master"
fi
ref="$(git ls-remote git://github.com/$package.git | grep $branch)"

cmd="composer require $package:dev-$branch#${ref:0:7}"

$cmd
git add composer.{json,lock}
git diff --staged
git commit -m "$cmd"
