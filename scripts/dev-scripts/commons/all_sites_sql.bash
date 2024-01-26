#!/bin/bash
set -ex

echo 'not finished yet, read the source'
exit

blog_ids="$(mysql -BNe 'SELECT blog_id FROM hcommons.wp_blogs')"

for blog_id in $blog_ids
do
  options_table=wp_${blog_id}_options

  if [[ -n "$(mysql hcommons -Be "show tables like '$options_table'")" ]]
  then
    mysql hcommons -BNe "SELECT domain FROM wp_blogs WHERE blog_id = $blog_id"
    mysql hcommons -BNe "SELECT option_value FROM $options_table WHERE option_name = 'current_theme'"
  else
    echo "no such table '$options_table'"
  fi

done


#+-----------+---------------+-------------------+----------+
#| option_id | option_name   | option_value      | autoload |
#+-----------+---------------+-------------------+----------+
#|       546 | current_theme | Boss. Child Theme | yes      |
#+-----------+---------------+-------------------+----------+
