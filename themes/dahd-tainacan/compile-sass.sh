#!/bin/bash

command -v npx sass >/dev/null 2>&1 || {
  echo >&2 "REQUIRE: SASS installed to compile the SCSS archives to CSS.";
  exit 1;
}

echo "Compiling Sass..."

# Theme Styles
npx sass --no-source-map assets/scss/style.scss style.css
echo "Style of Tainacan Compiled";

# Editor Styles
npx sass --no-source-map --style=compressed assets/scss/editor-style.scss editor-style.css
echo "Style of Tainacan for Gutenberg Editor Compiled";

# Vendor Styles
npx sass --no-source-map --style=compressed assets/scss/bootstrap_custom.scss assets/vendor/bootstrap/css/bootstrap.min.css
echo "Bootstrap Compiled";

npx sass --no-source-map --style=compressed assets/vendor/slick/slick.scss       assets/vendor/slick/css/slick.min.css
npx sass --no-source-map --style=compressed assets/vendor/slick/slick-theme.scss assets/vendor/slick/css/slick-theme.min.css
echo "Slick for slider carousel Compiled";

rm -rf *.css.map
echo "CSS map files removed";

echo "Sass compilation Completed!"
exit 0
