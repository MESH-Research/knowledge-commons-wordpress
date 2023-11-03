#!/bin/sh -x
echo "Replace $1 with $2 in $3"
cat $3 | /root/go/bin/go-search-replace $1 $2 > $3.tmp
mv $3.tmp $3