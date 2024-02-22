#!/bin/sh -x
echo "Replace $1 with $2 in $3"
cat $3 | /root/go/bin/go-search-replace $1 $2 > $3.tmp
if [ -s  $3.tmp ]; then
  echo "Replacing $3 with $3.tmp"
  mv $3.tmp $3
else
  echo "No changes made"
  rm $3.tmp
fi