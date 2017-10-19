#!/bin/bash
set -ex

# usage:
# after updating a package already required by composer,
# run this script & pass the package name to make composer require the latest version
# e.g.
#
# composer_require.bash mlaa/hc-custom

package="$1" # e.g. mlaa/hc-custom
branch="master" # TODO configurable
ref="$(git ls-remote git://github.com/$package.git | grep $branch)"

cmd="composer require $package:dev-$branch#${ref:0:7}"

$cmd
git add composer.{json,lock}
git diff --staged
git commit -m "$cmd"
