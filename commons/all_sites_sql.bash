#!/bin/bash
set -ex

echo 'not finished yet, read the source'
exit

tables="$(mysql -Be "SELECT TABLE_NAME FROM information_schema.tables WHERE TABLE_NAME LIKE 'wp_%options'")"
results=()

for table in $tables
do
  [[ "$table" = "TABLE_NAME" ]] && continue # TODO do better

  #results+=("$(mysql hcommons -Be "SELECT option_value FROM $table WHERE option_name = 'current_theme'")")
  mysql hcommons -Be "SELECT option_value FROM $table WHERE option_name = 'current_theme'"
done


for result in $results
do
  echo $result
done


#+-----------+---------------+-------------------+----------+
#| option_id | option_name   | option_value      | autoload |
#+-----------+---------------+-------------------+----------+
#|       546 | current_theme | Boss. Child Theme | yes      |
#+-----------+---------------+-------------------+----------+
