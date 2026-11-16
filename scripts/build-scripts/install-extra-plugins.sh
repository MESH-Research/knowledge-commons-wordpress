#!/bin/sh
#
# Install branch-specific extra composer plugins.
#
# Each deployment branch may carry a small, self-contained composer project at
# extra-plugins/<branch>/ (its own composer.json + composer.lock) whose
# installer-paths point back into site/web/app/. The root composer.json and
# composer.lock stay identical across branches; this script decides at image
# build time, from the branch being built, whether any extras apply.
#
# Usage: install-extra-plugins.sh <branch> [repo-root]
#
# Exits 0 (doing nothing) when the branch has no extras project. Only a plain
# branch name maps to a directory: anything containing "/" is treated as having
# no extras, so a feature branch can never resolve outside extra-plugins/.

set -eu

branch="${1:?usage: install-extra-plugins.sh <branch> [repo-root]}"
root="${2:-/app}"

case "$branch" in
    ""|*/*|.|..)
        echo "install-extra-plugins: no extras for branch '$branch'"
        exit 0
        ;;
esac

dir="$root/extra-plugins/$branch"

if [ ! -f "$dir/composer.json" ]; then
    echo "install-extra-plugins: no extras for branch '$branch'"
    exit 0
fi

echo "install-extra-plugins: installing extras for branch '$branch' from $dir"
cd "$dir"
exec composer install \
    --no-dev --no-interaction --no-progress --no-scripts --optimize-autoloader
