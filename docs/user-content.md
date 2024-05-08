# Working with user content

## Accessing Content

To work with user content, you need to import the WordPress database and uploads directory. 

The uploads directory can be accessed through AWS EFS via an NFS mount. You can do this by:

1. Copy the `lando.efs.yaml` file to `lando.yaml` in the root of the project.
2. Connect to the Commons OpenVPN.
3. Rebuild with `./lando-rebuild.sh`.

The database can be synced using the `lando s3-pull` command:

1. Configure the AWS CLI: `lando aws configure`. You will need to obtain CLI user credentials.
2. Run `lando s3-pull` to list available content.
3. Run `lando s3-pull production-db-full-localized` to import the database. This takes a while.

## Logging in to the site

There are two login options for local development:

1) Login using the WordPress native login. The `scripts/reset-local-passwords.php` script will set all user passwords to 'password'.
2) Login using the dev IDMS stack. Once secrets have been imported using `scripts/get-local-secrets.php`, your local instance should be able to login using the dev IDMS stack, as you would from EC2-based dev instances. (Local enrollment flows do not yet exist, so you cannot register using this method.)