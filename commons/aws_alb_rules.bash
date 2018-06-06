#!/bin/bash
set -ex

# listener arns on the alb
alb_80_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-dev-alb/17d1462aa767154b/42b0c314d5758b2f
alb_443_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-dev-alb/17d1462aa767154b/2e831b50e55e7409

# arn of the instance target groups
rumi_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-rumi/ca6e1322c2cc1e6a
atwood_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-atwood/a94860ed716401ed
heinlein_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-heinlein/df91597b2992dcb7
hurston_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-hurston/e041969f7c2bb002
musashi_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-musashi/7943fc13971632af


# priority of the first rule. each subsequent rule will add 1 to this starting priority.
# this doesn't really matter as far as order but it must be unique.
# if you're not sure, just try it - if you get an error, change it and try again.
priority=30

for listener_arn in $alb_80_listener_arn $alb_443_listener_arn
do
	for tg_arn in \
		$atwood_tg_arn \
		$chaucer_tg_arn \
		$heinlein_tg_arn \
		$hurston_tg_arn \
		$musashi_tg_arn
		#$rumi_tg_arn
	do
		# parse hostname from tg identifier
		instance_hostname=${tg_arn##*hcommons-dev-}
		instance_hostname=${instance_hostname%%/*}.mlacommons.org

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
