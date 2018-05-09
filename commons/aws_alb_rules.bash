#!/bin/bash
set -ex

# listener arns on the alb
hcommons_dev_alb_80_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-dev-alb/17d1462aa767154b/42b0c314d5758b2f
hcommons_dev_alb_443_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-dev-alb/17d1462aa767154b/2e831b50e55e7409

# arn of the target group for this instance.
hcommons_dev_rumi_tg_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:targetgroup/hcommons-dev-rumi/ca6e1322c2cc1e6a

# priority of the first rule. each subsequent rule will add 1 to this starting priority.
# this doesn't really matter as far as order but it must be unique.
# if you're not sure, just try it - if you get an error, change it and try again.
priority=20

for listener_arn in $hcommons_dev_alb_80_listener_arn $hcommons_dev_alb_443_listener_arn
do
	# create rule for this instance
	for host in \
		rumi.mlacommons.org \
		'*.rumi.mlacommons.org' \
		'*.ajs.rumi.mlacommons.org' \
		'*.aseees.rumi.mlacommons.org' \
		'*.caa.rumi.mlacommons.org' \
		'*.mla.rumi.mlacommons.org' \
		'*.up.rumi.mlacommons.org'
	do
		aws elbv2 create-rule \
			--listener-arn $listener_arn \
			--priority $priority \
			--conditions Field=host-header,Values="$host" \
			--actions Type=forward,TargetGroupArn=$hcommons_dev_rumi_tg_arn

		priority=$((priority+1))
	done

	# describe existing rules
	# aws elbv2 describe-rules --listener-arn $listener_arn
done
