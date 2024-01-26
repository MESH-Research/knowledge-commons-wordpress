#!/bin/bash
set -ex

stop_prod_services() {
  ssh $remote_user@$remote_hostname "sudo service apache2 stop && sudo service slapd stop"
  ssh ubuntu@hc-idp.hcommons.org "sudo service slapd stop"
}

start_prod_services() {
  ssh $remote_user@$remote_hostname "sudo service apache2 start && sudo service slapd start"
  ssh ubuntu@hc-idp.hcommons.org "sudo service slapd start"
}

stop_dev_services() {
  sudo service apache2 stop
  sudo service slapd stop
}

start_dev_services() {
  sudo service apache2 start
  sudo service slapd start
}

dump_slap() {
  ssh $remote_user@$remote_hostname "sudo slapcat -n 1 > $dump_path/registry_slapcat_latest.out"
}

dump_idpolr_slap() {
  ssh ubuntu@hc-idp.hcommons.org "sudo slapcat -n 1 > $dump_path/idpolr_slapcat_latest.out"
}

# TODO we should do this from a stored backup rather than live sync.
dump_db() {
  # --lock-tables=false may result in inconsistent dump, but prevents bringing production down while dumping
  ssh $remote_user@$remote_hostname "\
    mysqldump\
      -u$prod_db_user\
      -p$prod_db_pass\
      -h$prod_db_host\
      --lock-tables=false\
      --quick\
      $prod_db_name\
      > $dump_path/$dump_name"
}

copy_dump() {
  rsync -zhP $remote_user@$remote_hostname:$dump_path/$dump_name $dump_path/$dump_name
  rsync -zhP $remote_user@$remote_hostname:$dump_path/registry_slapcat_latest.out $dump_path/registry_slapcat_latest.out
}

# TODO should use variables here rather than hardcode
edit_dump() {
  # junk in the sqldump that breaks import unless we comment it out
  sed -i "s#\(\/\*![0-9]\+ DEFINER\)#-- \1#g" "$dump_path/$dump_name"

  # service addresses/urls
  # these are now redundant, handled by the TLD catchall hcommons.org -> hcommons-dev.org
  #sed -i "s#registry.hcommons.org#registry.hcommons-dev.org#g" "$dump_path/$dump_name"
  #sed -i "s#https://group-registry.hcommons.org#https://group-registry.hcommons-dev.org#g" "$dump_path/$dump_name"
  # RDS. TODO change prod to use the hostname
  sed -i "s#10\.101\.11\.248#hcommons-dev-registry.cyongorao4kh.us-east-1.rds.amazonaws.com#g" "$dump_path/$dump_name"
  # hc-idp TODO can't this use the hostname too?
  sed -i "s#ldap://10\.101\.11\.210#ldap://10.98.11.98#g" "$dump_path/$dump_name"

  # idp scopes
  # TODO update these to match domains
  sed -i "s#google.com@google-gateway.hcommons.org#google.com@commons.mla.org#g" "$dump_path/$dump_name"
  sed -i "s#twitter.com@twitter-gateway.hcommons.org#twitter.com@twitter-gateway.hcommons-dev.org#g" "$dump_path/$dump_name"
  # prior to 2017-06-09 the idps below started with @ to match exampleuser@idp-scope.com.
  # removed @ when we discovered the value of the "Humanities Commons IdP Scope" option doesn't include @
  sed -i "s#hc-idp.hcommons.org#hcommons-test.mla.org#g" "$dump_path/$dump_name"
  sed -i "s#mla-idp.hcommons.org#dev.mla.org#g" "$dump_path/$dump_name"

  # primary url
  sed -i "s#hcommons.org#hcommons-dev.org#g" "$dump_path/$dump_name"

  # ?
  #sed -i "s#https://group-registry-dev.commons.mla.org#https://grouper-dev.commons.mla.org#g" "$dump_path/$dump_name"

  # fix email addresses (for enrollments)
  sed -i "s#\([a-z][a-z][a-z]\?[a-z]\?\)@hcommons-dev.org#\1@hcommons.org#g" "$dump_path/$dump_name"
}

import_slap() {
  bash modify_slapcat.bash
  bash sync_ldap_from_copy.bash
}

import_dump() {
  mysql --max_allowed_packet=100M -u$dev_db_user -p$dev_db_pass -h$dev_db_host $dev_db_name < "$dump_path/$dump_name"
}

clean_up() {
  ssh $remote_user@$remote_hostname "rm -v $dump_path/$dump_name"
  rm -v $dump_path/$dump_name
}

clear_comanage_cache() {
  (
    cd /var/www/comanage-registry/app
    sudo sudo -u www-data ./Console/cake cache
  )
}

remote_hostname=registry.hcommons.org
remote_user=admin

# TODO find a more secure way to store credentials?
dev_db_name=registry
#dev_db_user=comanage
dev_db_user=rdsroot
dev_db_host=hcommons-dev-registry.cyongorao4kh.us-east-1.rds.amazonaws.com
dev_db_pass=a6823214c7fdcd52829b1dec61ef8d23e394b8bb
prod_db_name=$dev_db_name
#prod_db_user=$dev_db_user 
prod_db_user=comanage
prod_db_host=hcommons-prod-registry.cyongorao4kh.us-east-1.rds.amazonaws.com
prod_db_pass=$dev_db_pass

# same name & path on prod & dev
#dump_name=${prod_db_name}_$(date +%Y%m%dT%H%M%S).sql
dump_name=registry_latest.sql
dump_path=/tmp

#echo "disabled for now. read the source"

#stop_prod_services
#dump_db
#dump_slap
#dump_idpolr_slap
#start_prod_services

#copy_dump
#edit_dump
#
#stop_dev_services
#import_dump
#import_slap

#clear_comanage_cache
#start_dev_services 

#clean_up
