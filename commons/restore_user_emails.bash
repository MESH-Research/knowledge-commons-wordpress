#!/bin/bash
set -ex

# pass one or more usernames to this script and it update their emails to remove '@sign'
# e.g. restore_user_emails.bash rwms

for username in $*
do
  user_email="$(wp --url=$(hostname) user get --field=user_email $username)"
  wp --url=$(hostname) user update $username --user_email=${user_email/sign}
done
