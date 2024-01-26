#!/bin/sh
input_file=$1
banner_file=avatar-staff-overlay.png

convert $input_file -resize 300x300 output/${input_file}.tmp
composite -gravity south $banner_file output/${input_file}.tmp output/$input_file
rm output/${input_file}.tmp
