#!/bin/bash
set -x

# listener arns on the alb
alb_80_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-dev-alb/17d1462aa767154b/42b0c314d5758b2f
alb_443_listener_arn=arn:aws:elasticloadbalancing:us-east-1:811884064162:listener/app/hcommons-dev-alb/17d1462aa767154b/2e831b50e55e7409

for listener_arn in $alb_80_listener_arn $alb_443_listener_arn
do
	# describe existing rules
	rule_arns=$(aws elbv2 describe-rules --listener-arn $listener_arn | jq -r '.Rules[] .RuleArn')
	for arn in $rule_arns
	do
		aws elbv2 delete-rule --rule-arn=$arn
	done
done
