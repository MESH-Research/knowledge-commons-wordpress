#!/bin/bash -e

#
# Update this repo to the latest version with git pull.
#

install_dir="$(dirname $(readlink -f ${BASH_SOURCE[0]}))"

pushd $install_dir

git pull --ff-only --recurse-submodules
git submodule update --init --recursive

popd
