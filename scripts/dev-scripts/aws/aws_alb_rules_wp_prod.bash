#!/bin/bash
set -ex

# listener arns on the alb
alb_80_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-prod-alb/9a374b762cc517cd/178c9de800663657
alb_443_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-prod-alb/9a374b762cc517cd/27a1a62ee63f0abe

# arn of the instance target groups
prod_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-prod-wordpress/3a46783cd4ae7d45

priority=200

for listener_arn in $alb_80_listener_arn $alb_443_listener_arn
do
	for tg_arn in \
		$prod_tg_arn
	do
		# parse hostname from tg identifier
		instance_hostname=hcommons.org

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
