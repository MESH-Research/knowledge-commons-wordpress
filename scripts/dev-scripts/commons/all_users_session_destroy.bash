#!/bin/bash

set -ex

wp="wp --url=$(hostname) --skip-plugins --skip-themes --skip-packages"

for id in $($wp user list --field=ID --orderby=ID)
do
  $wp user session destroy --all $id
done
