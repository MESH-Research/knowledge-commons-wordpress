cd /var/lib/ldap
sudo service slapd stop
sudo rm __db.*
sudo rm *.bdb
sudo slapadd -v -c -n 1 -l /tmp/registry_slapcat_latest.out
ls -l
sudo chown openldap:openldap log.0000*
sudo chown openldap:openldap __db.*
sudo chown openldap:openldap *.bdb
 ls -l
sudo slapindex -v -c -n 1
ls -l
sudo service slapd start
