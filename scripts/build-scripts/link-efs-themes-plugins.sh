#!/bin/sh

# Link plugins from EFS volume to WordPress plugins directory
if [ -d "/content/plugins" ]; then
  for plugin in /content/plugins/*/; do
    if [ -d "$plugin" ]; then
      rm -rf /app/site/web/app/plugins/$plugin
      ln -sf "$plugin" /app/site/web/app/plugins/
    fi
  done
fi

# Link themes from EFS volume to WordPress themes directory 
if [ -d "/content/themes" ]; then
  for theme in /content/themes/*/; do
    if [ -d "$theme" ]; then
      rm -rf /app/site/web/app/themes/$theme
      ln -sf "$theme" /app/site/web/app/themes/
    fi
  done
fi
