#!/bin/bash
set -ex

stop_prod_services() {
  ssh $remote_user@remote_hostname "sudo service slapd stop"
}

start_prod_services() {
  ssh $remote_user@$remote_hostname "sudo service slapd start"
}

stop_dev_services() {
  sudo service slapd stop
}

start_dev_services() {
  sudo service slapd start
}

dump_idpolr_slap() {
  ssh $remote_user@$remote_hostname "sudo slapcat -n 1 > $dump_path/$dump_name"
}

copy_dump() {
  rsync -zhP $remote_user@$remote_hostname:$dump_path/$dump_name $dump_path/$dump_name
}

edit_dump() {
  #one line per attribute
  awk 'NR>1 && !sub(/^ /,""){print s; s=""} {s = s $0} END{print s}' $dump_path/$dump_name > $dump_path/$edit_name
  #remove these attributes
  sed -i "/structuralObjectClass:.*/d" $dump_path/$edit_name
  sed -i "/entryUUID:.*/d" $dump_path/$edit_name
  sed -i "/creatorsName:.*/d" $dump_path/$edit_name
  sed -i "/createTimestamp:.*/d" $dump_path/$edit_name
  sed -i "/entryCSN:.*/d" $dump_path/$edit_name
  sed -i "/modifiersName:.*/d" $dump_path/$edit_name
  sed -i "/modifyTimestamp:.*/d" $dump_path/$edit_name
  sed -i "/pwmGUID:.*/d" $dump_path/$edit_name
  sed -i "/pwdChangedTime:.*/d" $dump_path/$edit_name
  sed -i "/pwmLastPwdUpdate:.*/d" $dump_path/$edit_name
  sed -i "/pwdFailureTime:.*/d" $dump_path/$edit_name
  sed -i "/pwmEventLog::.*/d" $dump_path/$edit_name
}

import_slap() {
  cd /var/lib/ldap
  sudo rm __db.*
  sudo rm *.bdb
  sudo slapadd -v -c -n 1 -l $dump_path/$edit_name
  ls -l
  sudo chown openldap:openldap log.0000*
  sudo chown openldap:openldap __db.*
  sudo chown openldap:openldap *.bdb
  ls -l
  sudo slapindex -v -c -n 1
  ls -l
}

clean_up() {
  ssh $remote_user@$remote_hostname "rm -v $dump_path/$dump_name"
  rm -v $dump_path/$dump_name
  rm -v $dump_path/$edit_name
}

remote_hostname=hc-idp.hcommons.org
remote_user=ubuntu

# same name & path on prod & dev
dump_name=idpolr_slapcat_latest.out
edit_name=idpolr_slapcat_latest.edit
dump_path=/tmp

#echo "disabled for now. read the source"

#stop_prod_services
#dump_idpolr_slap
#start_prod_services

#copy_dump
#edit_dump

#stop_dev_services
#import_slap
#start_dev_services 

#clean_up
