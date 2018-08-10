#!/bin/bash
set -ex

# listener arns on the alb
alb_80_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-dev-alb/17d1462aa767154b/42b0c314d5758b2f
alb_443_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-dev-alb/17d1462aa767154b/2e831b50e55e7409

# arn of the instance target groups
stage_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-wordpress/9be032affef279fa

# priority of the first rule. each subsequent rule will add 1 to this starting priority.
# this doesn't really matter as far as order but it must be unique.
# if you're not sure, just try it - if you get an error, change it and try again.
priority=200

for listener_arn in $alb_80_listener_arn $alb_443_listener_arn
do
	for tg_arn in \
		$stage_tg_arn
	do
		# parse hostname from tg identifier
		instance_hostname=hcommons-dev.org

		# create rule for this instance
		for host in \
			$instance_hostname \
			"*.$instance_hostname" \
			"*.ajs.$instance_hostname" \
			"*.aseees.$instance_hostname" \
			"*.caa.$instance_hostname" \
			"*.mla.$instance_hostname" \
			"*.up.$instance_hostname"
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
