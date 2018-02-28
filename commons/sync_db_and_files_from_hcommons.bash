#!/bin/bash
set -ex

remote_hostname=hcommons.org
remote_user=ubuntu

dev_domain=hcommons-dev.org
prod_domain=$remote_hostname

# TODO find a more secure way to store credentials?
prod_db_name=hcommons
prod_db_user=hcdb
prod_db_host=hcommons-prod-wordpress.cyongorao4kh.us-east-1.rds.amazonaws.com
prod_db_pass=hAXzDgVuWsrWSdIOZqvDse
#dev_db_name=hcommons_dev
dev_db_name=hcommons
dev_db_user=hcommons_dev
dev_db_host=hcommons-dev-wordpress.cyongorao4kh.us-east-1.rds.amazonaws.com
dev_db_pass=sANQ8NF5DkgSsIT7SskbxO

#dump_name=${prod_db_name}_$(date +%Y%m%dT%H%M%S).sql
dump_name=${prod_db_name}_latest.sql
dump_path=/tmp

project_path=/srv/www/commons/current

shared_path=/srv/www/commons/shared

# TODO we should do this from a stored backup rather than live sync.
sync_db() {
  # --lock-tables=false may result in inconsistent dump, but prevents bringing production down while dumping
  # changed dump path due to low available space on root drive. this one is EFS
  ssh $remote_user@$remote_hostname "mysqldump\
    -u$prod_db_user\
    -p$prod_db_pass\
    -h$prod_db_host\
    --databases $prod_db_name\
    --lock-tables=false\
    --quick\
    > /mnt/efs-logs/$dump_name"

  rsync -zhP --remove-source-files $remote_user@$remote_hostname:/mnt/efs-logs/$dump_name $dump_path/$dump_name

  # DO NOT USE. breaks serialized data. use search-replace instead (below)
  #sed -i "s/$prod_domain/$dev_domain/g" $dump_path/$dump_name

  mysql --max_allowed_packet=100M -u$dev_db_user -p$dev_db_pass -h$dev_db_host $dev_db_name < "$dump_path/$dump_name"

  #ssh $remote_user@$remote_hostname "rm -v $dump_path/$dump_name"
  rm -v $dump_path/$dump_name

  sudo -u www-data wp search-replace\
    --url="$prod_domain"\
    --all-tables\
    --path=/srv/www/commons/current/web/wp\
    "$prod_domain" "$dev_domain"

  # fix email addresses
  sudo -u www-data wp search-replace\
    --url="$dev_domain"\
    --all-tables\
    --path=/srv/www/commons/current/web/wp\
    "@$dev_domain" "@$prod_domain"

  # update domain mapping
  sudo -u www-data wp search-replace\
    --url="$dev_domain"\
    --all-tables\
    --path=/srv/www/commons/current/web/wp\
    "style.mla.org" "style.mla-dev.org"
  sudo -u www-data wp search-replace\
    --url="$dev_domain"\
    --all-tables\
    --path=/srv/www/commons/current/web/wp\
    "action.mla.org" "action.mla-dev.org"

  # not necessary, but here for reference
  #local tables="$(mysql -u$dev_db_user -p$dev_db_pass -h$dev_db_host $dev_db_name -BNe 'show tables')"
  #for table in $tables
  #do
  #  $project_path/vendor/wp-cli/wp-cli/bin/wp search-replace\
  #    --url="$prod_domain"\
  #    --network\
  #    --path=/srv/www/commons/current/web/wp\
  #    "$prod_domain" "$dev_domain" "$table"
  #done

  cd $project_path
  ~/all_networks_wp.bash cache flush

  # disable most emails (until a wp action changes them back)
  mysql -u$dev_db_user -p$dev_db_pass -h$dev_db_host $dev_db_name -e "update wp_users set user_email=replace(user_email,'@','@sign') where ID not in (1,1937,4381);"
  # fix whitelisted emails
  bash ~/dev-scripts/commons/restore_user_emails.bash adonlon lfulgencio nalonso eknappe rwms

  # handle sparkpost/bp-rbe settings
  bash /home/ubuntu/dev-scripts/commons/set_sp_settings_dev.bash
}

sync_files() {
  local rsync_opts="-azhP --delete"
  # rsync can fail to transfer ninjaforms csvs due to permissions. proceed anyway.
  sudo rsync $rsync_opts --rsync-path="sudo rsync" $remote_user@$remote_hostname:$shared_path/ $shared_path || :
}

# depends on all_networks_wp.bash
activate_plugins() {
  cd $project_path
  ~/all_networks_wp.bash plugin activate --network\
    debug-bar\
    debug-bar-actions-and-filters-addon\
    wordpress-debug-bar-template-trace\
    simply-show-ids\
    debug-bar-elasticpress\
    buddypress-body-classes
}

OPTIND=1
while getopts "h?dfp" opt; do
  case "$opt" in
    h|\?) echo "i should probably write help output sometime. until then, read the source"; exit 0;;
    d) d=1;;
    f) f=1;;
    p) p=1;;
  esac
done
shift $((OPTIND-1))
if [ "$1" = -- ]; then shift; fi

# if no options were passed, do everything
if [[ -z "$r$d$f$p" ]]
then
  sync_db
  sync_files
  #activate_plugins
  bash ~/dev-scripts/commons/slackpost_commons_dev.bash 'hcommons-dev.org sync finished'
  exit
fi

# otherwise just do what was asked
if [[ -n "$d" ]]; then sync_db; fi
if [[ -n "$f" ]]; then sync_files; fi
if [[ -n "$p" ]]; then activate_plugins; fi
