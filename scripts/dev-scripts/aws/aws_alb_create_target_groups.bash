#!/bin/bash
set -x

hc_dev_vpc_id=vpc-21c0c947



#for name in atwood chaucer heinlein hurston musashi
#for name in registry group-registry hc-idp mla-idp google-gateway twitter-gateway
for name in wordpress
do
	aws elbv2 create-target-group \
		--name hcommons-dev-$name \
		--protocol HTTPS \
		--port 443 \
		--vpc-id $hc_dev_vpc_id
done
