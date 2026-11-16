# Branch-specific extra plugins

The root `composer.json` and `composer.lock` are shared by every branch. This
directory lets a deployment branch install additional composer-managed
WordPress plugins or themes without forking those files.

Each subdirectory is a self-contained composer project named after the branch
it applies to (`dev/`, `staging/`, `test/`, ...). Its `installer-paths` point
back into `site/web/app/`, so packages land next to the ones installed by the
root project. Each has its own `composer.lock`, so the root lock stays
identical across branches.

The image build runs `scripts/build-scripts/install-extra-plugins.sh` with the
branch being built (the `APP_BRANCH` build argument) and installs
`extra-plugins/<branch>/` only if it exists. These directories can therefore
live on every branch: promoting a change from dev to production never carries
dev-only plugins with it, because production builds skip `extra-plugins/dev/`.

## Adding a plugin to a branch

    cd extra-plugins/dev
    composer require wp-plugin/<slug>

Commit the updated `composer.json` and `composer.lock`. Nothing else changes.

## Adding a new branch

Copy `dev/composer.json` to `extra-plugins/<branch>/composer.json`, adjust the
`require` block, run `composer update` in that directory and commit both
files.

Composer does not cross-check the root lock and a branch lock, so a plugin
here that depends on a different version of something in the root project
will not be flagged. Keep these to self-contained plugins.
