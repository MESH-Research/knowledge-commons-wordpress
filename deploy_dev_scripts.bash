#!/bin/bash
set -ex

for i in atwood chaucer heinlein musashi rumi
do
	ssh $i '~/dev-scripts/selfupdate.bash'
done
