#!/bin/bash

command -v npx sass >/dev/null 2>&1 || {
  echo >&2 "REQUIRE: SASS installed to compile the SCSS archives to CSS.";
  exit 1;
}

# source build-config.cfg
# destination='/var/www/docroot/wp-content/themes/dahd-tainacan-theme'
# echo $destination

## Compile SASS
sh compile-sass.sh

echo "Compilation Finish!!"
