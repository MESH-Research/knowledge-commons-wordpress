#!/bin/bash

# when two branches have different composer installs, merging can create a conflict that only affects the hash.
#
# this script attempts to automate resolving that conflict by:
#   removing the conflict markers from the composer.lock file,
#   running the most recent composer require again,
#   and staging the resulting composer.lock with the updated hash.
#
# run this from inside /srv/www/commons/current

set -ex

sed -i '7d;8d;9d;11d' composer.lock

$(git log --all --grep='composer require' -1 --format=%B)

git add composer.lock

echo 'if all looks good, run git commit now to finish the merge'
