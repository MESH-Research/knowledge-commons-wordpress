#!/bin/bash

set -ex

# wrapper for slackpost.bash that posts $1 to #commons-dev

~/dev-scripts/commons/slackpost.bash 'https://hooks.slack.com/services/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' '#commons-dev' 'platybot' "$1"
