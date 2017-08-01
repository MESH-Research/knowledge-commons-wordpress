#!/bin/bash
set -ex

# run this on the host, NOT the vagrant guest.
#
# things this script does not do which you'll need to do manually (once):
#
# set up keys to be able to ssh to remote & vagrant machines
# for vagrant -> remote ssh, either set up those keys as well or use ssh-agent. for sudo to use ssh-agent, add:
# Defaults    env_keep+=SSH_AUTH_SOCK
# to /etc/sudoers (visudo) on $vagrant_hostname
# otherwise rsync won't be able to ssh to the remote host using the agent on your guest
# see http://serverfault.com/questions/107187/ssh-agent-forwarding-and-sudo-to-another-user
#
# change domains in config/environments/all.php
# -define( 'COOKIEHASH', md5( 'alpha.hcommons.org' ) );
# -define( 'COOKIE_DOMAIN', 'alpha.hcommons.org' );
# +define( 'COOKIEHASH', md5( 'mustache.vagrant.dev' ) );
# +define( 'COOKIE_DOMAIN', 'mustache.vagrant.dev' );
#
# ensure .env has the right db name & credentials


sync_db() {
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

  $ssh "rsync -azhP --remove-source-files $remote_user@$remote_hostname:$dump_path/$dump_name $project_path/"

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

  $ssh "\
    ./all_networks_wp.bash --network cache flush
  "
}

sync_files() {
  local rsync_opts="-azhP --delete"
  local uploads_path=$project_path/web/app/uploads
  local blogsdir_path=$project_path/web/app/blogs.dir

  $ssh "\
    sudo rsync $rsync_opts --rsync-path='sudo rsync' $remote_user@$remote_hostname:$uploads_path/ $uploads_path &&\
    sudo rsync $rsync_opts --rsync-path='sudo rsync' $remote_user@$remote_hostname:$blogsdir_path/ $blogsdir_path" || :
}

# depends on all_networks_wp.bash
activate_plugins() {
  # password-protected is special. deactivate at site, then network, then site again for full effect
  #$ssh "\
  #  ./all_networks_wp.bash plugin deactivate\
  #    password-protected\
  #    ;\
  #  ./all_networks_wp.bash plugin deactivate --network\
  #    password-protected\
  #    wordpress-mu-domain-mapping\
  #    ;\
  #  ./all_networks_wp.bash plugin deactivate\
  #    password-protected\
  #    ;\
  #  ./all_networks_wp.bash plugin activate --network\
  #    debug-bar\
  #    debug-bar-actions-and-filters-addon\
  #    wordpress-debug-bar-template-trace\
  #    simply-show-ids\
  #"
  $ssh "\
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


OPTIND=1
while getopts "h?Sdfp" opt; do
  case "$opt" in
    h|\?) echo "i should probably write help output sometime. until then, read the source"; exit 0;;
    S) S=1;; # sync from staging rather than production
    d) d=1;; # dump & import database
    f) f=1;; # rsync files
    p) p=1;; # (de)activate plugins
  esac
done
shift $((OPTIND-1))
if [ "$1" = -- ]; then shift; fi


# TODO use .env http://stackoverflow.com/questions/19331497/set-environment-variables-from-file

if [[ -n "$S" ]]
then
  remote_hostname=hcommons-dev.org
  db_host=hcommons-dev-wordpress.cyongorao4kh.us-east-1.rds.amazonaws.com
  db_pass=sANQ8NF5DkgSsIT7SskbxO
  db_user=hcommons_dev
else
  remote_hostname=hcommons.org
  db_host=hcommons-prod-wordpress.cyongorao4kh.us-east-1.rds.amazonaws.com
  db_pass=hAXzDgVuWsrWSdIOZqvDse
  db_user=hcdb
fi

vagrant_hostname=$(hostname -s)
project_path=/srv/www/commons/current
remote_user=ubuntu
dump_path=/tmp
dev_domain=$vagrant_hostname.mlacommons.org # TODO dynamic
prod_domain=$remote_hostname
db_name=hcommons
#dump_name=${db_name}_$(date +%Y%m%dT%H%M%S).sql
dump_name=${db_name}_latest.sql

# TODO switch depending on whether we're running  vagrant/virtualbox or just connecting locally
#ssh="vagrant ssh $vagrant_hostname -c"
ssh="ssh -o ForwardAgent=yes $vagrant_hostname"

# TODO standardize...
#wp="$project_path/vendor/wp-cli/wp-cli/bin/wp"
wp="sudo -u www-data wp"

# if no options were passed, do everything
if [[ -z "$r$d$f$p" ]]
then
  sync_db
  sync_files
  activate_plugins
  exit
fi

# otherwise just do what was asked
if [[ -n "$d" ]]; then sync_db; fi
if [[ -n "$f" ]]; then sync_files; fi
if [[ -n "$p" ]]; then activate_plugins; fi
