registry_dump_name=registry_slapcat_latest.out
dump_path=/tmp

sed -i "/structuralObjectClass:.*/d" "$dump_path/$registry_dump_name"
sed -i "/entryUUID:.*/d" "$dump_path/$registry_dump_name"
sed -i "/creatorsName:.*/d" "$dump_path/$registry_dump_name"
sed -i "/createTimestamp:.*/d" "$dump_path/$registry_dump_name"
sed -i "/entryCSN:.*/d" "$dump_path/$registry_dump_name"
sed -i "/modifiersName:.*/d" "$dump_path/$registry_dump_name"
sed -i "/modifyTimestamp:.*/d" "$dump_path/$registry_dump_name"
sed -i "s/google-gateway.hcommons.org/commons.mla.org/" "$dump_path/$registry_dump_name"
sed -i "s/twitter-gateway.hcommons.org/twitter-gateway.hcommons-dev.org/" "$dump_path/$registry_dump_name"
sed -i "s/mla-idp.hcommons.org/dev.mla.org/" "$dump_path/$registry_dump_name"
sed -i "s/hc-idp.hcommons.org/hcommons-test.mla.org/" "$dump_path/$registry_dump_name"
