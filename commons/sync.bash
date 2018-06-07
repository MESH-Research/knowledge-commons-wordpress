#!/bin/bash
set -e

# THINGS THIS SCRIPT DOES NOT DO WHICH YOU'LL NEED TO DO MANUALLY (ONCE):
#
# 0. make sure your dev machine hostname matches the wordpress main domain e.g. `rumi.mlacommons.org`
# there are multiple steps required for this change to be permanent, follow aws instructions below
# see https://aws.amazon.com/premiumsupport/knowledge-center/linux-static-hostname/
#
# 1. set up ssh config/keys to be able to ssh $remote_user@$remote_hostname (use ssh-agent with ForwardAgent yes)
#
# 2. add the following to /etc/sudoers on the instance this script runs on with `sudo visudo`
#   Defaults    env_keep+=SSH_AUTH_SOCK
# (otherwise rsync won't be able to ssh to the remote host using the agent on your guest)
# see http://serverfault.com/questions/107187/ssh-agent-forwarding-and-sudo-to-another-user

dump_remote_db() {
  echo "dumping remote db..."

  # to avoid collision/overload, check for an existing dumpfile & bail if found
  local in_progress=$(ssh $remote_user@$remote_hostname "[[ -e $remote_dump_path/$dump_name ]] && echo 1")
  if [[ -n "$in_progress" ]]
  then
    echo "Another mysqldump is in progress, please try again later."
    exit 1
  fi

  # --lock-tables=false may result in inconsistent dump, but prevents bringing production down while dumping
  ssh $remote_user@$remote_hostname "mysqldump\
    -u$db_user\
    -p$db_pass\
    -h$db_host\
    --databases $db_name\
    --lock-tables=false\
    --no-create-db\
    --quick\
    > $remote_dump_path/$dump_name"
}

copy_dump() {
  echo "copying dump..."

  local rsync_opts="-azh --remove-source-files"

  # turn up verbosity if requested
  [[ -n "$v" ]] && rsync_opts="$rsync_opts -P"

  rsync $rsync_opts $remote_user@$remote_hostname:$remote_dump_path/$dump_name $dump_path/
}

import_dump() {
  echo "importing dump..."

  mysql < $dump_path/$dump_name

  echo "replacing user emails..."

  # disable most emails (until a wp action changes them back)
  mysql $db_name -e "update wp_users set user_email=replace(user_email,'@','@sign');"

  echo "replacing domains..."

  $wp search-replace\
    --url="$prod_domain"\
    --all-tables\
    --path=/srv/www/commons/current/web/wp\
    "$prod_domain" "$dev_domain" > /dev/null

  # if object cache is active, make sure it is completely flushed
  if [[ -e "$project_path/object-cache.php" ]]
  then
    # if any prod domains are in the object cache, cache flush command might fail since wp-cli can't find the site(s)
    mv {$project_path/web/app,/tmp}/object-cache.php
    ~/all_networks_wp.bash --network cache flush > /dev/null

    # restore object-cache.php and try once more
    mv {/tmp,$project_path/web/app}/object-cache.php
    ~/all_networks_wp.bash --network cache flush > /dev/null

    # run cache flush a second time to ensure all networks are initialized after restoring object-cache.php
    ~/all_networks_wp.bash --network cache flush > /dev/null
  fi

  # also restart apache/shib to clear shib sessions
  sudo service apache2 restart
  sudo service shibd restart
}

sync_files() {
  echo "syncing files..."

  local rsync_opts="-azh --delete"
  local uploads_path=$project_path/web/app/uploads
  local blogsdir_path=$project_path/web/app/blogs.dir
  local more_uploads_path=$project_path/../shared

  # turn up verbosity if requested
  [[ -n "$v" ]] && rsync_opts="$rsync_opts -P"

  # fork these since they don't depend on anything else
  # TODO no longer forking due to performance concerns on prod server
  sudo rsync $rsync_opts --rsync-path='sudo rsync' $remote_user@$remote_hostname:$uploads_path/ $uploads_path
  sudo rsync $rsync_opts --rsync-path='sudo rsync' $remote_user@$remote_hostname:$blogsdir_path/ $blogsdir_path
  sudo rsync $rsync_opts --rsync-path='sudo rsync' $remote_user@$remote_hostname:$more_uploads_path/ $more_uploads_path
}

