# Composer

External dependencies are managed by Composer, with `composer.json` in the project root. This manages WordPress itself, external plugins & themes, SimpleSAMLPhp, and other dependencies. Building for local dev runs `composer update` to download available updates. Building for production and staging runs `composer install` to match current versions without updating.

## Adding and removing themes & plugins

WordPress themes and plugins are managed by Composer.

To add a plugin: `composer require wpackagist-plugin/<plugin slug>`

To add a theme: `composer require wpackagist-theme/<theme slug>`

## Updating themes & plugins

Minor updates are done automatically on rebuild or can be triggered manually by running `composer update` (all packages) or `composer update <package>`.

For major version updates, run `composer require <package> "^<new version>"` or edit `composer.json` directly.

## Updating WordPress

To update WordPress itself to a new major version, run `composer require roots/wordpress "^<new version>"`.