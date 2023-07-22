# Composer

External dependencies are managed by Composer, with `composer.json` in the project root. This manages WordPress itself, external plugins & themes, SimpleSAMLPhp, and other dependencies. Building for local dev runs `composer update` to download available updates. Building for production and staging runs `composer install` to match current versions without updating.

## Adding and removing themes & plugins

WordPress themes and plugins are managed by Composer.

To add a plugin: `lando composer require wpackagist-plugin/<plugin slug>`

To add a theme: `lando composer require wpackagist-theme/<theme slug>`

## Updating themes & plugins

Minor updates are done automatically on rebuild or can be triggered manually by running `lando composer update` (all packages) or `lando composer update <package>`.

For major version updates, run `lando composer require <package> "^<new version>"` or edit `composer.json` directly.

## Removing themes & plugins

To remove a theme or plugin, it must be removed from `composer.json`. This can be done either by directly editing the file or running `lando composer remove <package name>`.

## Updating WordPress

To update WordPress itself to a new major version, run `lando composer require roots/wordpress "^<new version>"`.