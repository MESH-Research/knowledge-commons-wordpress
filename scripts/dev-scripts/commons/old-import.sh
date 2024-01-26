#!/bin/sh

## Copy restore files from off-site backup. Clone project, install dependencies
## and import restore files.

# Configuration
current_date=`date +%Y%m%d`
project_bin_dir="/srv/www/commons/bin"
project_uploads_dir="/srv/www/commons/shared"
project_root_dir="/srv/www/commons/current"
project_env_file_template="$project_bin_dir/.env.template"
project_env_file="$project_root_dir/.env"
nginx_conf_dir="/etc/nginx/sites-available"
production_domain_regex="commons\.mla\.org"

mysql_backup_s3_file="s3://mla-backup/commons/db/daily/commons-db_$current_date.sql.gz"
wordpress_uploads_s3_dir="s3://mla-backup/commons/www/sync"

# Make sure script is not run by root.
if [ "$(id -u)" = "0" ]; then
   echo "Do not run this script as root." 1>&2
   exit 1
fi

# Attempt to set git config.
if [ -z `git config --global --get user.name` ]; then
  git_username=`ssh git@github.com 2>&1 >/dev/null | grep -Po 'Hi [^!]+' | awk '{ print $2 }'`
  if [ -z "$git_username" ]; then
    read -p "Enter your git config username: " git_username
  fi
  if [ ! -z "$git_username" ]; then
    git config --global user.name "$git_username"
  fi
fi

if [ -z `git config --global --get user.email` ]; then
  git_email=`curl -s https://api.github.com/users/$git_username | grep -Po '"email": "[^"]+"' | awk '{ gsub(/"/, "", $2); print $2 }'`
  if [ -z "$git_email" ]; then
    read -p "Enter your git config e-mail: " git_email
  fi
  if [ ! -z "$git_email" ]; then
    git config --global user.email "$git_email"
  fi
fi

# Get FQDN.
fqdn=`hostname -f`

# If no TLD is present, use vagrant.dev.
case "$fqdn" in
  *.*)
    echo "FQDN: $fqdn" ;;
  *)
    fqdn=$fqdn.vagrant.dev
    echo "FQDN: $fqdn" ;;
esac

# Download database backup.
echo "Downloading database backup...."
aws s3 cp $mysql_backup_s3_file /tmp/mysql.backup.sql.gz >> $project_bin_dir/import.log

# Create project directory
mkdir -p $project_root_dir
cd $project_root_dir

# Clone project and install dependencies.
if [ ! -d "$project_root_dir/.git" ]; then
  echo "Cloning project repo..."
  git init
  git remote add origin git@github.com:mlaa/commons.git
  git fetch --all
  git checkout -f -t origin/master
fi
echo "Installing dependencies..."
composer install

# Update WP uploads.
echo "Updating WP uploads...."
mkdir -p $project_root_dir/web/app

# Sync uploads and set correct permissions.
sudo aws s3 sync $wordpress_uploads_s3_dir/uploads $project_uploads_dir/uploads --exclude 'group-documents/*' --exclude 'humcore/*' --exclude 'cache/*' >> $project_bin_dir/import.log
sudo chown -R www-data:www-data $project_uploads_dir/uploads

# Create symlinks.
rm -rf $project_root_dir/web/app/blogs.dir $project_root_dir/web/app/uploads
ln -s $project_uploads_dir/blogs.dir $project_root_dir/web/app/blogs.dir
ln -s $project_uploads_dir/uploads $project_root_dir/web/app/uploads

# Notify user that we have not synced everything.
echo "Skipped syncing the following directories within ${project_uploads_dir}:"
echo "blogs.dir, uploads/cache, uploads/group-documents, uploads/humcore"
echo "Adapt the following commands to sync them anyway (watch free disk space!):"
echo "sudo aws s3 sync $wordpress_uploads_s3_dir/blogs.dir $project_uploads_dir/blogs.dir --delete"
echo "sudo chown -R www-data:www-data $project_uploads_dir/blogs.dir"

# Extract database backup and replace existing database.
echo "Loading database backup...."
gunzip -c /tmp/mysql.backup.sql.gz | mysql commons
rm /tmp/mysql.backup.sql.gz

# Populate .env with development domain.
echo "Updating project environment...."
sed "s/$production_domain_regex/$fqdn/g" $project_env_file_template > $project_env_file

# Create wp-cli.local.yml with development domain.
echo "url: http://$fqdn" > $project_root_dir/wp-cli.local.yml

# Generate self-signed certificate.
sudo $project_bin_dir/self-signed-certificate.sh $fqdn >> $project_bin_dir/import.log

# Update nginx configuration to use development domain.
echo "Updating nginx configuration...."
for file in $nginx_conf_dir/*.conf; do
  sudo sed -i "s/$production_domain_regex/$fqdn/g" $file
done
sudo service nginx restart

# Update database to use development domain.
echo "Updating database...."
sudo -u www-data wp search-replace \
--skip-columns=guid \
--network \
--url="commons.mla.org" \
--path="$project_root_dir/web/wp" \
"commons.mla.org" "$fqdn" >> $project_bin_dir/import.log
