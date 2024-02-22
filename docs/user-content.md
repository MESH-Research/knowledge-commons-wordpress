# Working with user content

## Accessing Content

To manage user content we use S3 to store (partial) exports of the `uploads/` directory and WordPress database. You can use `scripts/s3-pull.php` to view and import this content. Generally imported content will wipe the current `uploads/` directory and database, so any local changes will be lost.

In order to interact with exports, you will need to [install](https://docs.aws.amazon.com/cli/latest/userguide/getting-started-install.html) and [configure](https://docs.aws.amazon.com/cli/latest/userguide/cli-authentication-user.html#cli-authentication-user-configure.title) the AWS CLI. You will also need to obtain CLI user credentials.

Examples:

- `scripts/s3-pull.php` List available content.
- `scripts/s3-pull.php --help` See help.
- `scripts/s3-pull.php --import-prefix=hcdev-base-sites` Import uploads and db from `hcdev-base-sites` prefix.

Note: These files can be quite large, so it can take a while to run the import script.

## Logging in to the site

There are two login options for local development:

1) Login using the WordPress native login. The `scripts/reset-local-passwords.php` script will set all user passwords to 'password'.
2) Login using the dev IDMS stack. Once secrets have been imported using `scripts/get-local-secrets.php`, your local instance should be able to login using the dev IDMS stack, as you would from EC2-based dev instances. (Local enrollment flows do not yet exist, so you cannot register using this method.)