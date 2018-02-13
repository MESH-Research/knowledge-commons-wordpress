#!/bin/bash
set -x

for i in atwood chaucer heinlein musashi rumi hcd
do
	ssh $i '~/dev-scripts/selfupdate.bash'
done
