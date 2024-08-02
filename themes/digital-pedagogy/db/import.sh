#!/bin/bash
# shellcheck disable=SC1068
cd $( dirname "${BASH_SOURCE[0]}" )
mydir=$(pwd);
parent=$(basename $(dirname "$mydir"));
myarray=(`find ${mydir} -maxdepth 1 -name "${parent}_db_*.sql"`)
if [ ${#myarray[@]} -gt 0 ]; then
#    file = "style_db_01222020.sql";
    file=$(find . -maxdepth 1 -type f -name "*.sql");
    wp db import "${file[0]}";
    mkdir -p "./old_imports/"; mv ./*.sql $_;
else
    echo "No SQL files avail for import"
fi
