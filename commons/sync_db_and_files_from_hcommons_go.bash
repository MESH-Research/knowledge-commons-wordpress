#!/bin/bash
set -ex

# echo env variable values
# $1 name of .env variable
# $2 server from which to fetch value
_get_remote_env_var() {
  local line=$(ssh $remote_user@$2 "grep '^$1' /srv/www/commons/current/.env")
  echo $line | awk -F '=' '{print $2}' | tr -d '"' | tr -d "'"
}

# echo env variable values
# $1 name of .env variable
# $2 server from which to fetch value
_get_local_env_var() {
  local line=$(grep ^$1 /srv/www/commons/current/.env)
  echo $line | awk -F '=' '{print $2}' | tr -d '"' | tr -d "'"
}

setup_data() {
  remote_hostname=hcommons.org
  remote_user=ubuntu

  dev_domain="$(hostname)"
  prod_domain=$remote_hostname

  prod_db_name=$(_get_remote_env_var DB_NAME $remote_hostname)
  prod_db_host=$(_get_remote_env_var DB_HOST $remote_hostname)
  prod_db_pass=$(_get_remote_env_var DB_PASS $remote_hostname)
  prod_db_user=$(_get_remote_env_var DB_USER $remote_hostname)

  dev_db_name=$(_get_local_env_var DB_NAME)
  dev_db_host=$(_get_local_env_var DB_HOST)
  dev_db_pass=$(_get_local_env_var DB_PASS)
  dev_db_user=$(_get_local_env_var DB_USER)

  dump_name=${prod_db_name}_latest.sql
  dump_1_name=${prod_db_name}_latest_1.sql
  dump_path=/tmp
  project_path=/srv/www/commons/current
  shared_path=/srv/www/commons/shared
echo $dev_db_host
}

export_db() {
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
}

sync_db() {

  #rsync -zhP --remove-source-files $remote_user@$remote_hostname:/mnt/efs-logs/$dump_name $dump_path/$dump_name
  rsync -zhP $remote_user@$remote_hostname:/mnt/efs-logs/$dump_name $dump_path/$dump_name
  cat $dump_path/$dump_name | ~/go/bin/go-search-replace "$prod_domain" "$dev_domain" > $dump_path/$dump_1_name
}

import_db() {

  # DO NOT USE. breaks serialized data. use search-replace instead (below)
  #sed -i "s/$prod_domain/$dev_domain/g" $dump_path/$dump_name

  mysql --max_allowed_packet=100M -u$dev_db_user -p$dev_db_pass -h$dev_db_host $dev_db_name < "$dump_path/$dump_1_name"

  #ssh $remote_user@$remote_hostname "rm -v $dump_path/$dump_name"
  #rm -v $dump_path/$dump_name
  #rm -v $dump_path/$dump_1_name
}

modify_db() {

  #  now done with go-search-replace
###  sudo -u www-data wp search-replace\
###    --url="$prod_domain"\
###    --all-tables\
###    --path=/srv/www/commons/current/web/wp\
###    "$prod_domain" "$dev_domain"
###  wp --url=hcommons.org --path=/srv/www/commons/current/web/wp cache flush

  # fix email addresses
###  sudo -u www-data wp search-replace\
###    --url="$dev_domain"\
###    --all-tables\
###    --path=/srv/www/commons/current/web/wp\
###    "@$dev_domain" "@$prod_domain"

#error
#  networks=("" "ajs." "aseees." "arlisna." "caa." "mla." "msu." "sah." "up.")
#  for network in "${networks[@]}"
#  do
#    sudo -u www-data wp search-replace --url="$network$dev_domain" --network --path=/srv/www/commons/current/web/wp --report-changed-only "@$dev_domain" "@$prod_domain"
#  done

###  # update domain mapping
###  sudo -u www-data wp search-replace\
###    --url="$dev_domain"\
###    --all-tables\
###    --path=/srv/www/commons/current/web/wp\
###    "style.mla.org" "style.mla-dev.org"

###  sudo -u www-data wp search-replace\
###    --url="$dev_domain"\
###    --all-tables\
###    --path=/srv/www/commons/current/web/wp\
###    "action.mla.org" "action.mla-dev.org"

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
  ~/dev-scripts/commons/all_networks_wp.bash cache flush

  # disable most emails (until a wp action changes them back)
  mysql -u$dev_db_user -p$dev_db_pass -h$dev_db_host $dev_db_name -e "update wp_users set user_email=replace(user_email,'@','@sign') where ID not in (1,4381);"
  # fix whitelisted emails
  bash ~/dev-scripts/commons/restore_user_emails.bash adonlon nalonso eknappe jbetancourt
}

sync_settings() {
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
  ~/dev-scripts/commons/all_networks_wp.bash plugin activate --network\
    debug-bar\
    debug-bar-actions-and-filters-addon\
    wordpress-debug-bar-template-trace\
    simply-show-ids\
    debug-bar-elasticpress\
    buddypress-body-classes
}

OPTIND=1
while getopts "h?defimps" opt; do
  case "$opt" in
    h|\?) echo "i should probably write help output sometime. until then, read the source"; exit 0;;
    d) d=1;;
    e) e=1;;
    f) f=1;;
    i) i=1;;
    m) m=1;;
    p) p=1;;
    s) s=1;;
  esac
done
shift $((OPTIND-1))
if [ "$1" = -- ]; then shift; fi

# if no options were passed, do everything
if [[ -z "$r$d$e$f$p" ]]
then
  #bash ~/dev-scripts/commons/slackpost_commons_dev.bash "$(hostname) sync starting"
  setup_data
  #export_db
  sync_db
##  import_db
##  modify_db
##  sync_settings
##  sync_files
  #activate_plugins
  #bash ~/dev-scripts/commons/slackpost_commons_dev.bash "$(hostname) sync finished"
  #echo "activate the shibboleth plugin if this is a shibboleth install!"
  exit
fi

# otherwise just do what was asked
setup_data
if [[ -n "$e" ]]; then export_db; fi
if [[ -n "$d" ]]; then sync_db; fi
if [[ -n "$i" ]]; then import_db; fi
if [[ -n "$m" ]]; then modify_db; fi
if [[ -n "$f" ]]; then sync_files; fi
if [[ -n "$s" ]]; then sync_settings; fi
if [[ -n "$p" ]]; then activate_plugins; fi
