#!/bin/bash
set -ex

# listener arns on the alb
alb_80_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-dev-alb/17d1462aa767154b/42b0c314d5758b2f
alb_443_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-dev-alb/17d1462aa767154b/2e831b50e55e7409

# arn of the instance target groups
group_registry_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-group-registry/cde472e04d26703d
registry_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-registry/9816a8e6433c9ac1
mla_idp_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-mla-idp/fb121c259be20475
hc_idp_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-hc-idp/2193ac7bcbec5bc4
twitter_gateway_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-twitter-gateway/e7fe8f12923fe8bf
google_gateway_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-google-gateway/941a8d62d6923bad

# priority of the first rule. each subsequent rule will add 1 to this starting priority.
# this doesn't really matter as far as order but it must be unique.
# if you're not sure, just try it - if you get an error, change it and try again.
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
		instance_hostname=${tg_arn##*hcommons-dev-}
		instance_hostname=${instance_hostname%%/*}.hcommons-dev.org

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
