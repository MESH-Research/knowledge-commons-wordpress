#!/bin/sh
# Print the release version a build should carry.
#
# Usage: resolve-build-version.sh <branch> [next_version]
#
# main owns the version line: commitizen bumps the VERSION file there and
# tags the bump commit v<version>. main therefore reports the VERSION file,
# or the next version commitizen has just computed when the caller passes it.
#
# Every other branch (dev, staging, production) is promoted *into* main and
# never receives main's bump commits, so its VERSION file stops at whatever
# release was last merged down (it sat at 1.2.1 while main reached 1.6.0).
# Those branches report the highest released v<major>.<minor>.<patch> tag in
# the repository instead. Tags need not be reachable from the branch; they
# only need to have been fetched (actions/checkout with fetch-depth: 0 does).
# The VERSION file still wins if it is higher than any tag, and is the
# fallback when there are no tags at all.
set -eu

branch="${1:-}"
next_version="${2:-}"

if [ -z "$branch" ]; then
	echo "usage: $0 <branch> [next_version]" >&2
	exit 64
fi

file_version="$(tr -d '[:space:]' < VERSION)"

if [ "$branch" = "main" ]; then
	printf '%s\n' "${next_version:-$file_version}"
	exit 0
fi

highest_tag="$(git tag --list 'v*' | sed -n 's/^v\([0-9][0-9]*\.[0-9][0-9]*\.[0-9][0-9]*\)$/\1/p' | sort -V | tail -n 1)"

printf '%s\n%s\n' "$file_version" "$highest_tag" | grep -v '^$' | sort -V | tail -n 1
