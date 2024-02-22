#!/bin/sh
apk add --no-cache go
go install github.com/mitchellh/gox@latest
go install github.com/Automattic/go-search-replace@latest