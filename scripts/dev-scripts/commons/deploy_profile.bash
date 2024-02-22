#!/bin/bash
set -ex

# code
cd /srv/www/commons/current
git fetch
# will need to be changed to master for production deployment
git checkout profile-release
git pull --ff-only
composer install

# wipe/import data
bash /srv/www/commons/bin/import.sh

# really just here for domain mapping disabling
# NOT FOR LIVE DEPLOYMENT! STAGE ONLY!
#bash /srv/www/commons/current/scripts/setupdev.bash

# migrate
cd /srv/www/commons/current/web/app/plugins/profile
# CAREFUL - THIS CHANGES DATA!
bash bash/migrate.bash

# mode
chmod -R 777 /srv/www/commons/current/

# because of the changes to cbox-mla js, we need to regen the cache.
# this may be the wrong way to do it but it works.
# saving options & touching ini files should do it according to the doc, but it doesn't.
rm -rf /srv/www/commons/current/web/app/uploads/exports/cbox-*