# depends on all_networks_wp.bash
activate_plugins() {
  echo "(de)activating plugins..."

  # password-protected is special. deactivate at site, then network, then site again for full effect
  ~/all_networks_wp.bash plugin deactivate\
    password-protected || :

  ~/all_networks_wp.bash plugin deactivate --network\
    password-protected\
    wordpress-mu-domain-mapping || :

  ~/all_networks_wp.bash plugin deactivate\
    password-protected || :

  ~/all_networks_wp.bash plugin activate --network\
    debug-bar\
    debug-bar-actions-and-filters-addon\
    debug-bar-console\
    debug-bar-constants\
    debug-bar-cron\
    debug-bar-elasticpress\
    debug-bar-list-dependencies\
    debug-bar-post-types\
    debug-bar-remote-requests\
    debug-bar-shortcodes\
    debug-bar-sidebars-widgets\
    debug-bar-slow-actions\
    debug-bar-taxonomies\
    debug-bar-transients\
    wordpress-debug-bar-template-trace\
    simply-show-ids\
    buddypress-body-classes\
    user-switching || :
}

print_help() {
  cat <<END
$0 syncs WordPress database & media files to this machine from production or staging.

If you haven't already, read and follow these steps to ensure everything works as intended:
  https://github.com/mlaa/dev-scripts/blob/master/commons/sync.bash#L4

Note that $0 depends on all_networks_wp.bash to flush cache & deal with plugins.

Options
  -d sync database (overwrites local database)
  -f sync files (rsync media files, deleting extraneous files)
  -p activate debug plugins
  -l re-import an existing database dump already on this machine
  -S sync from stage instead of production (this works with -d and -f as well)
  -v turn up verbosity

Examples
  $0      # Sync database & files from production (equal to -df)
  $0 -d   # Sync database only from production
  $0 -df  # Sync database & files from production
  $0 -Sdf # Sync database & files from staging
  $0 -p   # Activate debug plugins
END
}

# echo env variable values
# $1 name of .env variable
# $2 server from which to fetch value
_get_env_var() {
  local line=$(ssh $remote_user@$2 "grep '^$1' /srv/www/commons/current/.env")
  echo $line | awk -F '=' '{print $2}' | tr -d '"'
}

OPTIND=1
while getopts "h?Sdlfpv" opt; do
  case "$opt" in
    h|\?) print_help; exit 0;;
    S) S=1;; # sync from staging rather than production
    d) d=1;; # dump & import database
    l) l=1;; # import database from most recent dump
    f) f=1;; # rsync files
    p) p=1;; # (de)activate plugins
    v) v=1;; # enable verbose output
  esac
done
shift $((OPTIND-1))
if [ "$1" = -- ]; then shift; fi

[[ -n "$v" ]] && set -x

if [[ -n "$S" ]]
then
  remote_hostname=10.98.11.85 # hcommons-dev.org
else
  remote_hostname=10.100.11.140 # hcommons.org
fi

remote_user=ubuntu
project_path=/srv/www/commons/current
dev_domain="$(hostname)"
prod_domain=$remote_hostname
db_name=$(_get_env_var DB_NAME $remote_hostname)
db_host=$(_get_env_var DB_HOST $remote_hostname)
db_pass=$(_get_env_var DB_PASS $remote_hostname)
db_user=$(_get_env_var DB_USER $remote_hostname)
remote_dump_path=/mnt/efs-logs
dump_path=/tmp
dump_name=${db_name}_latest.sql
wp="sudo -u www-data wp"

# if no options were passed, do everything
if [[ -z "$r$d$l$f$p" ]]
then
  sync_files
  dump_remote_db
  copy_dump
  import_dump
  exit
fi

# otherwise just do what was asked
if [[ -n "$f" ]]; then sync_files; fi
if [[ -n "$d" ]]; then dump_remote_db; copy_dump; import_dump; fi
if [[ -n "$l" ]]; then import_dump; fi
if [[ -n "$p" ]]; then activate_plugins; fi

# make sure children have finished before exiting
wait

echo "finished!"
