#!/bin/bash
set -ex

stop_prod_services() {
  ssh $remote_user@$remote_hostname "\
    sudo /etc/init.d/grouper_loader stop &&\
    sudo /etc/init.d/tomcat6 stop"
}

start_prod_services() {
  ssh $remote_user@$remote_hostname "\
    sudo /etc/init.d/grouper_loader start &&\
    sudo /etc/init.d/tomcat6 start"
}

stop_dev_services() {
    sudo /etc/init.d/grouper_loader stop
    sudo /etc/init.d/tomcat6 stop
}

start_dev_services() {
    sudo /etc/init.d/grouper_loader start
    sudo /etc/init.d/tomcat6 start
}

dump_slap() {
  ssh $remote_user@$remote_hostname "sudo slapcat -n 1 > $dump_path/idpolr_slapcat_latest.out"
}

# TODO we should do this from a stored backup rather than live sync.
dump_db() {
  # --lock-tables=false may result in inconsistent dump, but prevents bringing production down while dumping
  ssh $remote_user@$remote_hostname "\
    mysqldump\
      -u$prod_db_user\
      -p$prod_db_pass\
      -h$prod_db_host\
      --databases $prod_db_name\
      --lock-tables=false\
      --quick\
      > $dump_path/$dump_name"
}

copy_dump() {
  rsync -zhP $remote_user@$remote_hostname:$dump_path/$dump_name $dump_path/$dump_name
}

# TODO should use variables here rather than hardcode
edit_dump() {
  #sed -i "s/group-registry/grouper-dev/g" "$dump_path/$dump_name"
  sed -i "s/group-registry.commons.mla/group-registry.hcommons-dev/g" "$dump_path/$dump_name"
  sed -i "s/google.com@google-gateway.hcommons.org/google.com@commons.mla.org/g" "$dump_path/$dump_name"
  sed -i "s/twitter.com@twitter-gateway.hcommons.org/twitter.com@twitter-gateway.hcommons-dev.org/g" "$dump_path/$dump_name"
  sed -i "s/@hc-idp.hcommons.org/@hcommons-test.mla.org/g" "$dump_path/$dump_name"
  sed -i "s/@mla-idp.hcommons.org/@dev.mla.org/g" "$dump_path/$dump_name"
}

import_dump() {
  mysql --max_allowed_packet=100M -u$dev_db_user -p$dev_db_pass -h$dev_db_host $dev_db_name < "$dump_path/$dump_name"
}

clean_up() {
  ssh $remote_user@$remote_hostname "rm -v $dump_path/$dump_name"
  rm -v $dump_path/$dump_name
}

remote_hostname=group-registry.hcommons.org
remote_user=admin

# TODO find a more secure way to store credentials?
dev_db_name=grouper
dev_db_user=grouper
dev_db_host=hcommons-dev-grouper.cyongorao4kh.us-east-1.rds.amazonaws.com
dev_db_pass=2F470B7A82D0A159C9805482F068C5E46E96D2E4
prod_db_name=$dev_db_name
prod_db_user=$dev_db_user
prod_db_host=hcommons-prod-grouper.cyongorao4kh.us-east-1.rds.amazonaws.com
prod_db_pass=$dev_db_pass

# same name & path on prod & dev
#dump_name=${prod_db_name}_$(date +%Y%m%dT%H%M%S).sql
dump_name=grouper_latest.sql
dump_path=/tmp




#stop_prod_services
#dump_db
##dump_slap # this is actually for idpolr i think, not prod grouper - leave disabled
start_prod_services

copy_dump
#edit_dump
#
#stop_dev_services
#import_dump

#start_dev_services

#clean_up
