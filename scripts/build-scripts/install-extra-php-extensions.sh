#!/bin/sh
#
# Install branch-specific extra PHP core extensions.
#
# Companion to install-extra-plugins.sh: a deployment branch may list PHP core
# extensions, one per line, in extra-plugins/<branch>/php-extensions ("#"
# comments and blank lines ignored). They are installed with
# docker-php-ext-install, so only extensions bundled with the PHP source are
# supported. Must run as root inside the image build.
#
# Usage: install-extra-php-extensions.sh <branch> [repo-root]
#
# Exits 0 (doing nothing) when the branch has no list. Only a plain branch
# name maps to a directory: anything containing "/" is treated as having no
# extras, so a feature branch can never resolve outside extra-plugins/.

set -eu

branch="${1:?usage: install-extra-php-extensions.sh <branch> [repo-root]}"
root="${2:-/app}"

case "$branch" in
    ""|*/*|.|..)
        echo "install-extra-php-extensions: no extra extensions for branch '$branch'"
        exit 0
        ;;
esac

list="$root/extra-plugins/$branch/php-extensions"

if [ ! -f "$list" ]; then
    echo "install-extra-php-extensions: no extra extensions for branch '$branch'"
    exit 0
fi

# Strip comments and whitespace, drop empty lines, join into one argument list.
exts=$(sed -e 's/#.*$//' -e 's/^[[:space:]]*//' -e 's/[[:space:]]*$//' "$list" | grep -v '^$' | tr '\n' ' ' | sed 's/ $//')

if [ -z "$exts" ]; then
    echo "install-extra-php-extensions: list for branch '$branch' is empty"
    exit 0
fi

echo "install-extra-php-extensions: installing for branch '$branch': $exts"
# shellcheck disable=SC2086  # word splitting is the point here
exec docker-php-ext-install $exts
