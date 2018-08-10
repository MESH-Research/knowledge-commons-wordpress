#!/bin/bash
set -ex

# listener arns on the alb
alb_80_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-prod-idms-alb/02f56f3a2c7178ff/f2b837f8a1abad2e
alb_443_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-prod-idms-alb/02f56f3a2c7178ff/4d0cce6d52c7bb46

# arn of the instance target groups
group_registry_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-prod-group-registry/69ab7c10add31887
registry_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-prod-registry/a524651b3893f2c5
mla_idp_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-prod-mla-idp/48913e524299b09b
hc_idp_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-prod-hc-idp/cbae25bea05d6763
twitter_gateway_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-prod-twitter-gateway/de32cf424462f949
google_gateway_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-prod-google-gateway/7cca580556d3158a

priority=100

for listener_arn in $alb_80_listener_arn $alb_443_listener_arn
do
	for tg_arn in \
		$group_registry_tg_arn \
		$registry_tg_arn \
		$mla_idp_tg_arn \
		$hc_idp_tg_arn \
		$twitter_gateway_tg_arn \
		$google_gateway_tg_arn
	do
		# parse hostname from tg identifier
		instance_hostname=${tg_arn##*hcommons-prod-}
		instance_hostname=${instance_hostname%%/*}.hcommons.org

		# create rule for this instance
		for host in \
			$instance_hostname
		do
			aws elbv2 create-rule \
				--listener-arn $listener_arn \
				--priority $priority \
				--conditions Field=host-header,Values="$host" \
				--actions Type=forward,TargetGroupArn=$tg_arn

			priority=$((priority+1))
		done
	done

	# describe existing rules
	# aws elbv2 describe-rules --listener-arn $listener_arn
done
