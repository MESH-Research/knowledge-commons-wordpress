#!/bin/bash
set -e

# THINGS THIS SCRIPT DOES NOT DO WHICH YOU'LL NEED TO DO MANUALLY (ONCE):
#
# 1. set up keys to be able to ssh to remote & vagrant machines
#
# 2. add the following to /etc/sudoers on the instance this script runs on (use `sudo visudo` to edit)
#   Defaults    env_keep+=SSH_AUTH_SOCK
# (otherwise rsync won't be able to ssh to the remote host using the agent on your guest)
# see http://serverfault.com/questions/107187/ssh-agent-forwarding-and-sudo-to-another-user

dump_remote_db() {
  # --lock-tables=false may result in inconsistent dump, but prevents bringing production down while dumping
  ssh $remote_user@$remote_hostname "mysqldump\
    -u$db_user\
    -p$db_pass\
    -h$db_host\
    --databases $db_name\
    --lock-tables=false\
    --no-create-db\
    --quick\
    > $dump_path/$dump_name"
}

copy_dump() {
  $ssh "rsync -azhP --remove-source-files $remote_user@$remote_hostname:$dump_path/$dump_name $project_path/"
}

import_dump() {
  $ssh "mysql < $project_path/$dump_name"

  # disable most emails (until a wp action changes them back)
  $ssh "mysql $db_name -e 'update wp_users set user_email=replace(user_email,'@','@sign');'"

  # TODO create this on vagrant if not exists. or find a better way, this is a hack anyway
  pre_php=/tmp/__pre.php; [[ -e "$pre_php" ]] || echo "<?php error_reporting( 0 ); define( 'WP_DEBUG', false );" > "$pre_php"
  $ssh "\
    touch '$pre_php' &&\
    $wp search-replace\
      --require='$pre_php'\
      --url='$prod_domain'\
      --all-tables\
      --path=/srv/www/commons/current/web/wp\
      '$prod_domain' '$dev_domain' > /dev/null"

  $ssh "./all_networks_wp.bash --network cache flush"
}

sync_files() {
  local rsync_opts="-azh --delete"
  local uploads_path=$project_path/web/app/uploads
  local blogsdir_path=$project_path/web/app/blogs.dir

  # turn up verbosity if requested
  [[ -n "$v" ]] && rsync_opts="$rsync_opts -P"

  $ssh "\
    sudo rsync $rsync_opts --rsync-path='sudo rsync' $remote_user@$remote_hostname:$uploads_path/ $uploads_path &&\
    sudo rsync $rsync_opts --rsync-path='sudo rsync' $remote_user@$remote_hostname:$blogsdir_path/ $blogsdir_path" || :
}

# depends on all_networks_wp.bash
activate_plugins() {
  # password-protected is special. deactivate at site, then network, then site again for full effect
  $ssh "\
    ./all_networks_wp.bash plugin deactivate\
      password-protected\
      ;\
    ./all_networks_wp.bash plugin deactivate --network\
      password-protected\
      wordpress-mu-domain-mapping\
      ;\
    ./all_networks_wp.bash plugin deactivate\
      password-protected\
      ;\
    ./all_networks_wp.bash plugin activate --network\
      debug-bar\
      debug-bar-actions-and-filters-addon\
      wordpress-debug-bar-template-trace\
      simply-show-ids\
      debug-bar-elasticpress\
      buddypress-body-classes\
      user-switching\
  "
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
    h|\?) echo "i should probably write help output sometime. until then, read the source"; exit 0;;
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
  remote_hostname=hcommons-dev.org
else
  remote_hostname=hcommons.org
fi

remote_user=ubuntu
vagrant_hostname=$(hostname -s)
project_path=/srv/www/commons/current
dev_domain=$vagrant_hostname.mlacommons.org
prod_domain=$remote_hostname
db_name=$(_get_env_var DB_NAME $remote_hostname)
db_host=$(_get_env_var DB_HOST $remote_hostname)
db_pass=$(_get_env_var DB_PASS $remote_hostname)
db_user=$(_get_env_var DB_USER $remote_hostname)
dump_path=/tmp
dump_name=${db_name}_latest.sql
ssh="ssh -o ForwardAgent=yes $vagrant_hostname"
wp="sudo -u www-data wp"

# if no options were passed, do everything
if [[ -z "$r$d$l$f$p" ]]
then
  sync_files & # fork this since it doesn't depend on anything else, proceed while it's running
  dump_remote_db
  copy_dump
  import_dump
  activate_plugins
  exit
fi

# otherwise just do what was asked
if [[ -n "$d" ]]; then dump_remote_db; copy_dump; import_dump; fi
if [[ -n "$l" ]]; then import_dump; fi
if [[ -n "$f" ]]; then sync_files; fi
if [[ -n "$p" ]]; then activate_plugins; fi
